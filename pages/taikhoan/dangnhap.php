<?php
require_once __DIR__ . '/../../includes/auth.php';

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';

if (isLoggedIn()) { header('Location: ' . safeRedirect($redirect)); exit; }

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $errors[] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $mk    = $_POST['matkhau'] ?? '';

        $st = getDB()->prepare('SELECT ID, HoTen, Email, MatKhau, VaiTro, TrangThai FROM nguoidung WHERE Email = ?');
        $st->execute([$email]);
        $u = $st->fetch();

        if (!$u || !password_verify($mk, $u['MatKhau'])) {
            $errors[] = 'Email hoặc mật khẩu không đúng.';
        } elseif ($u['TrangThai'] !== 'hoat_dong') {
            $errors[] = 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.';
        } else {
            if (password_needs_rehash($u['MatKhau'], PASSWORD_DEFAULT)) {
                getDB()->prepare('UPDATE nguoidung SET MatKhau = ? WHERE ID = ?')
                       ->execute([password_hash($mk, PASSWORD_DEFAULT), $u['ID']]);
            }
            loginUser($u);
            // Admin vao trang quan ly neu khong co trang dich cu the
            $default = $u['VaiTro'] === 'admin' ? BASE_URL . '/pages/admin/duyetdoivaitro.php' : BASE_URL . '/pages/sukien/sukien.php';
            header('Location: ' . ($redirect !== '' ? safeRedirect($redirect) : $default));
            exit;
        }
    }
}

$pageTitle = 'Đăng nhập - Music4Life';
require __DIR__ . '/../../components/top.php';
require __DIR__ . '/../../components/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css"> 
<link rel="stylesheet" href="<?= BASE_URL ?>/pages/taikhoan/taikhoan.css">
<link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<main class="main-content">
    <form class="auth-card" method="post">
        <h2 class="auth-title">Đăng nhập</h2>
        <?php if (isset($_GET['registered'])): ?><p class="alert success">Đăng ký thành công, hãy đăng nhập.</p><?php endif; ?>
        <?php if (isset($_GET['reset'])): ?><p class="alert success">Đặt lại mật khẩu thành công, hãy đăng nhập.</p><?php endif; ?>
        <?php if (isset($_GET['loggedout'])): ?><p class="alert success">Bạn đã đăng xuất.</p><?php endif; ?>
        <?php foreach ($errors as $er): ?><p class="alert error"><?= e($er) ?></p><?php endforeach; ?>
        <?= csrfField() ?>
        <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

        <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        </div>
        <div class="form-row">
            <label for="matkhau">Mật khẩu</label>
            <input type="password" id="matkhau" name="matkhau" required>
        </div>

        <p class="form-link" style="text-align:right;margin:0"><a href="quenmatkhau.php">Quên mật khẩu?</a></p>
        <div class="form-button">
            <button class="btn" id="login-button" type="submit">Đăng nhập</button>
        </div>
        <p class="form-link">Chưa có tài khoản? <a href="dangky.php">Đăng ký</a></p>
    </form>
</main>
<?php require __DIR__ . '/../../components/footer.php'; ?>