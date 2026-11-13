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
    /* Khu vực nội dung */
    .ticket-container {
    max-width: 1200px;
    margin: 10px auto 35px;
    padding: 20px;
}

    /* Tiêu đề trang */
    .ticket-heading {
    position: relative;
    text-align: center;
    background: transparent;
    padding: 12px 20px 28px;
    margin-bottom: 30px;
}

.ticket-heading .heading-label {
    color: #55a0cb !important;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 3px;
    margin-bottom: 10px;
}

.ticket-heading h1 {
    color: #ffffff !important;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
    font-size: 42px;
    font-weight: 800;
    margin: 0 0 10px;
    opacity: 1;
}

.ticket-heading p {
    color: #c2d9eb !important;
    font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
    font-size: 17px;
    margin: 0;
    opacity: 1;
}

    /* Thẻ vé */
    .ticket-card {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 280px;
        background: #fff;
        border: 1px solid #dce6ee;
        border-radius: 17px;
        overflow: hidden;
        margin-bottom: 24px;
        box-shadow: 0 5px 20px rgba(30, 70, 100, 0.06);
    }

    .ticket-content {
        padding: 28px 32px;
        min-width: 0;
    }

    .ticket-card h3 {
        color: #123b5c;
        font-size: 25px;
        font-weight: 750;
        margin: 0 0 20px;
    }

    /* Trạng thái */
    .ticket-status {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 24px;
    }

    .status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 13px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
    }

    .status-active {
        color: #21804c;
        background: #e7f5ec;
    }

    .status-cancelled {
        color: #c43d3d;
        background: #fdeaea;
    }

    .status-checked {
        color: #176ba7;
        background: #e7f2fb;
    }

    .status-waiting {
        color: #a66a13;
        background: #fff4dc;
    }

    /* Thông tin vé */
    .ticket-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 35px;
        row-gap: 18px;
    }

    .ticket-info {
        min-width: 0;
        margin: 0;
    }

    .ticket-info-label {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #8293a2;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .ticket-info-label i {
        color: #8298aa;
    }

    .ticket-info-value {
        color: #183d5d;
        font-size: 16px;
        font-weight: 650;
        overflow-wrap: anywhere;
    }

    .ticket-total {
        color: #2180b9;
        font-size: 20px;
        font-weight: 800;
    }

    /* Mã vé - chỉ một dòng */
    .ticket-code {
        margin-top: 25px;
        padding: 14px 18px;
        border: 1px dashed #a9c9e2;
        border-radius: 11px;
        background: #f5f9fc;
    }

    .ticket-code-label {
        color: #8293a2;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 1px;
        margin-bottom: 7px;
    }

    .ticket-code-value {
        color: #176ba7;
        font-size: 15px;
        font-weight: 800;
        white-space: nowrap;
        overflow-x: auto;
        display: block;
        max-width: 100%;
    }

    /* Khu vực bên phải */
    .ticket-side {
        border-left: 1px dashed #cbdce9;
        background: #f5f9fc;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 25px 20px;
    }

    .ticket-icon {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        background: #e2f0f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #3985b5;
        font-size: 30px;
        margin-bottom: 13px;
    }

    .ticket-brand {
        color: #8293a2;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 1px;
        margin-bottom: 5px;
    }

    .ticket-side h4 {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif !important;
        color: #174568;
        font-size: 19px;
        font-weight: 800;
        margin: 0 0 7px;
    }

    .ticket-side p {
        color: #8293a2;
        font-size: 14px;
        margin: 0 0 20px;
    }

    /* Nút hủy */
    .cancel-button {
        padding: 10px 20px;
        border: 1px solid #ef5555;
        border-radius: 9px;
        background: #fff;
        color: #e34242;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s;
    }

    .cancel-button:hover {
        background: #e34242;
        color: #fff;
    }

    .cancel-button i {
        margin-right: 5px;
    }

    .cancel-disabled {
        color: #8293a2;
        font-size: 13px;
        font-weight: 600;
    }

    /* Thông báo */
    .message {
        padding: 14px 18px;
        background: #eaf5ed;
        color: #287344;
        border-radius: 9px;
        margin-bottom: 20px;
    }

    .message-error {
        background: #fdeeee;
        color: #bd3939;
    }

    .empty-message {
        text-align: center;
        padding: 45px 20px;
        background: #fff;
        border: 1px solid #dce6ee;
        border-radius: 13px;
        color: #718096;
    }

    .empty-message i {
        display: block;
        color: #8bb4d0;
        font-size: 42px;
        margin-bottom: 15px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .ticket-container {
            padding: 15px;
            margin: 20px auto;
        }

        .ticket-heading {
            margin-bottom: 28px;
        }

        .ticket-heading h1 {
            font-size: 30px;
        }

        .ticket-card {
            grid-template-columns: 1fr;
        }

        .ticket-content {
            padding: 22px 18px;
        }

        .ticket-card h3 {
            font-size: 21px;
        }

        .ticket-side {
            border-left: none;
            border-top: 1px dashed #cbdce9;
            padding: 22px;
        }
    }

    @media (max-width: 480px) {
        .ticket-info-grid {
            grid-template-columns: 1fr;
            row-gap: 15px;
        }

        .ticket-heading h1 {
            font-size: 27px;
        }

        .ticket-code-value {
            font-size: 13px;
        }
    }
</style>

