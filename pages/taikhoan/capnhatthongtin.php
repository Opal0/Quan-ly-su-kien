<?php
// pages/taikhoan/capnhatthongtin.php
require_once __DIR__ . '/../../includes/auth.php'; // đã nạp config, session, e(), csrf, getDB()

// 1. Bắt buộc đăng nhập (tự chuyển về trang đăng nhập, tự kiểm tra tài khoản bị khóa)
requireLogin();
$userId = currentUser()['id'];
$db     = getDB();

$errors  = [];
$success = '';

// 2. Xử lý khi bấm "Lưu"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errors[] = 'Phiên làm việc không hợp lệ, vui lòng tải lại trang.';
    }

    $hoTen = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt   = trim($_POST['so_dien_thoai'] ?? '');

    // Validate
    if ($hoTen === '' || mb_strlen($hoTen) > 100) {
        $errors[] = 'Họ tên không được để trống và tối đa 100 ký tự.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if ($sdt !== '' && !preg_match('/^(0|\+84)[0-9]{9}$/', $sdt)) {
        $errors[] = 'Số điện thoại không hợp lệ (ví dụ: 0912345678).';
    }

    // Nếu tìm thấy Email từ user có id khác với user hiện tại thì báo lỗi
    if (!$errors) {
        $stmt = $db->prepare('SELECT ID FROM nguoidung WHERE Email = :email AND ID <> :id');
        $stmt->execute([':email' => $email, ':id' => $userId]);
        if ($stmt->fetch()) {
            $errors[] = 'Email này đã được tài khoản khác sử dụng.';
        }
    }

    // Lưu vào CSDL
    if (!$errors) {
        $stmt = $db->prepare(
            'UPDATE nguoidung SET HoTen = :hoten, Email = :email, SoDienThoai = :sdt WHERE ID = :id'
        );
        $ok = $stmt->execute([
            ':hoten' => $hoTen,
            ':email' => $email,
            ':sdt'   => $sdt === '' ? null : $sdt,
            ':id'    => $userId,
        ]);
        if ($ok) {
            $success = 'Cập nhật thông tin thành công!';
            // Đồng bộ session để header hiển thị tên/email mới
            $_SESSION['user_name']  = $hoTen;
            $_SESSION['user_email'] = $email;
        } else {
            $errors[] = 'Có lỗi khi lưu, vui lòng thử lại.';
        }
    }
}

// 3. Luôn đọc lại dữ liệu mới nhất từ CSDL để hiển thị
$stmt = $db->prepare('SELECT HoTen, Email, SoDienThoai FROM nguoidung WHERE ID = :id');
$stmt->execute([':id' => $userId]);
$thongTin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$thongTin) {
    die('Không tìm thấy tài khoản.');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thông tin tài khoản</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../pages/taikhoan/taikhoan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <?php require __DIR__ . '/../../components/top.php';
    require __DIR__ . '/../../components/header.php';?>

    <main class="main-content capnhatthongtin";>
        <h2>Thông tin tài khoản</h2>

        <?php if ($success): ?>
            <p style="color:green;"><?= e($success) ?></p>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
            <p style="color:red;"><?= e($err) ?></p>
        <?php endforeach; ?>

        <!-- Hiển thị thông tin hiện tại / mới nhất -->
        <section class="current-info">
            <h3>Thông tin hiện tại</h3>
            <ul>
                <li><b>Họ tên:</b> <?= e($thongTin['HoTen']) ?></li>
                <li><b>Email:</b> <?= e($thongTin['Email']) ?></li>
                <li><b>Số điện thoại:</b> <?= e($thongTin['SoDienThoai'] ?: 'Chưa cập nhật') ?></li>
            </ul>
        </section>

        <!-- Form nhập thông tin mới (điền sẵn giá trị hiện có) -->
        <section class="update-info">
            <h3>Cập nhật thông tin</h3>
            <form method="post" action="">
                <?= csrfField() ?>
                <p>
                    <label>Họ tên<br>
                    <input type="text" name="ho_ten" required maxlength="100"
                        value="<?= e($_POST['ho_ten'] ?? $thongTin['HoTen']) ?>"></label>
                </p>
                <p>
                    <label>Email<br>
                    <input type="email" name="email" required
                        value="<?= e($_POST['email'] ?? $thongTin['Email']) ?>"></label>
                </p>
                <p>
                    <label>Số điện thoại<br>
                    <input type="tel" name="so_dien_thoai"
                        value="<?= e($_POST['so_dien_thoai'] ?? $thongTin['SoDienThoai']) ?>"></label>
                </p>
                <button type="submit">Lưu thay đổi</button>
            </form>
        </section>
    </main>
    <?php require_once ROOT_PATH . '/components/footer.php'; ?>

    <script src="<?= BASE_URL ?>/assets/js/components.js"></script>
</body>
</html>