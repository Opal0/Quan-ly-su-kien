<?php
require_once __DIR__ . '/../../includes/auth.php';

// Bắt buộc đăng nhập
requireLogin();

$userId = (int) $_SESSION['user_id'];
$bookingId = (int) ($_GET['id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: ' . BASE_URL . '/pages/taikhoan/ve_cua_toi.php');
    exit;
}

$db = getDB();

// Lấy thông tin đăng ký và giao dịch thanh toán
$stmt = $db->prepare("
    SELECT
        ds.ID AS ID_DangKy,
        ds.ID_NguoiDung,
        ds.ID_SuKien,
        ds.ID_LoaiVe,
        ds.SoLuong,
        ds.DonGia,
        ds.ThanhTien,
        ds.TrangThai AS TrangThaiDangKy,

        s.TenSuKien,
        s.ThoiGianBatDau,
        s.DiaDiem,

        lv.TenLoaiVe,

        tt.ID AS ID_ThanhToan,
        tt.SoTien,
        tt.PhuongThuc,
        tt.TrangThai AS TrangThaiThanhToan,
        tt.NgayTao

    FROM dangkysukien ds

    INNER JOIN sukien s
        ON ds.ID_SuKien = s.ID

    LEFT JOIN loaive lv
        ON ds.ID_LoaiVe = lv.ID

    INNER JOIN thanhtoan tt
        ON tt.ID_DangKy = ds.ID

    WHERE ds.ID = ?
      AND ds.ID_NguoiDung = ?

    ORDER BY tt.ID DESC
    LIMIT 1
");

$stmt->execute([$bookingId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Không tìm thấy đơn hàng hoặc đơn hàng không thuộc người dùng
if (!$order) {
    header('Location: ' . BASE_URL . '/pages/taikhoan/ve_cua_toi.php?error=' . urlencode('Không tìm thấy đơn thanh toán.'));
    exit;
}

// Chỉ hiển thị trang thanh toán khi giao dịch còn chờ
if (
    $order['TrangThaiDangKy'] !== 'cho_thanh_toan' ||
    $order['TrangThaiThanhToan'] !== 'cho_thanh_toan'
) {
    header('Location: ' . BASE_URL . '/pages/taikhoan/ve_cua_toi.php');
    exit;
}

require_once ROOT_PATH . '/components/top.php';
require_once ROOT_PATH . '/components/header.php';
?>

<style>
.payment-main {
    flex: 1 0 auto;
    width: 100%;
    min-height: 75vh;
    padding: 55px 20px;
    background: #f3f7fb;
}

.payment-container {
    max-width: 850px;
    margin: 0 auto;
}

.payment-heading {
    text-align: center;
    margin-bottom: 30px;
}

.payment-heading h1 {
    color: #234d70;
    font-size: 30px;
    margin-bottom: 10px;
}

.payment-heading p {
    color: #667788;
    font-size: 15px;
}

.payment-card {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 5px 25px rgba(30, 65, 95, 0.08);
}

.payment-demo {
    text-align: center;
    padding: 18px;
    background: #eaf4fb;
    border: 1px dashed #4c8eb7;
    border-radius: 12px;
    margin-bottom: 25px;
}

.payment-demo strong {
    display: block;
    color: #234d70;
    font-size: 17px;
    margin-bottom: 6px;
}

.payment-demo p {
    color: #587084;
    font-size: 14px;
    margin: 0;
}

.order-title {
    color: #234d70;
    font-size: 19px;
    margin-bottom: 18px;
}

.order-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 13px 0;
    border-bottom: 1px solid #edf0f3;
    color: #536474;
}

.order-row strong {
    color: #263f53;
    text-align: right;
}

.order-total {
    margin-top: 10px;
    padding-top: 18px;
    border-top: 2px solid #e8eef3;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.order-total span {
    color: #234d70;
    font-weight: 700;
}

.order-total strong {
    color: #e05b45;
    font-size: 25px;
}

.payment-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.payment-actions {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    gap: 15px;
    margin-top: 30px;
    width: 100%;
}

.payment-actions button {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    min-height: 48px;
}

.payment-actions button {
    flex: 1;
    border: none;
    border-radius: 9px;
    padding: 14px 18px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s;
}

.btn-success {
    background: #23865b;
    color: #fff;
}

.btn-success:hover {
    background: #176b47;
}

.btn-failure {
    background: #fff;
    color: #c94c4c;
    border: 1px solid #c94c4c !important;
}

.btn-failure:hover {
    background: #fff1f1;
}

.payment-note {
    margin-top: 20px;
    text-align: center;
    color: #84919c;
    font-size: 13px;
}

@media (max-width: 600px) {
    .payment-card {
        padding: 20px;
    }

    .payment-actions {
        flex-direction: column;
    }

    .order-row {
        font-size: 14px;
    }
}
</style>

<main class="payment-main">
    <div class="payment-container">

        <div class="payment-heading">
            <h1>Thanh toán đơn hàng</h1>
            <p>Vui lòng kiểm tra thông tin trước khi xác nhận thanh toán.</p>
        </div>

        <div class="payment-card">

            <div class="payment-demo">
                <strong>Thanh toán</strong>
            </div>

            <h2 class="order-title">Thông tin đơn đặt vé</h2>

            <div class="order-row">
                <span>Mã đơn hàng</span>
                <strong>#<?= (int) $order['ID_DangKy'] ?></strong>
            </div>

            <div class="order-row">
                <span>Sự kiện</span>
                <strong><?= e($order['TenSuKien']) ?></strong>
            </div>

            <div class="order-row">
                <span>Thời gian sự kiện</span>
                <strong>
                    <?= e(date('d/m/Y H:i', strtotime($order['ThoiGianBatDau']))) ?>
                </strong>
            </div>

            <div class="order-row">
                <span>Địa điểm</span>
                <strong><?= e($order['DiaDiem'] ?? 'Chưa cập nhật') ?></strong>
            </div>

            <div class="order-row">
                <span>Loại vé</span>
                <strong><?= e($order['TenLoaiVe'] ?? 'Vé miễn phí') ?></strong>
            </div>

            <div class="order-row">
                <span>Số lượng</span>
                <strong><?= (int) $order['SoLuong'] ?></strong>
            </div>

            <div class="order-row">
                <span>Đơn giá</span>
                <strong>
                    <?= number_format((float) $order['DonGia'], 0, ',', '.') ?> VNĐ
                </strong>
            </div>

            <div class="order-total">
                <span>Tổng thanh toán</span>
                <strong>
                    <?= number_format((float) $order['SoTien'], 0, ',', '.') ?> VNĐ
                </strong>
            </div>

            <form
                action="<?= BASE_URL ?>/backend/payments/mock_process.php"
                method="POST"
            >
                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="ID_DangKy"
                    value="<?= (int) $order['ID_DangKy'] ?>"
                >

                <input
                    type="hidden"
                    name="ID_ThanhToan"
                    value="<?= (int) $order['ID_ThanhToan'] ?>"
                >

                <div class="payment-actions">

                    <button
                        type="submit"
                        name="ket_qua"
                        value="thanh_cong"
                        class="btn-success"
                    >
                        Xác nhận thanh toán
                    </button>

                    <button
                        type="submit"
                        name="ket_qua"
                        value="that_bai"
                        class="btn-failure"
                    >
                        Thanh toán thất bại
                    </button>

                </div>
            </form>

            <p class="payment-note">
                * Hai lựa chọn trên chỉ dùng để mô phỏng kết quả thanh toán.
            </p>

        </div>
    </div>
</main>

<?php require_once ROOT_PATH . '/components/footer.php'; ?>
