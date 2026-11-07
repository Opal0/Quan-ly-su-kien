<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sự kiện</title>

    <link rel="stylesheet" href="sukien.css">
    <link rel="stylesheet" href="../../assets/css/style.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <?php
    require_once __DIR__ . '/../../config/app.php';
    require_once ROOT_PATH . '/config/database.php';
    ?>
    <?php include __DIR__ . '/../../components/top.html'; ?>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <!-- Nội dung trang sự kiện -->
    <main class="main-section">

    </main>

    <?php include ROOT_PATH . '/components/footer.php'; ?>

</body>
</html>
