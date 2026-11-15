<?php
/**
 * Xử lý dữ liệu cho trang Sự kiện: truy vấn CSDL, lọc, phân trang, đăng ký / hủy đăng ký.
 * Được nạp từ sukien.php (sau session_start và tạo CSRF token).
 *
 * CSDL music4life: sukien, danhmuc, dangkysukien, nguoidung.
 * Các khóa của mảng $events (title, category, date, time, ...) giữ nguyên
 * như bản cũ để file view sukien.php không phải sửa phần hiển thị.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once ROOT_PATH . '/config/database.php';   // cung cấp getDB()

$pdo = getDB();

/* ---------- Dữ liệu sự kiện ---------- */
$sql = "SELECT s.ID                                   AS id,
               s.TenSuKien                            AS title,
               d.TenDanhMuc                           AS category,
               DATE(s.ThoiGianBatDau)                 AS `date`,
               DATE_FORMAT(s.ThoiGianBatDau, '%H:%i') AS `time`,
               DATE(s.ThoiGianKetThuc)                AS end_date,
               DATE_FORMAT(s.ThoiGianKetThuc, '%H:%i') AS end_time,
               s.DiaDiem                              AS location,
               s.MoTa                                 AS `desc`,
               s.AnhSuKien                            AS image,
               s.SoLuongToiDa                         AS capacity,
               s.TrangThai                            AS db_status,
               COALESCE(SUM(COALESCE(r.SoLuong, 1)), 0) AS registered
        FROM sukien s
        JOIN danhmuc d ON d.ID = s.ID_DanhMuc
        LEFT JOIN dangkysukien r ON r.ID_SuKien = s.ID AND r.TrangThai = 'da_dang_ky'
        WHERE s.TrangThai <> 'da_huy'
          AND d.TrangThai = 'hoat_dong'
        GROUP BY s.ID
        ORDER BY s.ThoiGianBatDau";
$dbError = false;
try {
    $events = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    error_log('sukien_controller.php: ' . $ex->getMessage());
    $events = [];
    $dbError = true;
}

/* ---------- Hàm hỗ trợ ---------- */
if (!function_exists('e')) { function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('sk_url')) { function sk_url(array $over = []) {
    $q = array_merge($_GET, $over);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($q);
} }

$today = date('Y-m-d');
foreach ($events as &$ev) {
    $ev['id']         = (int)$ev['id'];
    $ev['capacity']   = (int)$ev['capacity'];
    $ev['registered'] = (int)$ev['registered'];
    $ev['desc']       = (string)$ev['desc'];
    if ($ev['db_status'] === 'da_ket_thuc' || $ev['end_date'] < $today) $ev['status'] = 'past';
    elseif ($ev['date'] <= $today)                                       $ev['status'] = 'today';
    else                                                                 $ev['status'] = 'upcoming';
    $ev['full'] = $ev['capacity'] > 0
    && $ev['registered'] >= $ev['capacity'];
}
unset($ev);

