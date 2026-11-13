<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$tab  = ($_GET['tab'] ?? 'taikhoan') === 'truycap' ? 'truycap' : 'taikhoan';
$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 20;
$off  = ($page - 1) * $per;
$like = '%' . $q . '%';

if ($tab === 'taikhoan') {
    $cnt = $conn->prepare("SELECT COUNT(*) FROM nguoidung WHERE HoTen LIKE ? OR Email LIKE ?");
    $cnt->execute([$like, $like]);
    $total = (int)$cnt->fetchColumn();

    $st = $conn->prepare("
        SELECT n.ID, n.HoTen, n.Email, n.VaiTro, n.TrangThai,
               (SELECT MAX(ThoiGian) FROM nhatky_truycap t WHERE t.ID_NguoiDung = n.ID) AS LanCuoi,
               (SELECT DiaChiIP FROM nhatky_truycap t WHERE t.ID_NguoiDung = n.ID
                 ORDER BY ThoiGian DESC LIMIT 1) AS IPCuoi
        FROM nguoidung n
        WHERE n.HoTen LIKE ? OR n.Email LIKE ?
        ORDER BY n.ID DESC LIMIT $per OFFSET $off");
    $st->execute([$like, $like]);
} else {
    $cnt = $conn->prepare("SELECT COUNT(*) FROM nhatky_truycap WHERE DiaChiIP LIKE ? OR Trang LIKE ?");
    $cnt->execute([$like, $like]);
    $total = (int)$cnt->fetchColumn();

    $st = $conn->prepare("
        SELECT t.*, n.HoTen
        FROM nhatky_truycap t LEFT JOIN nguoidung n ON n.ID = t.ID_NguoiDung
        WHERE t.DiaChiIP LIKE ? OR t.Trang LIKE ?
        ORDER BY t.ThoiGian DESC LIMIT $per OFFSET $off");
    $st->execute([$like, $like]);
}
$dsDong = $st->fetchAll();
$pages  = max(1, (int)ceil($total / $per));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý người dùng</title>
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../pages/admin/admin.css">
</head>
<body>
<?php require_once __DIR__ . '/../../components/top.php'; ?>
<?php require_once __DIR__ . '/../../components/header.php'; ?>

<main class="admin-page">
  <h1>Quản lý người dùng</h1>

  <nav class="tabs">
    <a class="<?= $tab === 'taikhoan' ? 'active' : '' ?>" href="nguoidung.php?tab=taikhoan">Tài khoản</a>
    <a class="<?= $tab === 'truycap' ? 'active' : '' ?>" href="nguoidung.php?tab=truycap">Lượt truy cập hệ thống</a>
  </nav>

  <form method="get" action="nguoidung.php" class="search-form">
    <input type="hidden" name="tab" value="<?= e($tab) ?>">
    <input name="q" value="<?= e($q) ?>" placeholder="<?= $tab === 'taikhoan' ? 'Tên hoặc email' : 'IP hoặc trang' ?>">
    <button type="submit">Tìm</button>
  </form>

  <?php if ($tab === 'taikhoan'): ?>
  <div class="table-wrap">
    <table class="table-stack">
      <tr>
        <th>ID</th><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th>
        <th>Truy cập cuối</th><th>IP cuối</th><th>Thao tác</th>
      </tr>
      <?php foreach ($dsDong as $r): ?>
      <tr>
        <td data-label="ID"><?= (int)$r['ID'] ?></td>
        <td data-label="Họ tên"><a href="nguoidung_chitiet.php?id=<?= (int)$r['ID'] ?>"><?= e($r['HoTen']) ?></a></td>
        <td data-label="Email"><?= e($r['Email']) ?></td>
        <td data-label="Vai trò"><span class="badge badge-<?= e($r['VaiTro']) ?>"><?= e($r['VaiTro']) ?></span></td>
        <td data-label="Trạng thái"><span class="badge badge-<?= e($r['TrangThai']) ?>"><?= e($r['TrangThai']) ?></span></td>
        <td data-label="Truy cập cuối"><?= e($r['LanCuoi'] ?? '—') ?></td>
        <td data-label="IP cuối"><?= e($r['IPCuoi'] ?? '—') ?></td>
        <td data-label="Thao tác">
          <?php if ($r['VaiTro'] !== 'admin' && (int)$r['ID'] !== (int)$_SESSION['user_id']): ?>
          <form method="post" action="nguoidung_khoa.php"
                onsubmit="return confirm('Xác nhận thay đổi trạng thái tài khoản này?');">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$r['ID'] ?>">
            <input type="hidden" name="hanh_dong" value="<?= $r['TrangThai'] === 'khoa' ? 'mo' : 'khoa' ?>">
            <?php if ($r['TrangThai'] === 'khoa'): ?>
              <button type="submit" class="btn btn-sm btn-success">🔓 Mở khoá</button>
            <?php else: ?>
              <button type="submit" class="btn btn-sm btn-danger">🔒 Khoá</button>
            <?php endif; ?>
          </form>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <?php else: ?>
  <div class="table-wrap">
    <table class="table-stack">
      <tr><th>Thời gian</th><th>Tài khoản</th><th>Địa chỉ IP</th><th>Trang</th><th>Method</th><th>Trình duyệt</th></tr>
      <?php foreach ($dsDong as $r): ?>
      <tr>
        <td data-label="Thời gian"><?= e($r['ThoiGian']) ?></td>
        <td data-label="Tài khoản">
          <?php if ($r['ID_NguoiDung']): ?>
            <a href="nguoidung_chitiet.php?id=<?= (int)$r['ID_NguoiDung'] ?>"><?= e($r['HoTen']) ?></a>
          <?php else: ?>Khách<?php endif; ?>
        </td>
        <td data-label="IP"><?= e($r['DiaChiIP']) ?></td>
        <td data-label="Trang"><?= e($r['Trang']) ?></td>
        <td data-label="Method"><?= e($r['PhuongThuc']) ?></td>
        <td data-label="Trình duyệt"><?= e($r['TrinhDuyet']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>

  <div class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a class="<?= $i === $page ? 'active' : '' ?>"
         href="nguoidung.php?tab=<?= e($tab) ?>&amp;q=<?= urlencode($q) ?>&amp;page=<?= $i ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
</main>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
<script src="../../assets/js/components.js"></script>
</body>
</html>