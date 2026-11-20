<?php
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];
$db = getDB();

$stUser = $db->prepare('SELECT ID, HoTen, Email, SoDienThoai FROM nguoidung WHERE ID = ?');
$stUser->execute([$userId]);
$taiKhoan = $stUser->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tài khoản của tôi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/pages/taikhoan/taikhoan.css?v=2">
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
                <input type="text" value="<?= $taiKhoan['HoTen'] ?>" readonly>
            </div>

            <div class="form-row">
                <label>Email</label>
                <input type="email" value="<?= $taiKhoan['Email'] ?>" readonly>
            </div>

            <div class="form-row">
                <label>Số điện thoại</label>
                <input type="text" value="<?= !empty($taiKhoan['SoDienThoai']) ? $taiKhoan['SoDienThoai'] : 'Chưa cập nhật' ?>" readonly>
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