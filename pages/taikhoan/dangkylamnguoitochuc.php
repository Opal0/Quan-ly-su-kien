<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();   // đã đồng bộ user_role từ DB

$nguoiGui = currentUser();
$db       = getDB();
$loi = ''; $ok = '';

if (currentRole() === 'user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfValid()) {
        $loi = 'Phien lam viec khong hop le, vui long thu lai.';
    } else {
        $lyDo = trim($_POST['ly_do'] ?? '');

        $st = $db->prepare("SELECT 1 FROM yeucau_organizer WHERE ID_NguoiDung = ? AND TrangThai = 'cho_duyet'");
        $st->execute([$nguoiGui['id']]);

        if ($st->fetch()) {
            $loi = 'Ban da co mot yeu cau dang cho duyet.';
        } elseif ($lyDo === '') {
            $loi = 'Vui long nhap ly do.';
        } else {
            try {
                $db->beginTransaction();

                $st = $db->prepare("INSERT INTO yeucau_organizer (ID_NguoiDung, LyDo) VALUES (?, ?)");
                $st->execute([$nguoiGui['id'], $lyDo]);

                // Thông báo cho tất cả admin (chữ hiển thị được viết trong thongbao.php)
                guiThongBaoAdmin(
                    $db,
                    'Yêu cầu làm nhà tổ chức',   // tiêu đề tạm, thongbao.php sẽ tự thay
                    $nguoiGui['name'],           // chỉ lưu tên người gửi
                    'yeucau_organizer'
                );

                $db->commit();
                $ok = 'Da gui yeu cau. Vui long cho admin duyet.';
            } catch (Throwable $ex) {
                if ($db->inTransaction()) $db->rollBack();
                // Khi chạy thật nên dùng: error_log($ex->getMessage());
                $loi = 'Loi gui yeu cau: ' . $ex->getMessage();
            }
        }
    }
}

// Yêu cầu gần nhất của user này
$st = $db->prepare("SELECT * FROM yeucau_organizer WHERE ID_NguoiDung = ? ORDER BY ID DESC LIMIT 1");
$st->execute([$nguoiGui['id']]);
$ycGanNhat = $st->fetch();

// Vừa gửi xong thì coi như đang chờ duyệt để hiện đúng trạng thái
$dangCho = $ok !== '' || ($ycGanNhat && $ycGanNhat['TrangThai'] === 'cho_duyet');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng ký làm nhà tổ chức</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/pages/taikhoan/taikhoan.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../../components/top.php'; ?>
<?php require __DIR__ . '/../../components/header.php'; ?>

<main class="main-content">
    <h2 class="main-title">Đăng ký làm nhà tổ chức sự kiện</h2>

    <?php if ($ok):  ?><p style="color:green"><?= e($ok) ?></p><?php endif; ?>
    <?php if ($loi): ?><p style="color:red"><?= e($loi) ?></p><?php endif; ?>

    <?php if (currentRole() !== 'user'): ?>
        <p>Tài khoản của bạn hiện là <b><?= e(currentRole()) ?></b>, không cần đăng ký thêm.</p>

    <?php elseif ($dangCho): ?>
        <?php if ($ycGanNhat): ?>
            <p>Yêu cầu gửi lúc <?= e($ycGanNhat['NgayGui']) ?> đang <b>chờ admin duyệt</b>.</p>
        <?php endif; ?>

    <?php else: ?>
        <form method="post">
            <?= csrfField() ?>
            <label for="ly_do" style="color: white;">Giới thiệu về bạn / tổ chức và lý do muốn tổ chức sự kiện</label><br>
            <textarea id="ly_do" class="form-textarea" name="ly_do" rows="10" required></textarea>
            <br>
            <button id="organizer-button" type="submit">Gửi yêu cầu</button>
        </form>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../../components/footer.php'; ?>
<script src="../../assets/js/components.js"></script>
</body>
</html>