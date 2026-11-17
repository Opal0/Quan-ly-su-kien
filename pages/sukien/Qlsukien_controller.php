<?php
/**
 * Xử lý dữ liệu cho trang quản lý sự kiện của ORGANIZER (qlsukien.php).
 * Thêm / sửa / xóa KHÔNG áp dụng ngay: chỉ tạo yêu cầu trong bảng yeucau_sukien,
 * admin duyệt ở pages/admin/duyetsukien.php thì bảng sukien mới thay đổi.
 *
 * - GET : nạp danh sách sự kiện của tôi, thống kê, lọc, phân trang, các yêu cầu của tôi
 * - POST: save / delete / cancel_request (AJAX, trả về JSON)
 * Được nạp từ qlsukien.php (sau session_start và tạo CSRF token).
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once ROOT_PATH . '/config/database.php';   // cung cấp getDB()

const QL_STATUS_CANCELLED = 'da_huy';
const QL_MAX_IMAGE        = 2 * 1024 * 1024; // 2MB
const QL_PER_PAGE         = 8;
const QL_UPLOAD_SUBDIR    = 'assets/uploads/sukien/';  // thư mục lưu ảnh, tính từ ROOT_PATH

/* ---------- Hàm hỗ trợ ---------- */
function ql_url(array $over = []): string {
    $q = array_merge($_GET, $over);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($q);
}

function ql_base(): string { return defined('BASE_URL') ? rtrim(BASE_URL, '/') : '../..'; }

function ql_json(int $code, array $payload): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function ql_img_url(?string $img): string {
    if (!$img) return '';
    if (preg_match('#^(https?:)?//#', $img)) return $img;
    return ql_base() . '/' . (strpos($img, '/') !== false ? ltrim($img, '/') : QL_UPLOAD_SUBDIR . $img);
}

function ql_parse_dt(string $s): ?string {
    $s = trim(str_replace('T', ' ', $s));
    if ($s === '') return null;
    $ts = strtotime($s);
    return $ts === false ? null : date('Y-m-d H:i:s', $ts);
}

// Lưu ảnh upload, trả về tên file mới (hoặc null nếu không có file)
function ql_save_image(string $field, array &$errors): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK)  { $errors['image'] = 'Tải ảnh lên thất bại, hãy thử lại'; return null; }
    if ($f['size'] > QL_MAX_IMAGE)      { $errors['image'] = 'Ảnh tối đa 2MB'; return null; }

    // Kiểm tra loại file thật bằng nội dung, không tin tên file người dùng gửi
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext)                          { $errors['image'] = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP'; return null; }

    $dir = ROOT_PATH . '/' . QL_UPLOAD_SUBDIR;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) { $errors['image'] = 'Không tạo được thư mục lưu ảnh'; return null; }
    $name = 'sk_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) { $errors['image'] = 'Không lưu được ảnh'; return null; }
    return $name;
}

// Chỉ xóa file do chính hệ thống tạo ra (tên dạng sk_xxxxxxxxxxxxxxxx.jpg), không đụng ảnh cũ của nhóm
function ql_delete_image(?string $name): void {
    if ($name && preg_match('/^sk_[a-f0-9]{16}\.(jpg|png|webp)$/', $name)) {
        $p = ROOT_PATH . '/' . QL_UPLOAD_SUBDIR . $name;
        if (is_file($p)) @unlink($p);
    }
}

/* ---------- Giá trị mặc định cho view ---------- */
$events = $shown = $cats = $myRequests = [];
$stats  = ['total' => 0, 'upcoming' => 0, 'past' => 0, 'cancelled' => 0, 'registered' => 0];
$q = $cat = '';
$tt = 'all';
$tg = 'all';
$page = $pages = 1;
$total = 0;
$dbError = false;

/* ---------- Kiểm tra quyền: chỉ organizer (admin dùng trang duyệt) ---------- */
$forbidden = !(isLoggedIn() && currentRole() === 'organizer');
if ($forbidden) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') ql_json(403, ['ok' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
    http_response_code(isLoggedIn() ? 403 : 401);
    return;   // view sẽ hiện thông báo từ biến $forbidden
}

