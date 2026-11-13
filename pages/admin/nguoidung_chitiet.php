<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
$u = $conn->prepare("SELECT ID, HoTen, Email, VaiTro, TrangThai FROM nguoidung WHERE ID = ?");
$u->execute([$id]);
$tk = $u->fetch();
if (!$tk) { http_response_code(404); exit('Không tìm thấy tài khoản'); }

$hd = $conn->prepare("SELECT HanhDong, MoTa, DiaChiIP, ThoiGian FROM nhatky_hoatdong
                      WHERE ID_NguoiDung = ? ORDER BY ThoiGian DESC LIMIT 100");
$hd->execute([$id]);
$dsHoatDong = $hd->fetchAll();

$tc = $conn->prepare("SELECT DiaChiIP, Trang, PhuongThuc, ThoiGian FROM nhatky_truycap
                      WHERE ID_NguoiDung = ? ORDER BY ThoiGian DESC LIMIT 100");
$tc->execute([$id]);
$dsTruyCap = $tc->fetchAll();

$ips = $conn->prepare("SELECT DiaChiIP, COUNT(*) AS SoLan, MAX(ThoiGian) AS LanCuoi
                       FROM nhatky_truycap WHERE ID_NguoiDung = ?
                       GROUP BY DiaChiIP ORDER BY LanCuoi DESC");
$ips->execute([$id]);
$dsIP = $ips->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chi tiết tài khoản</title>
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../pages/admin/admin.css">
</head>
<body>
<?php require_once __DIR__ . '/../../components/top.php'; ?>
<?php require_once __DIR__ . '/../../components/header.php'; ?>

<main class="admin-page">
  <a class="back-link" href="nguoidung.php">← Quay lại</a>

  <h1><?= e($tk['HoTen']) ?></h1>
  <p class="page-sub">
    <?= e($tk['Email']) ?> ·
    Vai trò: <span class="badge badge-<?= e($tk['VaiTro']) ?>"><?= e($tk['VaiTro']) ?></span> ·
    Trạng thái: <span class="badge badge-<?= e($tk['TrangThai']) ?>"><?= e($tk['TrangThai']) ?></span>
  </p>

  <?php if (isset($_GET['ok'])): ?>
    <p class="alert alert-success">Đã cập nhật trạng thái tài khoản.</p>
  <?php elseif (($_GET['loi'] ?? '') === 'tu_khoa'): ?>
    <p class="alert alert-error">Bạn không thể tự khoá tài khoản của mình.</p>
  <?php elseif (($_GET['loi'] ?? '') === 'khoa_admin'): ?>
    <p class="alert alert-error">Không thể khoá tài khoản admin.</p>
  <?php endif; ?>

  <?php if ($tk['VaiTro'] !== 'admin' && (int)$tk['ID'] !== (int)$_SESSION['user_id']): ?>
    <form method="post" action="nguoidung_khoa.php"
          onsubmit="return confirm('Xác nhận thay đổi trạng thái tài khoản này?');">
      <?= csrfField() ?>
      <input type="hidden" name="id" value="<?= (int)$tk['ID'] ?>">
      <?php if ($tk['TrangThai'] === 'khoa'): ?>
        <input type="hidden" name="hanh_dong" value="mo">
        <button type="submit" class="btn btn-success">🔓 Mở khoá tài khoản</button>
      <?php else: ?>
        <input type="hidden" name="hanh_dong" value="khoa">
        <button type="submit" class="btn btn-danger">🔒 Khoá tài khoản</button>
      <?php endif; ?>
    </form>
  <?php endif; ?>

  <h2>Các địa chỉ IP đã dùng</h2>
  <div class="table-wrap">
    <table class="table-stack">
      <tr><th>IP</th><th>Số lượt</th><th>Lần cuối</th></tr>
      <?php foreach ($dsIP as $r): ?>
      <tr>
        <td data-label="IP"><?= e($r['DiaChiIP']) ?></td>
        <td data-label="Số lượt"><?= (int)$r['SoLan'] ?></td>
        <td data-label="Lần cuối"><?= e($r['LanCuoi']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <h2>Nhật ký hoạt động (100 gần nhất)</h2>
  <div class="table-wrap">
    <table class="table-stack">
      <tr><th>Thời gian</th><th>Hành động</th><th>Mô tả</th><th>IP</th></tr>
      <?php foreach ($dsHoatDong as $r): ?>
      <tr>
        <td data-label="Thời gian"><?= e($r['ThoiGian']) ?></td>
        <td data-label="Hành động"><?= e($r['HanhDong']) ?></td>
        <td data-label="Mô tả"><?= e($r['MoTa']) ?></td>
        <td data-label="IP"><?= e($r['DiaChiIP']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <h2>Lịch sử truy cập (100 gần nhất)</h2>
  <div class="table-wrap">
    <table class="table-stack">
      <tr><th>Thời gian</th><th>Trang</th><th>Method</th><th>IP</th></tr>
      <?php foreach ($dsTruyCap as $r): ?>
      <tr>
        <td data-label="Thời gian"><?= e($r['ThoiGian']) ?></td>
        <td data-label="Trang"><?= e($r['Trang']) ?></td>
        <td data-label="Method"><?= e($r['PhuongThuc']) ?></td>
        <td data-label="IP"><?= e($r['DiaChiIP']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</main>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
<script src="../../assets/js/components.js"></script>
</body>
</html>