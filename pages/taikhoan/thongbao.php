<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db  = getDB();
$uid = currentUser()['id'];

$st = $db->prepare("SELECT * FROM thongbao WHERE ID_NguoiDung = ? ORDER BY NgayTao DESC, ID DESC LIMIT 50");
$st->execute([$uid]);
$dsThongBao = $st->fetchAll();

// Đánh dấu đã đọc sau khi đã lấy danh sách (để vẫn thấy cái nào là "mới")
$db->prepare("UPDATE thongbao SET DaDoc = 1 WHERE ID_NguoiDung = ? AND DaDoc = 0")
   ->execute([$uid]);

/**
 * Trả về [tiêu đề, nội dung] để hiển thị.
 * Loại thông báo nào cần tự viết chữ thì thêm vào đây.
 */
function noiDungThongBao(array $tb): array
{
    switch ($tb['LoaiThongBao']) {
        case 'yeucau_organizer':
            // cột NoiDung đang lưu tên người gửi
            return [
                'Có yêu cầu làm nhà tổ chức đang chờ duyệt',
                $tb['NoiDung'] . ' vừa gửi yêu cầu trở thành nhà tổ chức. Vào mục "Duyệt người tổ chức" để xử lý.',
            ];

        default:
            // các loại khác (organizer_duyet, organizer_tuchoi...) dùng chữ đã lưu trong DB
            return [$tb['TieuDe'], $tb['NoiDung']];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thông báo</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/pages/taikhoan/taikhoan.css">
</head>
<body>

    <?php
    require ROOT_PATH . '/components/top.php';
    require ROOT_PATH . '/components/header.php';
    ?>

    <main class="main-content">
        <h2>Thông báo</h2>

        <?php if (!$dsThongBao): ?>
            <p>Bạn chưa có thông báo nào.</p>
        <?php endif; ?>

        <?php foreach ($dsThongBao as $tb): ?>
            <?php [$tieuDe, $noiDung] = noiDungThongBao($tb); ?>
            <div style="border:1px solid #ddd; padding:10px; margin-bottom:8px; <?= !$tb['DaDoc'] ? 'background:#fff8e1;' : '' ?>">
                <b><?= e($tieuDe) ?></b>
                <?php if (!$tb['DaDoc']): ?><span style="color:red">(mới)</span><?php endif; ?>
                <p><?= nl2br(e($noiDung)) ?></p>
                <small><?= e(date('d/m/Y H:i', strtotime($tb['NgayTao']))) ?></small>

                <?php if ($tb['LoaiThongBao'] === 'organizer_duyet' && currentRole() === 'organizer'): ?>
                    <p><a href="<?= BASE_URL ?>/pages/sukien/quanly.php">Vào khu vực nhà tổ chức →</a></p>
                <?php endif; ?>

                <?php if ($tb['LoaiThongBao'] === 'yeucau_organizer' && isAdmin()): ?>
                    <p><a href="<?= BASE_URL ?>/pages/admin/duyetdoivaitro.php">Đi tới trang duyệt yêu cầu </a></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>

    <?php require ROOT_PATH . '/components/footer.php'; ?>
    <script src="<?= BASE_URL ?>/assets/js/components.js"></script>
</body>
</html>