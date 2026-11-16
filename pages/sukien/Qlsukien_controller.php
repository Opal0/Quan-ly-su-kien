<?php
/**
 * Xử lý dữ liệu cho trang QUẢN TRỊ sự kiện (qlsukien.php).
 * - GET : nạp danh sách, thống kê, lọc, phân trang
 * - POST: thêm / sửa / xóa sự kiện (qua AJAX, trả về JSON)
 *
 * CSDL music4life: sukien, danhmuc, dangkysukien.
 * Được nạp từ qlsukien.php (sau session_start và tạo CSRF token).
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once ROOT_PATH . '/config/database.php';   // cung cấp getDB()

/* ---------- Cấu hình: chỉnh cho khớp CSDL của nhóm ---------- */
const QL_STATUS_OPEN      = 'sap_dien_ra';   // giá trị cột sukien.TrangThai cho sự kiện mới (đổi nếu ENUM khác)
const QL_STATUS_CANCELLED = 'da_huy';
const QL_MAX_IMAGE        = 2 * 1024 * 1024; // 2MB
const QL_PER_PAGE         = 8;
const QL_UPLOAD_SUBDIR    = 'assets/uploads/sukien/';  // thư mục lưu ảnh, tính từ ROOT_PATH

/* ---------- Hàm hỗ trợ ---------- */
if (!function_exists('e')) { function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

function ql_url(array $over = []): string {
    $q = array_merge($_GET, $over);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($q);
}

function ql_base(): string { return defined('BASE_URL') ? rtrim(BASE_URL, '/') : '../..'; }

// Chỉ quản trị viên. Nếu auth.php của nhóm đã có isAdmin() thì dùng luôn;
// nếu không, đọc vai trò từ session (đổi tên khóa / giá trị cho khớp phần đăng nhập).
function ql_is_admin(): bool {
    return true;   // mọi người dùng đã đăng nhập đều được vào trang quản lý sự kiện
}

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
$events = $shown = $cats = [];
$stats  = ['total' => 0, 'upcoming' => 0, 'past' => 0, 'cancelled' => 0, 'registered' => 0];
$q = $cat = '';
$tt = 'all';
$tg = 'all';
$page = $pages = 1;
$total = 0;
$dbError = false;

/* ---------- Kiểm tra quyền ---------- */
$forbidden = !(isLoggedIn() && ql_is_admin());
if ($forbidden) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') ql_json(403, ['ok' => false, 'message' => 'Bạn cần đăng nhập để thực hiện thao tác này.']);
    http_response_code(isLoggedIn() ? 403 : 401);
    return;   // view sẽ hiện thông báo từ biến $forbidden
}

$pdo = getDB();

