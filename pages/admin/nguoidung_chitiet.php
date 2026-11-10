<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../components/top.php';
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/footer.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$u = $conn->prepare("SELECT ID, HoTen, Email, VaiTro, TrangThai FROM nguoidung WHERE ID = ?");
$u->execute([$id]);
$user = $u->fetch();
if (!$user) { http_response_code(404); exit('Không tìm thấy tài khoản'); }

$hd = $conn->prepare("SELECT HanhDong, MoTa, DiaChiIP, ThoiGian FROM nhatky_hoatdong
                      WHERE ID_NguoiDung = ? ORDER BY ThoiGian DESC LIMIT 100");
$hd->execute([$id]);

$tc = $conn->prepare("SELECT DiaChiIP, Trang, PhuongThuc, ThoiGian FROM nhatky_truycap
                      WHERE ID_NguoiDung = ? ORDER BY ThoiGian DESC LIMIT 100");
$tc->execute([$id]);

$ips = $conn->prepare("SELECT DiaChiIP, COUNT(*) AS SoLan, MAX(ThoiGian) AS LanCuoi
                       FROM nhatky_truycap WHERE ID_NguoiDung = ?
                       GROUP BY DiaChiIP ORDER BY LanCuoi DESC");
$ips->execute([$id]);
?>
<!DOCTYPE html>
<html lang="vi"><head><meta charset="utf-8"><title>Chi tiết tài khoản</title></head>
<body>
<p><a href="nguoidung.php">Quay lại</a></p>
<main class="main-content">
    <h1><?= e($user['HoTen']) ?></h1>
    <p><?= e($user['Email']) ?> · Vai trò: <?= e($user['VaiTro']) ?> · Trạng thái: <?= e($user['TrangThai']) ?></p>

    <?php if (isset($_GET['ok'])): ?>
      <p style="color:green">Đã cập nhật trạng thái tài khoản.</p>
    <?php elseif (($_GET['loi'] ?? '') === 'tu_khoa'): ?>
      <p style="color:red">Bạn không thể tự khoá tài khoản của mình.</p>
    <?php elseif (($_GET['loi'] ?? '') === 'khoa_admin'): ?>
      <p style="color:red">Không thể khoá tài khoản admin.</p>
    <?php endif; ?>

    <?php if ($user['VaiTro'] !== 'admin' && (int)$user['ID'] !== (int)$_SESSION['user_id']): ?>
      <form method="post" action="nguoidung_khoa.php"
            onsubmit="return confirm('Xác nhận thay đổi trạng thái tài khoản này?');">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int)$user['ID'] ?>">
        <?php if ($user['TrangThai'] === 'khoa'): ?>
          <input type="hidden" name="hanh_dong" value="mo">
          <button type="submit">🔓 Mở khoá tài khoản</button>
        <?php else: ?>
          <input type="hidden" name="hanh_dong" value="khoa">
          <button type="submit">🔒 Khoá tài khoản</button>
        <?php endif; ?>
      </form>
    <?php endif; ?>

    <h2>Các địa chỉ IP đã dùng</h2>
    <table border="1" cellpadding="6">
      <tr><th>IP</th><th>Số lượt</th><th>Lần cuối</th></tr>
      <?php foreach ($ips as $r): ?>
      <tr><td><?= e($r['DiaChiIP']) ?></td><td><?= (int)$r['SoLan'] ?></td><td><?= e($r['LanCuoi']) ?></td></tr>
      <?php endforeach; ?>
      </table>
  </main>
</body>
</html>