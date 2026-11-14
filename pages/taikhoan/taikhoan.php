<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$db = getDB();

$stUser = $db->prepare('SELECT ID, HoTen, Email, SoDienThoai FROM nguoidung WHERE ID = ?');
$stUser->execute([$userId]);
$taiKhoan = $stUser->fetch(PDO::FETCH_ASSOC);

if (!$taiKhoan) {
    logoutUser();
    header('Location: ' . BASE_URL . '/pages/taikhoan/dangnhap.php');
    exit;
}

$stEvents = $db->prepare('
    SELECT
        dk.NgayDangKy,
        dk.TrangThai AS TrangThaiDangKy,
        dk.MaVe,
        dk.TrangThaiCheckIn,
        sk.TenSuKien,
        sk.ThoiGianBatDau
    FROM dangkysukien dk
    INNER JOIN sukien sk ON dk.ID_SuKien = sk.ID
    WHERE dk.ID_NguoiDung = ?
    ORDER BY dk.NgayDangKy DESC
');
$stEvents->execute([$userId]);
$events = $stEvents->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Tài khoản | Musicforlife';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/pages/taikhoan/taikhoan.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <?php
    require_once ROOT_PATH . '/components/top.php';
    require_once ROOT_PATH . '/components/header.php';
    ?>

    <main class="main-content">

        <h1 class="account-title">Tài khoản của tôi</h1>

        <!-- THÔNG TIN CÁ NHÂN -->
        <section class="account-info">
            <h2>Thông tin cá nhân</h2>

            <div class="form-row">
                <label>Họ và tên</label>
                <input type="text" value="<?= e($taiKhoan['HoTen']) ?>" readonly>
            </div>

            <div class="form-row">
                <label>Email</label>
                <input type="email" value="<?= e($taiKhoan['Email']) ?>" readonly>
            </div>

            <div class="form-row">
                <label>Số điện thoại</label>
                <input type="text" value="<?= !empty($taiKhoan['SoDienThoai']) ? e($taiKhoan['SoDienThoai']) : 'Chưa cập nhật' ?>" readonly>
            </div>

            <div class="form-button">
                <a href="<?= BASE_URL ?>/pages/taikhoan/capnhatthongtin.php">
                    <button id="update-info" type="button">Cập nhật thông tin</button>
                </a>
            </div>
        </section>
    </main>

    <?php require_once ROOT_PATH . '/components/footer.php'; ?>

    <script src="<?= BASE_URL ?>/assets/js/components.js"></script>
</body>
</html>