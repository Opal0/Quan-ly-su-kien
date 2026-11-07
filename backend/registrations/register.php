<?php
session_start();

require_once __DIR__ . '/../../config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/taikhoan/dangnhap.php');
    exit;
}

// Chỉ xử lý POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/sukien/sukien.php');
    exit;
}

// Kiểm tra CSRF
if (
    !isset($_POST['csrf'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf'])
) {
    die('Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.');
}

$id_nguoi_dung = (int) $_SESSION['user_id'];
$id_su_kien = (int) ($_POST['ID_SuKien'] ?? 0);

if ($id_su_kien <= 0) {
    die('Sự kiện không hợp lệ.');
}

try {
    $pdo->beginTransaction();

    // Khóa dòng sự kiện để tránh nhiều người đăng ký vượt số lượng
    $stmt = $pdo->prepare("
        SELECT ID, ThoiGianBatDau, SoLuongToiDa
        FROM SuKien
        WHERE ID = :id
        FOR UPDATE
    ");
    $stmt->execute([':id' => $id_su_kien]);
    $su_kien = $stmt->fetch();

    if (!$su_kien) {
        throw new Exception('Không tìm thấy sự kiện.');
    }

    // Không cho đăng ký khi sự kiện đã bắt đầu
    if (strtotime($su_kien['ThoiGianBatDau']) <= time()) {
        throw new Exception('Sự kiện đã bắt đầu hoặc đã kết thúc.');
    }

    // Kiểm tra người dùng đã từng đăng ký chưa
    $stmt = $pdo->prepare("
        SELECT ID, TrangThai
        FROM DangKySuKien
        WHERE ID_NguoiDung = :nguoi_dung
          AND ID_SuKien = :su_kien
        LIMIT 1
    ");
    $stmt->execute([
        ':nguoi_dung' => $id_nguoi_dung,
        ':su_kien' => $id_su_kien
    ]);

    $dang_ky_cu = $stmt->fetch();

    if ($dang_ky_cu && $dang_ky_cu['TrangThai'] === 'da_dang_ky') {
        throw new Exception('Bạn đã đăng ký sự kiện này rồi.');
    }

    // Đếm số người đã đăng ký thành công
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM DangKySuKien
        WHERE ID_SuKien = :su_kien
          AND TrangThai = 'da_dang_ky'
    ");
    $stmt->execute([':su_kien' => $id_su_kien]);
    $so_nguoi_da_dang_ky = (int) $stmt->fetchColumn();

    // Kiểm tra số lượng chỗ
    if (
        $su_kien['SoLuongToiDa'] !== null &&
        $so_nguoi_da_dang_ky >= (int) $su_kien['SoLuongToiDa']
    ) {
        throw new Exception('Sự kiện đã hết chỗ.');
    }

    // Tạo mã vé mới
    $ma_ve = bin2hex(random_bytes(16));

    if ($dang_ky_cu) {
        // Người dùng từng hủy, cho phép đăng ký lại
        $stmt = $pdo->prepare("
            UPDATE DangKySuKien
            SET NgayDangKy = NOW(),
                TrangThai = 'da_dang_ky',
                MaVe = :ma_ve,
                TrangThaiCheckIn = 'chua_checkin',
                ThoiGianCheckIn = NULL
            WHERE ID = :id
        ");

        $stmt->execute([
            ':ma_ve' => $ma_ve,
            ':id' => $dang_ky_cu['ID']
        ]);
    } else {
        // Đăng ký lần đầu
        $stmt = $pdo->prepare("
            INSERT INTO DangKySuKien
                (ID_NguoiDung, ID_SuKien, NgayDangKy,
                 TrangThai, MaVe, TrangThaiCheckIn)
            VALUES
                (:nguoi_dung, :su_kien, NOW(),
                 'da_dang_ky', :ma_ve, 'chua_checkin')
        ");

        $stmt->execute([
            ':nguoi_dung' => $id_nguoi_dung,
            ':su_kien' => $id_su_kien,
            ':ma_ve' => $ma_ve
        ]);
    }

    $pdo->commit();

    header('Location: ../../pages/taikhoan/taikhoan.php?registered=1');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Quay lại trang sự kiện và hiển thị lỗi
    header(
        'Location: ../../pages/sukien/sukien.php?error=' .
        urlencode($e->getMessage())
    );
    exit;
}