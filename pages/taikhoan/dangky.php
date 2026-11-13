<?php
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$errors = [];
$old = ['hoten' => '', 'email' => '', 'sdt' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errors[] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } else {
        $hoten = trim($_POST['hoten'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $sdt   = trim($_POST['sdt'] ?? '');
        $mk    = $_POST['matkhau'] ?? '';
        $mk2   = $_POST['xacnhan'] ?? '';
        $old   = ['hoten' => $hoten, 'email' => $email, 'sdt' => $sdt];

        if ($hoten === '' || mb_strlen($hoten) > 100)               $errors[] = 'Họ tên không hợp lệ (tối đa 100 ký tự).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) $errors[] = 'Email không hợp lệ.';
        if ($sdt !== '' && !preg_match('/^[0-9+\s\-]{9,20}$/', $sdt)) $errors[] = 'Số điện thoại không hợp lệ.';
        if (strlen($mk) < 6)                                         $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
        if ($mk !== $mk2)                                            $errors[] = 'Mật khẩu xác nhận không khớp.';

        if (!$errors) {
            $db = getDB();
            $st = $db->prepare('SELECT 1 FROM nguoidung WHERE Email = ?');
            $st->execute([$email]);
            if ($st->fetch()) {
                $errors[] = 'Email này đã được đăng ký.';
            } else {
                // VaiTro luon la 'user' khi tu dang ky
                $ins = $db->prepare('INSERT INTO nguoidung (HoTen, Email, MatKhau, SoDienThoai, VaiTro)
                                     VALUES (?, ?, ?, ?, "user")');
                $ins->execute([$hoten, $email, password_hash($mk, PASSWORD_DEFAULT), $sdt !== '' ? $sdt : null]);
                header('Location: dangnhap.php?registered=1');
                exit;
            }
        }
    }
}

$pageTitle = 'Đăng ký - Music4Life';
require __DIR__ . '/../../components/top.php';
require __DIR__ . '/../../components/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/pages/taikhoan/taikhoan.css">
<link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<main class="main-content">
    <form class="auth-card" method="post" novalidate>
        <h2 class="auth-title">Đăng ký tài khoản</h2>
        <?php foreach ($errors as $er): ?><p class="alert error"><?= e($er) ?></p><?php endforeach; ?>
        <?= csrfField() ?>

        <div class="form-row">
            <label for="hoten">Họ tên</label>
            <input type="text" id="hoten" name="hoten" value="<?= e($old['hoten']) ?>" required maxlength="100">
        </div>
        <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required maxlength="150">
        </div>
        <div class="form-row">
            <label for="sdt">Số điện thoại (không bắt buộc)</label>
            <input type="text" id="sdt" name="sdt" value="<?= e($old['sdt']) ?>" maxlength="20">
        </div>
        <div class="form-row">
            <label for="matkhau">Mật khẩu</label>
            <input type="password" id="matkhau" name="matkhau" required minlength="6">
        </div>
        <div class="form-row">
            <label for="xacnhan">Xác nhận mật khẩu</label>
            <input type="password" id="xacnhan" name="xacnhan" required minlength="6">
        </div>

        <div class="form-button">
            <button class="btn" id="register-button" type="submit">Đăng ký</button>
        </div>
        <p class="form-link">Đã có tài khoản? <a href="dangnhap.php">Đăng nhập</a></p>
    </form>
</main>
<?php require_once ROOT_PATH . '/components/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/components.js"></script>