$uid = (int)currentUser()['id'];
$pdo = getDB();

// "Sự kiện của tôi" = sự kiện có yêu cầu THÊM của tôi đã được admin duyệt (không cần sửa bảng sukien)
$own = "EXISTS (SELECT 1 FROM yeucau_sukien y WHERE y.ID_SuKien = s.ID AND y.ID_NguoiGui = :uid
                  AND y.LoaiYeuCau = 'them' AND y.TrangThai = 'da_duyet')";

/* =====================================================
   POST: gửi yêu cầu thêm / sửa / xóa, rút yêu cầu (AJAX -> JSON)
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Chấp nhận token của auth.php (csrfValid) hoặc token do qlsukien.php tự tạo ($_SESSION['csrf_token'])
    $tokenView = (string)($_SESSION['csrf_token'] ?? '');
    $csrfOk = csrfValid() || ($tokenView !== '' && hash_equals($tokenView, (string)($_POST['csrf_token'] ?? '')));
    if (!$csrfOk) {
        ql_json(403, ['ok' => false, 'message' => 'Phiên làm việc đã hết hạn. Hãy tải lại trang và thử lại.']);
    }
    $action    = $_POST['action'] ?? '';
    $id        = (int)($_POST['id'] ?? 0);
    $uploaded  = null;    // ảnh mới vừa lưu, để dọn nếu có lỗi
    $imageKept = false;

    try {
        if (!in_array($action, ['save', 'delete', 'cancel_request'], true)) {
            ql_json(400, ['ok' => false, 'message' => 'Thao tác không hợp lệ.']);
        }

        /* ---------- Rút lại yêu cầu đang chờ ---------- */
        if ($action === 'cancel_request') {
            $rid = (int)($_POST['request_id'] ?? 0);
            $st = $pdo->prepare("SELECT DuLieu FROM yeucau_sukien WHERE ID = ? AND ID_NguoiGui = ? AND TrangThai = 'cho_duyet'");
            $st->execute([$rid, $uid]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) ql_json(404, ['ok' => false, 'message' => 'Không tìm thấy yêu cầu đang chờ duyệt.']);

            $del = $pdo->prepare("DELETE FROM yeucau_sukien WHERE ID = ? AND TrangThai = 'cho_duyet'");
            $del->execute([$rid]);
            if ($del->rowCount() === 0) ql_json(409, ['ok' => false, 'message' => 'Yêu cầu này đã được admin xử lý.']);

            ql_delete_image((json_decode((string)$row['DuLieu'], true) ?: [])['image'] ?? null);
            ql_json(200, ['ok' => true, 'message' => 'Đã rút lại yêu cầu.']);
        }

        /* ---------- Sửa / xóa: phải là sự kiện của tôi và chưa có yêu cầu nào đang chờ ---------- */
        $cur = null;
        if ($id > 0) {
            $st = $pdo->prepare(
                "SELECT s.TenSuKien,
                        (SELECT COUNT(*) FROM yeucau_sukien p WHERE p.ID_SuKien = s.ID AND p.TrangThai = 'cho_duyet') AS pending,
                        (SELECT COUNT(*) FROM dangkysukien r WHERE r.ID_SuKien = s.ID AND r.TrangThai = 'da_dang_ky') AS registered
                 FROM sukien s WHERE s.ID = :id AND $own"
            );
            $st->execute([':id' => $id, ':uid' => $uid]);
            $cur = $st->fetch(PDO::FETCH_ASSOC);
            if (!$cur)            ql_json(403, ['ok' => false, 'message' => 'Bạn chỉ được quản lý sự kiện do mình tạo.']);
            if ($cur['pending'])  ql_json(409, ['ok' => false, 'message' => 'Sự kiện này đang có yêu cầu chờ duyệt. Hãy đợi admin xử lý hoặc rút yêu cầu cũ.']);
        }

        if ($action === 'delete') {
            if (!$cur) ql_json(404, ['ok' => false, 'message' => 'Không tìm thấy sự kiện.']);
            $type = 'xoa';
            $data = ['title' => $cur['TenSuKien']];
        } else {
            /* ---------- Thêm / sửa: kiểm tra dữ liệu ---------- */
            $errors = [];

            $title = trim($_POST['title'] ?? '');
            if ($title === '' || mb_strlen($title) > 255) $errors['title'] = 'Tên sự kiện không được trống và tối đa 255 ký tự';

            $catId = (int)($_POST['category_id'] ?? 0);
            $st = $pdo->prepare('SELECT COUNT(*) FROM danhmuc WHERE ID = ?');
            $st->execute([$catId]);
            if (!$catId || (int)$st->fetchColumn() === 0) $errors['category_id'] = 'Hãy chọn danh mục hợp lệ';

            $start = ql_parse_dt($_POST['start'] ?? '');
            $end   = ql_parse_dt($_POST['end'] ?? '');
            if (!$start) $errors['start'] = 'Thời gian bắt đầu không hợp lệ';
            if (!$end)   $errors['end']   = 'Thời gian kết thúc không hợp lệ';
            if ($start && $end && $end < $start) $errors['end'] = 'Thời gian kết thúc phải sau thời gian bắt đầu';

            $location = trim($_POST['location'] ?? '');
            if ($location === '' || mb_strlen($location) > 255) $errors['location'] = 'Địa điểm không được trống và tối đa 255 ký tự';

            $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT);
            if ($capacity === false || $capacity < 1) $errors['capacity'] = 'Số lượng tối đa phải là số nguyên lớn hơn 0';
            elseif ($cur && $capacity < (int)$cur['registered']) $errors['capacity'] = "Đã có {$cur['registered']} người đăng ký, không thể đặt thấp hơn";

            if ($errors) ql_json(422, ['ok' => false, 'message' => 'Dữ liệu chưa hợp lệ', 'errors' => $errors]);

            $uploaded = ql_save_image('image', $errors);   // chỉ lưu file sau khi các trường khác đã hợp lệ
            if ($errors) ql_json(422, ['ok' => false, 'message' => 'Dữ liệu chưa hợp lệ', 'errors' => $errors]);

            $type = $id === 0 ? 'them' : 'sua';
            $data = [
                'title' => $title, 'category_id' => $catId, 'start' => $start, 'end' => $end,
                'location' => $location, 'desc' => trim($_POST['desc'] ?? ''), 'capacity' => $capacity,
                'image' => $uploaded, 'remove_image' => (($_POST['remove_image'] ?? '') === '1'),
            ];
        }

        /* ---------- Lưu yêu cầu chờ duyệt (bảng sukien chưa đổi gì) ---------- */
        $pdo->prepare('INSERT INTO yeucau_sukien (ID_SuKien, ID_NguoiGui, LoaiYeuCau, DuLieu) VALUES (?, ?, ?, ?)')
            ->execute([$id ?: null, $uid, $type, json_encode($data, JSON_UNESCAPED_UNICODE)]);
        $imageKept = true;   // ảnh nằm trong yêu cầu, admin từ chối hoặc người gửi rút lại thì mới xóa

        // Báo cho tất cả admin (lỗi thông báo không làm hỏng yêu cầu đã gửi)
        $label = ['them' => 'thêm', 'sua' => 'chỉnh sửa', 'xoa' => 'xóa'][$type];
        try {
            foreach ($pdo->query("SELECT ID FROM nguoidung WHERE VaiTro = 'admin' AND TrangThai = 'hoat_dong'")->fetchAll(PDO::FETCH_COLUMN) as $adminId) {
                guiThongBao($pdo, (int)$adminId, 'Có yêu cầu sự kiện cần duyệt',
                    ($_SESSION['user_name'] ?? 'Một nhà tổ chức') . " gửi yêu cầu $label sự kiện \"{$data['title']}\".", 'sukien_yeucau');
            }
        } catch (Throwable $t) {
            error_log('qlsukien_controller.php (thông báo): ' . $t->getMessage());
        }

        ql_json(202, ['ok' => true, 'message' => [
            'them' => 'Đã gửi sự kiện "' . $data['title'] . '" cho admin duyệt. Sự kiện sẽ hiển thị sau khi được duyệt.',
            'sua'  => 'Đã gửi yêu cầu chỉnh sửa "' . $data['title'] . '". Thông tin cũ được giữ nguyên cho đến khi admin duyệt.',
            'xoa'  => 'Đã gửi yêu cầu xóa sự kiện "' . $data['title'] . '", chờ admin duyệt.',
        ][$type]]);

    } catch (PDOException $ex) {
        if (!$imageKept) ql_delete_image($uploaded);
        error_log('qlsukien_controller.php: ' . $ex->getMessage());
        ql_json(500, ['ok' => false, 'message' => 'Lỗi máy chủ, hãy thử lại sau.']);
    }
}

