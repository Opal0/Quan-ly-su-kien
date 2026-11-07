<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';

// Hàm bảo vệ dữ liệu khi hiển thị ra HTML
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Tạo CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Kiểm tra đăng nhập
$da_dang_nhap = isset($_SESSION['user_id']);
$danh_sach_ve = [];
$loi = '';

if ($da_dang_nhap) {
    try {
        $sql = "
            SELECT
                d.ID,
                d.NgayDangKy,
                d.TrangThai,
                d.MaVe,
                d.TrangThaiCheckIn,
                d.ThoiGianCheckIn,
                s.TenSuKien,
                s.DiaDiem,
                s.ThoiGianBatDau,
                s.ThoiGianKetThuc
            FROM DangKySuKien d
            INNER JOIN SuKien s ON d.ID_SuKien = s.ID
            WHERE d.ID_NguoiDung = :user_id
            ORDER BY d.NgayDangKy DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $_SESSION['user_id']
        ]);

        $danh_sach_ve = $stmt->fetchAll();

    } catch (PDOException $e) {
        $loi = 'Không thể tải danh sách vé. Vui lòng thử lại.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vé của tôi - Music For Life</title>

    <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
        .ticket-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
        }

        .ticket-container h1 {
            text-align: center;
            margin-bottom: 25px;
        }

        .ticket-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 18px;
            background: #fff;
        }

        .ticket-card h3 {
            margin-top: 0;
        }

        .ticket-info {
            margin: 8px 0;
        }

        .status {
            font-weight: bold;
        }

        .status-active {
            color: green;
        }

        .status-cancelled {
            color: #d9534f;
        }

        .status-checked {
            color: #1677cc;
        }

        .cancel-button {
            padding: 9px 16px;
            border: none;
            border-radius: 5px;
            background: #d9534f;
            color: white;
            cursor: pointer;
        }

        .message {
            padding: 15px;
            background: #f5f5f5;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .empty-message {
            text-align: center;
            padding: 30px;
            background: #f5f5f5;
            border-radius: 8px;
        }
    </style>
</head>

<body>

<?php
// Sử dụng lại header của dự án nếu có
include __DIR__ . '/../../components/header.php';
?>

<main class="ticket-container"
      style="display:block; color:black; background:white; min-height:500px;">

    <h1>Vé của tôi</h1>

    <?php if (!$da_dang_nhap): ?>

        <div class="message">
            Bạn cần đăng nhập để xem danh sách vé đã đăng ký.
        </div>

    <?php elseif ($loi !== ''): ?>

        <div class="message">
            <?= e($loi) ?>
        </div>

    <?php else: ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="message">
                Đăng ký sự kiện thành công!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['cancelled'])): ?>
            <div class="message">
                Hủy đăng ký thành công!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="message">
                <?= e($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($danh_sach_ve)): ?>

            <div class="empty-message">
                Bạn chưa đăng ký sự kiện nào.
            </div>

        <?php else: ?>

            <?php foreach ($danh_sach_ve as $ve): ?>

                <div class="ticket-card">

                    <h3><?= e($ve['TenSuKien']) ?></h3>

                    <p class="ticket-info">
                        <strong>Địa điểm:</strong>
                        <?= e($ve['DiaDiem']) ?>
                    </p>

                    <p class="ticket-info">
                        <strong>Thời gian bắt đầu:</strong>
                        <?= e($ve['ThoiGianBatDau']) ?>
                    </p>

                    <p class="ticket-info">
                        <strong>Thời gian kết thúc:</strong>
                        <?= e($ve['ThoiGianKetThuc']) ?>
                    </p>

                    <p class="ticket-info">
                        <strong>Ngày đăng ký:</strong>
                        <?= e($ve['NgayDangKy']) ?>
                    </p>

                    <p class="ticket-info">
                        <strong>Mã vé:</strong>
                        <?= e($ve['MaVe']) ?>
                    </p>

                    <p class="ticket-info">
                        <strong>Trạng thái đăng ký:</strong>

                        <?php if ($ve['TrangThai'] === 'da_dang_ky'): ?>
                            <span class="status status-active">
                                Đã đăng ký
                            </span>
                        <?php else: ?>
                            <span class="status status-cancelled">
                                Đã hủy
                            </span>
                        <?php endif; ?>
                    </p>

                    <p class="ticket-info">
                        <strong>Trạng thái check-in:</strong>

                        <?php if ($ve['TrangThaiCheckIn'] === 'da_checkin'): ?>
                            <span class="status status-checked">
                                Đã check-in
                            </span>
                        <?php else: ?>
                            Chưa check-in
                        <?php endif; ?>
                    </p>

                    <?php if (
                        $ve['TrangThai'] === 'da_dang_ky' &&
                        $ve['TrangThaiCheckIn'] !== 'da_checkin' &&
                        strtotime($ve['ThoiGianBatDau']) > time()
                    ): ?>

                        <form
                            action="../../backend/registrations/cancel.php"
                            method="POST"
                            onsubmit="return confirm('Bạn có chắc chắn muốn hủy đăng ký sự kiện này không?');"
                        >

                            <input
                                type="hidden"
                                name="ID_DangKy"
                                value="<?= (int)$ve['ID'] ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf"
                                value="<?= e($_SESSION['csrf_token']) ?>"
                            >

                            <button type="submit" class="cancel-button">
                                Hủy đăng ký
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    <?php endif; ?>

</main>

<?php include __DIR__ . '/../../components/footer.php'; ?>

</body>
</html>