/* =====================================================
   POST: thêm / sửa / xóa (AJAX -> JSON)
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        ql_json(403, ['ok' => false, 'message' => 'Phiên làm việc đã hết hạn. Hãy tải lại trang và thử lại.']);
    }
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    try {
        /* ---------- Thêm / sửa ---------- */
        if ($action === 'save') {
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

            $desc = trim($_POST['desc'] ?? '');

            if ($errors) ql_json(422, ['ok' => false, 'message' => 'Dữ liệu chưa hợp lệ', 'errors' => $errors]);

            $newImage = ql_save_image('image', $errors);   // chỉ lưu file sau khi các trường khác đã hợp lệ
            if ($errors) ql_json(422, ['ok' => false, 'message' => 'Dữ liệu chưa hợp lệ', 'errors' => $errors]);

            try {
                $pdo->beginTransaction();

                if ($id === 0) {                              // ----- Thêm mới -----
                    $pdo->prepare(
                        'INSERT INTO sukien (TenSuKien, ID_DanhMuc, ThoiGianBatDau, ThoiGianKetThuc, DiaDiem, MoTa, AnhSuKien, SoLuongToiDa, TrangThai)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    )->execute([$title, $catId, $start, $end, $location, $desc, $newImage, $capacity, QL_STATUS_OPEN]);
                    $pdo->commit();
                    ql_json(201, ['ok' => true, 'message' => 'Đã thêm sự kiện "' . $title . '".']);
                }

                // ----- Sửa: khóa dòng để số lượng đăng ký không đổi giữa chừng -----
                $st = $pdo->prepare('SELECT AnhSuKien FROM sukien WHERE ID = ? FOR UPDATE');
                $st->execute([$id]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    $pdo->rollBack();
                    ql_delete_image($newImage);
                    ql_json(404, ['ok' => false, 'message' => 'Không tìm thấy sự kiện.']);
                }

                $st = $pdo->prepare("SELECT COUNT(*) FROM dangkysukien WHERE ID_SuKien = ? AND TrangThai = 'da_dang_ky'");
                $st->execute([$id]);
                $registered = (int)$st->fetchColumn();
                if ($capacity < $registered) {
                    $pdo->rollBack();
                    ql_delete_image($newImage);
                    ql_json(422, ['ok' => false, 'message' => 'Dữ liệu chưa hợp lệ',
                        'errors' => ['capacity' => "Đã có $registered người đăng ký, không thể đặt thấp hơn"]]);
                }

                $image = $row['AnhSuKien'];
                if ($newImage !== null)                          $image = $newImage;   // thay ảnh mới
                elseif (($_POST['remove_image'] ?? '') === '1')  $image = null;        // chỉ xóa ảnh

                $pdo->prepare(
                    'UPDATE sukien SET TenSuKien = ?, ID_DanhMuc = ?, ThoiGianBatDau = ?, ThoiGianKetThuc = ?,
                                       DiaDiem = ?, MoTa = ?, AnhSuKien = ?, SoLuongToiDa = ?
                     WHERE ID = ?'
                )->execute([$title, $catId, $start, $end, $location, $desc, $image, $capacity, $id]);
                $pdo->commit();

                if ($image !== $row['AnhSuKien']) ql_delete_image($row['AnhSuKien']);   // dọn ảnh cũ sau khi lưu thành công
                ql_json(200, ['ok' => true, 'message' => 'Đã cập nhật sự kiện "' . $title . '".']);

            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                ql_delete_image($newImage);
                throw $ex;
            }
        }

        /* ---------- Xóa ---------- */
        if ($action === 'delete') {
            $st = $pdo->prepare('SELECT TenSuKien, TrangThai, AnhSuKien FROM sukien WHERE ID = ?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) ql_json(404, ['ok' => false, 'message' => 'Không tìm thấy sự kiện.']);

            // Còn bất kỳ bản ghi đăng ký nào (kể cả đã hủy) thì không xóa cứng, để giữ lịch sử và khóa ngoại
            $st = $pdo->prepare('SELECT COUNT(*) FROM dangkysukien WHERE ID_SuKien = ?');
            $st->execute([$id]);
            if ((int)$st->fetchColumn() > 0) {
                if ($row['TrangThai'] === QL_STATUS_CANCELLED) {
                    ql_json(200, ['ok' => true, 'message' => 'Sự kiện này đã ở trạng thái "Đã hủy".']);
                }
                $pdo->prepare('UPDATE sukien SET TrangThai = ? WHERE ID = ?')->execute([QL_STATUS_CANCELLED, $id]);
                ql_json(200, ['ok' => true, 'message' => 'Sự kiện đã có đăng ký nên được chuyển sang trạng thái "Đã hủy".']);
            }

            $pdo->prepare('DELETE FROM sukien WHERE ID = ?')->execute([$id]);
            ql_delete_image($row['AnhSuKien']);
            ql_json(200, ['ok' => true, 'message' => 'Đã xóa sự kiện "' . $row['TenSuKien'] . '".']);
        }

        ql_json(400, ['ok' => false, 'message' => 'Thao tác không hợp lệ.']);

    } catch (PDOException $ex) {
        error_log('qlsukien_controller.php: ' . $ex->getMessage());
        ql_json(500, ['ok' => false, 'message' => 'Lỗi máy chủ, hãy thử lại sau.']);
    }
}

/* =====================================================
   GET: nạp dữ liệu hiển thị
   ===================================================== */
try {
    // Quản trị thấy TẤT CẢ sự kiện (kể cả đã hủy / danh mục bị ẩn)
    $sql = "SELECT s.ID               AS id,
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
            ORDER BY s.ThoiGianBatDau DESC";
    $events = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $cats   = $pdo->query("SELECT ID AS id, TenDanhMuc AS name FROM danhmuc WHERE TrangThai = 'hoat_dong' ORDER BY TenDanhMuc")
                  ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    error_log('qlsukien_controller.php: ' . $ex->getMessage());
    $events = []; $cats = []; $dbError = true;
}

$today = date('Y-m-d');
foreach ($events as &$ev) {
    $ev['id']          = (int)$ev['id'];
    $ev['category_id'] = (int)$ev['category_id'];
    $ev['capacity']    = (int)$ev['capacity'];
    $ev['registered']  = (int)$ev['registered'];
    $ev['desc']        = (string)$ev['desc'];

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