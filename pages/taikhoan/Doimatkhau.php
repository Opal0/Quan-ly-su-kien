<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$err = '';
$ok  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $err = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } else {
        $cu  = $_POST['matkhau_cu'] ?? '';
        $mk  = $_POST['matkhau_moi'] ?? '';
        $mk2 = $_POST['xacnhan'] ?? '';
        $db  = getDB();

        $st = $db->prepare('SELECT MatKhau FROM nguoidung WHERE ID = ?');
        $st->execute([currentUser()['id']]);
        $hash = $st->fetchColumn();

        if (!$hash || !password_verify($cu, $hash)) {
            $err = 'Mật khẩu hiện tại không đúng.';
        } elseif (strlen($mk) < 6) {
            $err = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } elseif ($mk !== $mk2) {
            $err = 'Mật khẩu xác nhận không khớp.';
        } elseif ($cu === $mk) {
            $err = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
        } else {
            $db->prepare('UPDATE nguoidung SET MatKhau = ? WHERE ID = ?')
               ->execute([password_hash($mk, PASSWORD_DEFAULT), currentUser()['id']]);
            session_regenerate_id(true);
            $ok = true;
            ghiHoatDong(
                    $conn, $id,
                    'doi_mat_khau' ,
                    'Thực hiện bởi user ' . SESSION['user_id']
                );
        }
    }
}

$pageTitle = 'Đổi mật khẩu - Music4Life';
require __DIR__ . '/../../components/top.php';
require __DIR__ . '/../../components/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/pages/taikhoan/taikhoan.css">
<link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<main class="main-content">
    <form class="auth-card" method="post" autocomplete="off">
        <h2 class="auth-title">Đổi mật khẩu</h2>
        <?php if ($ok): ?><p class="alert success">Đổi mật khẩu thành công.</p><?php endif; ?>
        <?php if ($err): ?><p class="alert error"><?= e($err) ?></p><?php endif; ?>
        <?= csrfField() ?>
        <div class="form-row">
            <label for="matkhau_cu">Mật khẩu hiện tại</label>
            <input type="password" id="matkhau_cu" name="matkhau_cu" required>
        </div>
        <div class="form-row">
            <label for="matkhau_moi">Mật khẩu mới</label>
            <input type="password" id="matkhau_moi" name="matkhau_moi" required minlength="6">
        </div>
        <div class="form-row">
            <label for="xacnhan">Xác nhận mật khẩu mới</label>
            <input type="password" id="xacnhan" name="xacnhan" required minlength="6">
        </div>
        <div class="form-button">
            <button id="update-password" type="submit">Lưu mật khẩu</button>
        </div>
    </form>
</main>
<script src="<?= BASE_URL ?>/assets/js/components.js"></script>
<?php require __DIR__ . '/../../components/footer.php'; ?>