/* =====================================================
   GET: nạp dữ liệu hiển thị
   ===================================================== */
$pendingMap = [];
try {
    $st = $pdo->prepare(
        "SELECT s.ID               AS id,
                s.TenSuKien        AS title,
                s.ID_DanhMuc       AS category_id,
                d.TenDanhMuc       AS category,
                s.ThoiGianBatDau   AS start_at,
                s.ThoiGianKetThuc  AS end_at,
                s.DiaDiem          AS location,
                s.MoTa             AS `desc`,
                s.AnhSuKien        AS image,
                s.SoLuongToiDa     AS capacity,
                s.TrangThai        AS db_status,
                (SELECT COUNT(*) FROM dangkysukien r
                  WHERE r.ID_SuKien = s.ID AND r.TrangThai = 'da_dang_ky') AS registered
         FROM sukien s
         LEFT JOIN danhmuc d ON d.ID = s.ID_DanhMuc
         WHERE $own
         ORDER BY s.ThoiGianBatDau DESC"
    );
    $st->execute([':uid' => $uid]);
    $events = $st->fetchAll(PDO::FETCH_ASSOC);

    $cats = $pdo->query("SELECT ID AS id, TenDanhMuc AS name FROM danhmuc WHERE TrangThai = 'hoat_dong' ORDER BY TenDanhMuc")
                ->fetchAll(PDO::FETCH_ASSOC);

    // Sự kiện nào đang có yêu cầu sửa / xóa chờ duyệt
    foreach ($pdo->query("SELECT ID_SuKien, LoaiYeuCau FROM yeucau_sukien WHERE TrangThai = 'cho_duyet' AND ID_SuKien IS NOT NULL") as $p) {
        $pendingMap[(int)$p['ID_SuKien']] = $p['LoaiYeuCau'];
    }

    // Yêu cầu của tôi (mới nhất trước), gồm cả yêu cầu "thêm" chưa có trong bảng sukien
    $st = $pdo->prepare('SELECT * FROM yeucau_sukien WHERE ID_NguoiGui = ? ORDER BY NgayGui DESC LIMIT 30');
    $st->execute([$uid]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $d = json_decode((string)$r['DuLieu'], true) ?: [];
        $myRequests[] = [
            'id'         => (int)$r['ID'],
            'type'       => $r['LoaiYeuCau'],                                   // them | sua | xoa
            'type_label' => ['them' => 'Thêm', 'sua' => 'Chỉnh sửa', 'xoa' => 'Xóa'][$r['LoaiYeuCau']] ?? '',
            'event_id'   => $r['ID_SuKien'] ? (int)$r['ID_SuKien'] : null,
            'title'      => (string)($d['title'] ?? ''),
            'status'     => $r['TrangThai'],                                    // cho_duyet | da_duyet | tu_choi
            'reason'     => (string)$r['LyDoTuChoi'],
            'created_at' => $r['NgayGui'],
            'handled_at' => $r['NgayXuLy'],
            'image_url'  => ql_img_url($d['image'] ?? null),
        ];
    }
} catch (PDOException $ex) {
    error_log('qlsukien_controller.php: ' . $ex->getMessage());
    $events = []; $cats = []; $myRequests = []; $pendingMap = []; $dbError = true;
}

