<?php
require_once __DIR__ . '/../includes/auth.php';
$role = currentRole();          // guest | user | organizer | admin
$user = currentUser();
$B = BASE_URL;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Music4Life') ?></title>
    <link rel="stylesheet" href="<?= $B ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= $B ?>/assets/css/cyber-ocean.css">
    <script src="<?= $B ?>/assets/js/main.js" defer></script>
    <script src="<?= $B ?>/assets/js/cyber-ocean.js" defer></script>
</head>
<body>
<header class="site-header">
    <nav class="page-header__navbar" id="mainNav">
    <a class="page-header__logo-link" href="<?= BASE_URL ?>/pages/sukien/sukien.php">
        <img class="page-header__logo-link img" src="../../assets/images/Music%20for%20life.png" alt="Music for life">
    </a>
        <?php if ($role === 'user' || $role === 'organizer'): ?>
            <a class="page-header__link" href="<?= $B ?>/pages/sukien/sukien.php">Sự kiện</a>
            <a class="page-header__link" href="<?= $B ?>/pages/muave/muave.php">Mua vé</a>
            <a class="page-header__link" href="<?= $B ?>/pages/taikhoan/ve_cua_toi.php">Vé của tôi</a>
            <a class="page-header__link" href="<?= $B ?>/pages/taikhoan/taikhoan.php">Tài khoản</a>

        <?php endif; ?>
        <?php if ($role === 'guest'): ?>
            <a class="page-header__link" href="<?= $B ?>/pages/sukien/sukien.php">Sự kiện</a>
        <?php endif; ?>
        <?php if ($role === 'organizer'): ?>
            <a class="page-header__link" href="<?= $B ?>/pages/sukien/Qlsukien.php">Sự kiện của tôi</a>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <a class="page-header__link" href="<?= $B ?>/pages/admin/duyetsukien.php">Quản lý sự kiện</a>
            <a class="page-header__link" href="<?= $B ?>/pages/admin/nguoidung.php">Quản lý người dùng</a>
            <a class="page-header__link" href="<?= $B ?>/pages/admin/duyetdoivaitro.php">Duyệt người tổ chức</a>
        <?php endif; ?>
    </nav>
</header>
