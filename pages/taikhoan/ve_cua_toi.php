
<?php
require_once __DIR__ . '/../../includes/auth.php';

// Bắt buộc đăng nhập
requireLogin();

$db = getDB();
$id_nguoi_dung = (int) $_SESSION['user_id'];

$danh_sach_ve = [];
$loi = '';

try {
    // Lấy danh sách vé của người dùng đang đăng nhập
    
$stmt = $db->prepare("
    SELECT
        d.ID,
        d.NgayDangKy,
        d.TrangThai,
        d.MaVe,
        d.TrangThaiCheckIn,
        d.ThoiGianCheckIn,

        d.SoLuong,
        d.DonGia,
        d.ThanhTien,
        d.ID_LoaiVe,

        lv.TenLoaiVe,

        s.TenSuKien,
        s.DiaDiem,
        s.ThoiGianBatDau,
        s.ThoiGianKetThuc

    FROM dangkysukien d

    JOIN sukien s
        ON d.ID_SuKien = s.ID

    LEFT JOIN loaive lv
        ON d.ID_LoaiVe = lv.ID

    WHERE d.ID_NguoiDung = ?

    ORDER BY d.NgayDangKy DESC
");


    $stmt->execute([$id_nguoi_dung]);
    $danh_sach_ve = $stmt->fetchAll();

} catch (PDOException $e) {
    $loi = 'Không thể tải danh sách vé. Vui lòng thử lại.';
}

$pageTitle = 'Vé của tôi - Music4Life';

require __DIR__ . '/../../components/top.php';
require __DIR__ . '/../../components/header.php';
?>


<style>
    .ticket-container {
        max-width: 1000px;
        margin: 35px auto;
        padding: 20px;
    }

    .ticket-container h1 {
        text-align: center;
        margin-bottom: 25px;
        color: #fff;
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
        color: #222;
    }

    .ticket-info {
        margin: 9px 0;
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
        margin-top: 10px;
    }

    .cancel-button:hover {
        background: #b52b27;
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

<div class="ticket-container">

    <h1>Vé của tôi</h1>
    <?php if (isset($_GET['purchased'])): ?>
    <div class="message">
        Mua vé thành công! Vé của bạn đã được lưu vào hệ thống.
    </div>
<?php endif; ?>
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

    <?php if ($loi !== ''): ?>

        <div class="message">
            <?= e($loi) ?>
        </div>

    <?php elseif (empty($danh_sach_ve)): ?>

        <div class="empty-message">
            Bạn chưa đăng ký sự kiện nào.
        </div>

    <?php else: ?>

        <?php foreach ($danh_sach_ve as $ve): ?>

            <div class="ticket-card">

                <h3><?= e($ve['TenSuKien']) ?></h3>

                
<p class="ticket-info">
    <strong>Mã vé:</strong>
    <?= e($ve['MaVe']) ?>
</p>

<?php if ($ve['ID_LoaiVe'] !== null): ?>

    <p class="ticket-info">
        <strong>Loại vé:</strong>
        <?= e($ve['TenLoaiVe'] ?? 'Loại vé không còn tồn tại') ?>
    </p>

    <p class="ticket-info">
        <strong>Số lượng:</strong>
        <?= (int)$ve['SoLuong'] ?> vé
    </p>

    <p class="ticket-info">
        <strong>Đơn giá:</strong>
        <?= (float)$ve['DonGia'] <= 0
            ? 'Miễn phí'
            : number_format((float)$ve['DonGia'], 0, ',', '.') . ' VNĐ' ?>
    </p>

    <p class="ticket-info">
        <strong>Thành tiền:</strong>
        <strong>
            <?= (float)$ve['ThanhTien'] <= 0
                ? 'Miễn phí'
                : number_format((float)$ve['ThanhTien'], 0, ',', '.') . ' VNĐ' ?>
        </strong>
    </p>

<?php endif; ?>


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
                        action="<?= BASE_URL ?>/backend/registrations/cancel.php"
                        method="POST"
                        onsubmit="return confirm('Bạn có chắc chắn muốn hủy đăng ký sự kiện này không?');"
                    >

                        <input
                            type="hidden"
                            name="ID_DangKy"
                            value="<?= (int) $ve['ID'] ?>"
                        >

                        <?= csrfField() ?>

                        <button type="submit" class="cancel-button">
                            Hủy đăng ký
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</main>

<?php require __DIR__ . '/../../components/footer.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/components.js"></script>
</body>
</html>