$today = date('Y-m-d');
foreach ($events as &$ev) {
    $ev['id']          = (int)$ev['id'];
    $ev['category_id'] = (int)$ev['category_id'];
    $ev['capacity']    = (int)$ev['capacity'];
    $ev['registered']  = (int)$ev['registered'];
    $ev['desc']        = (string)$ev['desc'];
    $ev['pending']     = $pendingMap[$ev['id']] ?? null;   // 'sua' | 'xoa' | null (view: hiện "Chờ duyệt", khóa nút sửa/xóa)

    $startDate = substr((string)$ev['start_at'], 0, 10);
    $endDate   = $ev['end_at'] ? substr((string)$ev['end_at'], 0, 10) : $startDate;
    if     ($ev['db_status'] === QL_STATUS_CANCELLED)                          $ev['status'] = 'cancelled';
    elseif ($ev['db_status'] === 'da_ket_thuc' || $endDate < $today)           $ev['status'] = 'past';
    elseif ($startDate <= $today)                                              $ev['status'] = 'today';
    else                                                                       $ev['status'] = 'upcoming';

    $ev['image_url']   = ql_img_url($ev['image']);
    $ev['start_input'] = $ev['start_at'] ? date('Y-m-d\TH:i', strtotime($ev['start_at'])) : '';
    $ev['end_input']   = $ev['end_at']   ? date('Y-m-d\TH:i', strtotime($ev['end_at']))   : '';
    $ev['pct']         = $ev['capacity'] > 0 ? min(100, (int)round($ev['registered'] / $ev['capacity'] * 100)) : 0;

    // Dữ liệu đưa sang JS để đổ vào form sửa (không cần gọi thêm request)
    $ev['json'] = [
        'id' => $ev['id'], 'title' => $ev['title'], 'category_id' => $ev['category_id'],
        'start' => $ev['start_input'], 'end' => $ev['end_input'], 'location' => $ev['location'],
        'desc' => $ev['desc'], 'capacity' => $ev['capacity'], 'registered' => $ev['registered'],
        'image_url' => $ev['image_url'],
    ];

    $stats['total']++;
    if ($ev['status'] === 'upcoming' || $ev['status'] === 'today') $stats['upcoming']++;
    if ($ev['status'] === 'past')                                  $stats['past']++;
    if ($ev['status'] === 'cancelled')                             $stats['cancelled']++;
    if ($ev['status'] !== 'cancelled')                             $stats['registered'] += $ev['registered'];
}
unset($ev);