/* ---------- Người dùng hiện tại + các sự kiện họ đã đăng ký ---------- */
$isLoggedIn = isLoggedIn();
$userId     = $isLoggedIn ? (int)($_SESSION['user_id'] ?? 0) : 0;
$myRegs     = [];   // [id sự kiện => mã vé]
if ($userId > 0) {
    try {
        $st = $pdo->prepare("SELECT ID_SuKien, MaVe FROM dangkysukien WHERE ID_NguoiDung = ? AND TrangThai = 'da_dang_ky'");
        $st->execute([$userId]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $myRegs[(int)$r['ID_SuKien']] = $r['MaVe'];
    } catch (PDOException $ex) {
        error_log('sukien_controller.php: ' . $ex->getMessage());
    }
}
foreach ($events as &$ev) { $ev['registered_by_me'] = isset($myRegs[$ev['id']]); }
unset($ev);

/* ---------- Xử lý đăng ký / hủy đăng ký (POST) ---------- */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $back   = strtok($_SERVER['REQUEST_URI'], '#') . '#danh-sach';
    // Đăng ký mới phải đi qua trang Mua vé và backend xử lý chung.
if ($action === 'register') {
    header(
        'Location: ' . BASE_URL .
        '/pages/muave/muave.php?event_id=' . $id
    );
    exit;
}

    $target = null;
    foreach ($events as $ev) { if ($ev['id'] === $id) { $target = $ev; break; } }

    $err = null;
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) $err = 'Phiên làm việc đã hết hạn. Hãy tải lại trang và thử lại.';
    elseif ($userId <= 0)                                    $err = 'Bạn cần đăng nhập để đăng ký hoặc hủy đăng ký sự kiện.';
    elseif (!$target)                                        $err = 'Không tìm thấy sự kiện.';
    elseif ($target['status'] === 'past')                    $err = 'Sự kiện này đã kết thúc.';

    if ($err) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => $err];
        header('Location: ' . $back);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Khóa dòng sự kiện để tránh nhiều người cùng đăng ký vượt số lượng
        $row = $pdo->prepare("SELECT SoLuongToiDa FROM sukien WHERE ID = ? AND TrangThai <> 'da_huy' FOR UPDATE");
        $row->execute([$id]);
        $cap = $row->fetchColumn();
        if ($cap === false) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Không tìm thấy sự kiện.'];
            header('Location: ' . $back);
            exit;
        }
        $cap = (int)$cap;

        // Bản ghi đăng ký (nếu có) của người dùng này cho sự kiện này
        $ex = $pdo->prepare("SELECT ID, TrangThai FROM dangkysukien WHERE ID_NguoiDung = ? AND ID_SuKien = ? FOR UPDATE");
        $ex->execute([$userId, $id]);
        $existing = $ex->fetch(PDO::FETCH_ASSOC);

        if ($action === 'cancel') {
            if (!$existing || $existing['TrangThai'] !== 'da_dang_ky') {
                $pdo->rollBack();
                $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Bạn chưa đăng ký sự kiện này.'];
                header('Location: ' . $back);
                exit;
            }
            $pdo->prepare("UPDATE dangkysukien SET TrangThai = 'da_huy' WHERE ID = ?")
                ->execute([$existing['ID']]);
            $pdo->commit();
            $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Đã hủy đăng ký "' . $target['title'] . '".'];
            header('Location: ' . $back);
            exit;
        }

        // ----- action = register -----
        if ($existing && $existing['TrangThai'] === 'da_dang_ky') {
            $pdo->rollBack();
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Bạn đã đăng ký sự kiện này rồi.'];
            header('Location: ' . $back);
            exit;
        }

        $cnt = $pdo->prepare("SELECT COUNT(*) FROM dangkysukien WHERE ID_SuKien = ? AND TrangThai = 'da_dang_ky'");
        $cnt->execute([$id]);
        if ((int)$cnt->fetchColumn() >= $cap) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Sự kiện đã đủ số lượng đăng ký.'];
            header('Location: ' . $back);
            exit;
        }

        $maVe = 'VE' . strtoupper(bin2hex(random_bytes(5)));
        if ($existing) {
            // Đã từng hủy -> đăng ký lại: dùng lại dòng cũ (do ràng buộc UNIQUE người dùng + sự kiện)
            $pdo->prepare("UPDATE dangkysukien
                           SET TrangThai = 'da_dang_ky', NgayDangKy = NOW(), MaVe = ?,
                               TrangThaiCheckIn = 'chua_checkin', ThoiGianCheckIn = NULL
                           WHERE ID = ?")
                ->execute([$maVe, $existing['ID']]);
        } else {
            $pdo->prepare("INSERT INTO dangkysukien (ID_NguoiDung, ID_SuKien, MaVe) VALUES (?, ?, ?)")
                ->execute([$userId, $id, $maVe]);
        }
        $pdo->commit();

        $_SESSION['flash'] = ['type' => 'ok', 'msg' => 'Đã đăng ký "' . $target['title'] . '". Mã vé của bạn: ' . $maVe];
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('sukien_controller.php: ' . $ex->getMessage());
        $_SESSION['flash'] = ['type' => 'error', 'msg' => $ex->getCode() === '23000'
            ? 'Bạn đã đăng ký sự kiện này rồi.'
            : 'Không thể lưu đăng ký lúc này. Hãy thử lại sau.'];
    }
    header('Location: ' . $back);
    exit;
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

$perPage = 6;
$total   = count($list);
$pages   = max(1, (int)ceil($total / $perPage));
$page    = min(max(1, (int)($_GET['trang'] ?? 1)), $pages);
$shown   = array_slice($list, ($page - 1) * $perPage, $perPage);

// Sự kiện nổi bật: sự kiện sắp diễn ra gần nhất còn chỗ
$featured = null;
foreach ($events as $ev) {
    if ($ev['status'] !== 'past' && !$ev['full'] && (!$featured || $ev['date'] < $featured['date'])) $featured = $ev;
}
$thang = ['', 'Th1','Th2','Th3','Th4','Th5','Th6','Th7','Th8','Th9','Th10','Th11','Th12'];