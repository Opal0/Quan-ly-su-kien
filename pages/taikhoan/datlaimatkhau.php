<?php
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) { header('Location: ' . BASE_URL . '/sukien.php'); exit; }

$email = $_SESSION['reset_email'] ?? '';
if ($email === '') { header('Location: quenmatkhau.php'); exit; }

$err = '';
const MAX_SAI = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $err = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
    } else {
        $code = trim($_POST['code'] ?? '');
        $mk   = $_POST['matkhau'] ?? '';
        $mk2  = $_POST['xacnhan'] ?? '';

        if (!preg_match('/^[0-9]{6}$/', $code)) {
            $err = 'Mã xác nhận gồm 6 chữ số.';
        } elseif (strlen($mk) < 6) {
            $err = 'Mật khẩu phải có ít nhất 6 ký tự.';
        } elseif ($mk !== $mk2) {
            $err = 'Mật khẩu xác nhận không khớp.';
        } else {
            $db = getDB();
            $st = $db->prepare(
                'SELECT d.ID, d.MaXacNhan, d.SoLanSai, d.ID_NguoiDung
                 FROM datlai_matkhau d JOIN nguoidung n ON n.ID = d.ID_NguoiDung
                 WHERE n.Email = ? AND n.TrangThai = "hoat_dong"
                   AND d.DaDung = 0 AND d.HetHan > NOW()
                 ORDER BY d.ID DESC LIMIT 1');
            $st->execute([$email]);
            $r = $st->fetch();

            if (!$r || $r['SoLanSai'] >= MAX_SAI) {
                $err = 'Mã không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu mã mới.';
            } elseif (!password_verify($code, $r['MaXacNhan'])) {
                $db->prepare('UPDATE datlai_matkhau SET SoLanSai = SoLanSai + 1 WHERE ID = ?')->execute([$r['ID']]);
                $err = 'Mã xác nhận không đúng.';
            } else {
                $db->beginTransaction();
                $db->prepare('UPDATE nguoidung SET MatKhau = ? WHERE ID = ?')
                   ->execute([password_hash($mk, PASSWORD_DEFAULT), $r['ID_NguoiDung']]);
                $db->prepare('UPDATE datlai_matkhau SET DaDung = 1 WHERE ID_NguoiDung = ?')
                   ->execute([$r['ID_NguoiDung']]);
                $db->commit();

                unset($_SESSION['reset_email']);
                header('Location: dangnhap.php?reset=1');
                ghiHoatDong(
                    $conn, $id,
                    'dat_lai_mat_khau' ,
                    'Thực hiện bởi user ' . SESSION['user_id']
                );
                exit;
            }
        }
    }
}

$pageTitle = 'Đặt lại mật khẩu - Music4Life';
require __DIR__ . '/../../components/top.php';
require __DIR__ . '/../../components/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/pages/taikhoan/taikhoan.css">
<link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<main class="main-content">
    <form class="auth-card" method="post" autocomplete="off">
        <h2 class="auth-title">Đặt lại mật khẩu</h2>
        <p>Nếu email <strong><?= e($email) ?></strong> đã đăng ký, mã xác nhận đã được gửi tới hộp thư.</p>
        <?php if ($err): ?><p class="alert error"><?= e($err) ?></p><?php endif; ?>
        <?= csrfField() ?>
        <div class="form-row">
            <label for="code">Mã xác nhận (6 chữ số)</label>
            <input type="text" id="code" name="code" inputmode="numeric" maxlength="6" required>
        </div>
        <div class="form-row">
            <label for="matkhau">Mật khẩu mới</label>
            <input type="password" id="matkhau" name="matkhau" required minlength="6">
        </div>
        <div class="form-row">
            <label for="xacnhan">Xác nhận mật khẩu mới</label>
            <input type="password" id="xacnhan" name="xacnhan" required minlength="6">
        </div>
        <div class="form-button">
            <button class="btn" type="submit">Đổi mật khẩu</button>
        </div>
        <p class="form-link"><a href="quenmatkhau.php">Gửi lại mã</a> · <a href="dangnhap.php">Đăng nhập</a></p>
    </form>
</main>
<script src="<?= BASE_URL ?>/assets/js/components.js"></script>
<?php require __DIR__ . '/../../components/footer.php'; ?>