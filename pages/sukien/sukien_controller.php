<?php
/**
 * Xử lý dữ liệu cho trang Sự kiện (người dùng): truy vấn CSDL, lọc, phân trang.
 * Dùng CHUNG bảng với trang quản lý Qlsukien: sukien, danhmuc, dangkysukien
 * => thêm / sửa / hủy sự kiện bên Qlsukien thì trang này đổi theo.
 * Được nạp từ sukien.php (sau session_start và tạo CSRF token).
 */
require_once __DIR__ . '/../../includes/auth.php';   // cung cấp e(), isLoggedIn(), currentUser(), getDB(), BASE_URL

/* ---------- Cấu hình: giữ khớp với Qlsukien_controller.php ---------- */
const SK_STATUS_CANCELLED = 'da_huy';
const SK_STATUS_FINISHED  = 'da_ket_thuc';
const SK_REG_ACTIVE       = 'da_dang_ky';   // dangkysukien.TrangThai của lượt đăng ký còn hiệu lực
const SK_UPLOAD_SUBDIR    = 'assets/uploads/sukien/';   // cùng thư mục với Qlsukien_controller.php

/* ---------- Hàm hỗ trợ ---------- */
if (!function_exists('sk_url')) { function sk_url(array $over = []) {
    $q = array_merge($_GET, $over);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($q);
} }

/* Đường dẫn ảnh sự kiện (cột AnhSuKien) - cùng cách xử lý với ql_img_url() bên trang quản lý */
if (!function_exists('sk_img_url')) { function sk_img_url(?string $img): string {
    if (!$img) return '';
    if (preg_match('#^(https?:)?//#', $img)) return $img;
    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '../..';
    return $base . '/' . (strpos($img, '/') !== false ? ltrim($img, '/') : SK_UPLOAD_SUBDIR . $img);
} }

/* ---------- Giá trị mặc định cho view ---------- */
$events = $cats = $shown = [];
$myRegs = [];
$dbError = false;
$isLoggedIn = isLoggedIn();
$thang = ['', 'Th1','Th2','Th3','Th4','Th5','Th6','Th7','Th8','Th9','Th10','Th11','Th12'];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ---------- Đăng ký / hủy: nay thực hiện ở trang đặt vé ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Hãy bấm "Chọn sự kiện / Đặt vé" để đăng ký tham gia.'];
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#danh-sach');
    exit;
}

/* ---------- Dữ liệu sự kiện (cùng nguồn với Qlsukien) ---------- */
try {
    $pdo = getDB();
    // Người dùng chỉ thấy sự kiện chưa hủy và thuộc danh mục đang hoạt động
    $sql = "SELECT s.ID             AS id,
                   s.TenSuKien      AS title,
                   COALESCE(d.TenDanhMuc, 'Khác') AS category,
                   s.ThoiGianBatDau AS start_at,
                   s.ThoiGianKetThuc AS end_at,
                   s.DiaDiem        AS location,
                   s.MoTa           AS `desc`,
                   s.AnhSuKien      AS image,
                   s.SoLuongToiDa   AS capacity,
                   s.TrangThai      AS db_status,
                   (SELECT COUNT(*) FROM dangkysukien r
                     WHERE r.ID_SuKien = s.ID AND r.TrangThai = '" . SK_REG_ACTIVE . "') AS registered
            FROM sukien s
            LEFT JOIN danhmuc d ON d.ID = s.ID_DanhMuc
            WHERE s.TrangThai <> '" . SK_STATUS_CANCELLED . "'
              AND (d.ID IS NULL OR d.TrangThai = 'hoat_dong')
            ORDER BY s.ThoiGianBatDau";
    $events = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) {
    error_log('SukienController: ' . $ex->getMessage());
    $events = [];
    $dbError = true;
}

$today = date('Y-m-d');
foreach ($events as &$ev) {
    $ev['id']         = (int)$ev['id'];
    $ev['capacity']   = (int)$ev['capacity'];
    $ev['registered'] = (int)$ev['registered'];
    $ev['desc']       = (string)$ev['desc'];
    $ev['image_url']  = sk_img_url($ev['image'] ?? '');

    $startTs   = strtotime((string)$ev['start_at']);
    $endTs     = $ev['end_at'] ? strtotime((string)$ev['end_at']) : null;
    $startDate = date('Y-m-d', $startTs);
    $endDate   = $endTs ? date('Y-m-d', $endTs) : $startDate;

    $ev['date'] = $startDate;                       // sukien.php dùng 'date' để hiện ngày
    $ev['time'] = date('H:i', $startTs);
    if ($endTs) {
        $ev['time'] .= ' - ' . ($endDate === $startDate ? date('H:i', $endTs) : date('d/m H:i', $endTs));
    }

    if     ($ev['db_status'] === SK_STATUS_FINISHED || $endDate < $today) $ev['status'] = 'past';
    elseif ($startDate <= $today)                                         $ev['status'] = 'today';
    else                                                                  $ev['status'] = 'upcoming';

    $ev['full']             = $ev['capacity'] > 0 && $ev['registered'] >= $ev['capacity'];
    $ev['registered_by_me'] = false;
}
unset($ev);

/* ---------- Sự kiện mà người dùng đang đăng nhập đã đăng ký ---------- */
if ($isLoggedIn && !$dbError) {
    try {
        $st = $pdo->prepare("SELECT ID_SuKien FROM dangkysukien WHERE ID_NguoiDung = ? AND TrangThai = '" . SK_REG_ACTIVE . "'");
        $st->execute([currentUser()['id']]);
        $mine = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        foreach ($events as &$ev) {
            if (in_array($ev['id'], $mine, true)) {
                $ev['registered_by_me'] = true;
                $myRegs[$ev['id']] = '';             // chưa có mã vé ở bảng này
            }
        }
        unset($ev);
    } catch (Throwable $ex) {
        error_log('SukienController (myRegs): ' . $ex->getMessage());   // không làm hỏng cả trang
    }
}

/* ---------- Lọc, tìm kiếm, phân trang ---------- */
$q     = trim($_GET['q'] ?? '');
$cat   = $_GET['loai'] ?? '';
$tt    = $_GET['tt'] ?? 'upcoming';           // upcoming | past | all
$cats  = array_values(array_unique(array_column($events, 'category')));

$list = array_filter($events, function ($ev) use ($q, $cat, $tt) {
    if ($tt === 'upcoming' && $ev['status'] === 'past')  return false;
    if ($tt === 'past'     && $ev['status'] !== 'past')  return false;
    if ($cat !== '' && $ev['category'] !== $cat)         return false;
    if ($q !== '' && mb_stripos($ev['title'] . ' ' . $ev['location'] . ' ' . $ev['desc'], $q) === false) return false;
    return true;
});
usort($list, fn($a, $b) => $tt === 'past' ? strcmp($b['date'], $a['date']) : strcmp($a['date'], $b['date']));

$perPage = 8;   // 2 hàng x 4 sự kiện
$total   = count($list);
$pages   = max(1, (int)ceil($total / $perPage));
$page    = min(max(1, (int)($_GET['trang'] ?? 1)), $pages);
$shown   = array_slice($list, ($page - 1) * $perPage, $perPage);

// Sự kiện nổi bật: sự kiện sắp diễn ra gần nhất còn chỗ
$featured = null;
foreach ($events as $ev) {
    if ($ev['status'] !== 'past' && !$ev['full'] && (!$featured || $ev['date'] < $featured['date'])) $featured = $ev;
}