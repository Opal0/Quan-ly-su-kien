
<?php
require_once __DIR__ . '/../../includes/auth.php';

// Bắt buộc đăng nhập
requireLogin();

// Chỉ cho phép gửi yêu cầu bằng POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/taikhoan/ve_cua_toi.php');
    exit;
}

// Kiểm tra CSRF
if (!csrfValid()) {
    die('Yêu cầu không hợp lệ. Vui lòng tải lại trang.');
}

$id_nguoi_dung = (int) $_SESSION['user_id'];
$id_dang_ky = (int) ($_POST['ID_DangKy'] ?? 0);

if ($id_dang_ky <= 0) {
    header('Location: ' . BASE_URL . '/pages/taikhoan/ve_cua_toi.php?error=' . urlencode('Mã đăng ký không hợp lệ.'));
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();

    // Lấy thông tin đăng ký và khóa dòng đăng ký
    $stmt = $db->prepare("
        SELECT
            d.ID,
            d.TrangThai,
            d.TrangThaiCheckIn,
            s.ThoiGianBatDau,
            s.TenSuKien
        FROM dangkysukien d
        JOIN sukien s ON d.ID_SuKien = s.ID
        WHERE d.ID = ?
          AND d.ID_NguoiDung = ?
        FOR UPDATE
    ");

    $stmt->execute([
        $id_dang_ky,
        $id_nguoi_dung
    ]);

    $dang_ky = $stmt->fetch();

    if (!$dang_ky) {
        throw new Exception('Không tìm thấy đăng ký.');
    }

    if ($dang_ky['TrangThai'] !== 'da_dang_ky') {
        throw new Exception('Đăng ký này đã được hủy trước đó.');
    }

    if ($dang_ky['TrangThaiCheckIn'] === 'da_checkin') {
        throw new Exception('Bạn đã check-in, không thể hủy đăng ký.');
    }

    if (strtotime($dang_ky['ThoiGianBatDau']) <= time()) {
        throw new Exception('Sự kiện đã bắt đầu, không thể hủy.');
    }

    // Cập nhật trạng thái đăng ký
    $stmt = $db->prepare("
        UPDATE dangkysukien
        SET TrangThai = 'da_huy'
        WHERE ID = ?
          AND ID_NguoiDung = ?
    ");

    $stmt->execute([
        $id_dang_ky,
        $id_nguoi_dung
    ]);

    $db->commit();
    ghiHoatDong(
        $db,
        (int)$_SESSION['user_id'],
        'huy_ve',
        "Hủy đặt vé cho sự kiện #{$dang_ky['TenSuKien']}"
    );
    header('Location: ' . BASE_URL . '/pages/taikhoan/ve_cua_toi.php?cancelled=1');
    exit;

} catch (Exception $e) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    header(
        'Location: ' . BASE_URL . '/pages/taikhoan/ve_cua_toi.php?error=' .
        urlencode($e->getMessage())
    );
    exit;
}