<div class="ticket-container">

    <div class="ticket-heading">
        <div class="heading-label">
            <i class="fa-solid fa-ticket"></i>
            MUSIC FOR LIFE
        </div>

        <h1>Vé của tôi</h1>

        <p>Quản lý và theo dõi các vé sự kiện bạn đã đăng ký.</p>
    </div>

    <?php if (isset($_GET['purchased'])): ?>
        <div class="message">
            <i class="fa-solid fa-circle-check"></i>
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
        <div class="message message-error">
            <?= e($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <?php if ($loi !== ''): ?>

        <div class="message message-error">
            <?= e($loi) ?>
        </div>

    <?php elseif (empty($danh_sach_ve)): ?>

        <div class="empty-message">
            <i class="fa-solid fa-ticket"></i>
            Bạn chưa đăng ký sự kiện nào.
        </div>

    <?php else: ?>

        <?php foreach ($danh_sach_ve as $ve): ?>

            <div class="ticket-card">

                <div class="ticket-content">

                    <h3><?= e($ve['TenSuKien']) ?></h3>

                    <div class="ticket-status">

                        <?php if ($ve['TrangThai'] === 'da_dang_ky'): ?>
                            <span class="status status-active">
                                <i class="fa-solid fa-circle-check"></i>
                                Đã đăng ký
                            </span>
                        <?php else: ?>
                            <span class="status status-cancelled">
                                <i class="fa-solid fa-circle-xmark"></i>
                                Đã hủy
                            </span>
                        <?php endif; ?>

                        <?php if ($ve['TrangThaiCheckIn'] === 'da_checkin'): ?>
                            <span class="status status-checked">
                                <i class="fa-solid fa-check"></i>
                                Đã check-in
                            </span>
                        <?php else: ?>
                            <span class="status status-waiting">
                                <i class="fa-regular fa-clock"></i>
                                Chưa check-in
                            </span>
                        <?php endif; ?>

                    </div>

                    <div class="ticket-info-grid">

                        <div class="ticket-info">
                            <div class="ticket-info-label">
                                <i class="fa-solid fa-location-dot"></i>
                                Địa điểm
                            </div>
                            <div class="ticket-info-value">
                                <?= e($ve['DiaDiem']) ?>
                            </div>
                        </div>

                        <div class="ticket-info">
                            <div class="ticket-info-label">
                                <i class="fa-solid fa-calendar-days"></i>
                                Ngày bắt đầu
                            </div>
                            <div class="ticket-info-value">
                                <?= e($ve['ThoiGianBatDau']) ?>
                            </div>
                        </div>

                        <div class="ticket-info">
                            <div class="ticket-info-label">
                                <i class="fa-solid fa-calendar-check"></i>
                                Ngày kết thúc
                            </div>
                            <div class="ticket-info-value">
                                <?= e($ve['ThoiGianKetThuc']) ?>
                            </div>
                        </div>

                        <div class="ticket-info">
                            <div class="ticket-info-label">
                                <i class="fa-solid fa-clock"></i>
                                Ngày đăng ký
                            </div>
                            <div class="ticket-info-value">
                                <?= e($ve['NgayDangKy']) ?>
                            </div>
                        </div>

                        <?php if ($ve['ID_LoaiVe'] !== null): ?>

                            <div class="ticket-info">
                                <div class="ticket-info-label">
                                    <i class="fa-solid fa-ticket"></i>
                                    Loại vé
                                </div>
                                <div class="ticket-info-value">
                                    <?= e($ve['TenLoaiVe'] ?? 'Loại vé không còn tồn tại') ?>
                                </div>
                            </div>

                            <div class="ticket-info">
                                <div class="ticket-info-label">
                                    <i class="fa-solid fa-users"></i>
                                    Số lượng
                                </div>
                                <div class="ticket-info-value">
                                    <?= (int)$ve['SoLuong'] ?> vé
                                </div>
                            </div>

                            <div class="ticket-info">
                                <div class="ticket-info-label">
                                    <i class="fa-solid fa-tag"></i>
                                    Đơn giá
                                </div>
                                <div class="ticket-info-value">
                                    <?= (float)$ve['DonGia'] <= 0
                                        ? 'Miễn phí'
                                        : number_format((float)$ve['DonGia'], 0, ',', '.') . ' VNĐ' ?>
                                </div>
                            </div>

                            <div class="ticket-info">
                                <div class="ticket-info-label">
                                    <i class="fa-solid fa-money-bill"></i>
                                    Thành tiền
                                </div>
                                <div class="ticket-info-value ticket-total">
                                    <?= (float)$ve['ThanhTien'] <= 0
                                        ? 'Miễn phí'
                                        : number_format((float)$ve['ThanhTien'], 0, ',', '.') . ' VNĐ' ?>
                                </div>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="ticket-code">
                        <div class="ticket-code-label">MÃ VÉ</div>
                        <span class="ticket-code-value"><?= e($ve['MaVe']) ?></span>
                    </div>

                </div>

                <div class="ticket-side">

                    <div class="ticket-icon">
                        <i class="fa-solid fa-ticket"></i>
                    </div>

                    <div class="ticket-brand">MUSIC FOR LIFE</div>

                    <h4>Vé sự kiện</h4>

                    <p>Chúc bạn có những trải nghiệm tuyệt vời!</p>

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
                                <i class="fa-solid fa-xmark"></i>
                                Hủy đăng ký
                            </button>

                        </form>

                    <?php elseif ($ve['TrangThai'] === 'da_huy'): ?>

                        <span class="cancel-disabled">
                            Vé đã được hủy
                        </span>

                    <?php elseif ($ve['TrangThaiCheckIn'] === 'da_checkin'): ?>

                        <span class="cancel-disabled">
                            Vé đã check-in
                        </span>

                    <?php else: ?>

                        <span class="cancel-disabled">
                            Không thể hủy vé
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</main>

<?php require __DIR__ . '/../../components/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/components.js"></script>
</body>
</html>
