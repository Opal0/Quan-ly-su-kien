<?php
session_start();

require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pages/taikhoan/dangnhap.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../pages/taikhoan/taikhoan.php');
    exit;
}

if (
    !isset($_POST['csrf'], $_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf'])
) {
    die('Yêu cầu không hợp lệ.');
}

$id_nguoi_dung = (int) $_SESSION['user_id'];
$id_dang_ky = (int) ($_POST['ID_DangKy'] ?? 0);

try {
    $stmt = $pdo->prepare("
        SELECT d.ID, d.TrangThai, d.TrangThaiCheckIn,
               s.ThoiGianBatDau
        FROM DangKySuKien d
        JOIN SuKien s ON d.ID_SuKien = s.ID
        WHERE d.ID = :id_dang_ky
          AND d.ID_NguoiDung = :id_nguoi_dung
        LIMIT 1
    ");

    $stmt->execute([
        ':id_dang_ky' => $id_dang_ky,
        ':id_nguoi_dung' => $id_nguoi_dung
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

    $stmt = $pdo->prepare("
        UPDATE DangKySuKien
        SET TrangThai = 'da_huy'
        WHERE ID = :id
          AND ID_NguoiDung = :nguoi_dung
    ");

    $stmt->execute([
        ':id' => $id_dang_ky,
        ':nguoi_dung' => $id_nguoi_dung
    ]);

    header('Location: ../../pages/taikhoan/taikhoan.php?cancelled=1');
    exit;

} catch (Exception $e) {
    header(
        'Location: ../../pages/taikhoan/taikhoan.php?error=' .
        urlencode($e->getMessage())
    );
    exit;
}