<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid()) {
    $idYc     = (int)($_POST['id'] ?? 0);
    $hanhDong = $_POST['hanh_dong'] ?? '';

    if ($idYc > 0 && in_array($hanhDong, ['duyet', 'tu_choi'], true)) {
        try {
            $db->beginTransaction();

            // 1. Lấy yêu cầu đang chờ duyệt (khóa dòng để tránh duyệt 2 lần)
            $st = $db->prepare("SELECT ID_NguoiDung FROM yeucau_organizer WHERE ID = ? AND TrangThai = 'cho_duyet' FOR UPDATE");
            $st->execute([$idYc]);
            $row = $st->fetch();

            if ($row) {
                // 2. Xác định trạng thái mới
                $trangThai = $hanhDong === 'duyet' ? 'da_duyet' : 'tu_choi';

                // 3. Cập nhật yêu cầu
                $db->prepare("UPDATE yeucau_organizer
                              SET TrangThai = ?, NgayXuLy = NOW(), ID_NguoiDuyet = ?
                              WHERE ID = ?")
                   ->execute([$trangThai, currentUser()['id'], $idYc]);

                // 4. Nếu duyệt thì đổi vai trò (chỉ với user thường)
                if ($trangThai === 'da_duyet') {
                    $db->prepare("UPDATE nguoidung SET VaiTro = 'organizer' WHERE ID = ? AND VaiTro = 'user'")
                       ->execute([$row['ID_NguoiDung']]);

                    guiThongBao($db, (int)$row['ID_NguoiDung'],
                        'Yêu cầu làm nhà tổ chức đã được duyệt',
                        'Chúc mừng! Tài khoản của bạn đã trở thành nhà tổ chức. Bạn có thể bắt đầu đăng sự kiện.',
                        'organizer_duyet');
                } else {
                    guiThongBao($db, (int)$row['ID_NguoiDung'],
                        'Yêu cầu làm nhà tổ chức bị từ chối',
                        'Rất tiếc, yêu cầu của bạn chưa được chấp nhận. Bạn có thể gửi lại yêu cầu mới với thông tin đầy đủ hơn.',
                        'organizer_tuchoi');
                }
            }
            $db->commit();
        } catch (Throwable $ex) {
            if ($db->inTransaction()) $db->rollBack();
            // Khi chạy thật nên đổi thành: error_log($ex->getMessage()); exit('Có lỗi xảy ra, vui lòng thử lại.');
            exit('Loi xu ly yeu cau: ' . $ex->getMessage() . ' (dong ' . $ex->getLine() . ' - ' . basename($ex->getFile()) . ')');
        }
    }
    header('Location: ' . BASE_URL . '/pages/admin/duyetdoivaitro.php');
    exit;
}

$dsYeuCau = $db->query("
    SELECT y.*, n.HoTen, n.Email
    FROM yeucau_organizer y
    JOIN nguoidung n ON n.ID = y.ID_NguoiDung
    WHERE y.TrangThai = 'cho_duyet'
    ORDER BY y.NgayGui ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Duyệt nhà tổ chức</title>
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../pages/admin/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../../components/top.php'; ?>
<?php require __DIR__ . '/../../components/header.php'; ?>

<main class="admin-page">
  <h1>Duyệt nhà tổ chức</h1>
  <p class="page-sub">Các yêu cầu làm nhà tổ chức đang chờ xử lý (<?= count($dsYeuCau) ?>)</p>

  <?php if (!$dsYeuCau): ?>
    <p class="alert alert-warning">Hiện không có yêu cầu nào.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-stack">
      <tr>
        <th>Người dùng</th><th>Email</th><th>Giới thiệu và lý do</th><th>Ngày gửi</th><th>Xử lý</th>
      </tr>
      <?php foreach ($dsYeuCau as $r): ?>
      <tr>
        <td data-label="Người dùng"><?= e($r['HoTen']) ?></td>
        <td data-label="Email"><?= e($r['Email']) ?></td>
        <td data-label="Lý do" class="cell-reason"><?= nl2br(e($r['LyDo'])) ?></td>
        <td data-label="Ngày gửi"><?= e($r['NgayGui']) ?></td>
        <td data-label="Xử lý">
          <form method="post" class="inline-actions">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$r['ID'] ?>">
            <button name="hanh_dong" value="duyet" class="btn btn-sm btn-success">
              <i class="fa-solid fa-check"></i> Duyệt
            </button>
            <button name="hanh_dong" value="tu_choi" class="btn btn-sm btn-danger"
                    onclick="return confirm('Từ chối yêu cầu này?');">
              <i class="fa-solid fa-xmark"></i> Từ chối
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/../../components/footer.php'; ?>
<script src="../../assets/js/components.js"></script>
</body>
</html>