/* ---------- Lọc, tìm kiếm, phân trang ---------- */
$q   = trim($_GET['q'] ?? '');
$cat = (string)(int)($_GET['loai'] ?? 0);
if ($cat === '0') $cat = '';
$tt  = in_array($_GET['tt'] ?? '', ['all', 'upcoming', 'today', 'past', 'cancelled'], true) ? $_GET['tt'] : 'all';
$tg  = in_array($_GET['tg'] ?? '', ['all', 'today', 'week', 'month'], true) ? $_GET['tg'] : 'all';

$list = array_values(array_filter($events, function ($ev) use ($q, $cat, $tt, $tg, $today) {
    if ($tt === 'upcoming'  && $ev['status'] !== 'upcoming')  return false;
    if ($tt === 'today'     && $ev['status'] !== 'today')     return false;
    if ($tt === 'past'      && $ev['status'] !== 'past')      return false;
    if ($tt === 'cancelled' && $ev['status'] !== 'cancelled') return false;
    $d = substr((string)$ev['start_at'], 0, 10);
    if ($tg === 'today' && $d !== $today)                                              return false;
    if ($tg === 'week'  && ($d < $today || $d > date('Y-m-d', strtotime('+7 days')))) return false;
    if ($tg === 'month' && substr($d, 0, 7) !== substr($today, 0, 7))                  return false;
    if ($cat !== '' && (string)$ev['category_id'] !== $cat)   return false;
    if ($q !== '' && mb_stripos($ev['title'] . ' ' . $ev['location'], $q) === false) return false;
    return true;
}));

$total = count($list);
$pages = max(1, (int)ceil($total / QL_PER_PAGE));
$page  = min(max(1, (int)($_GET['trang'] ?? 1)), $pages);
$shown = array_slice($list, ($page - 1) * QL_PER_PAGE, QL_PER_PAGE);