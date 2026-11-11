<?php
require_once __DIR__ . '/../../config/app.php';
require_once ROOT_PATH . '/config/database.php';

$db = getDB();

// Lấy danh sách sự kiện còn thời gian tổ chức
$sql = "
    SELECT
        s.ID,
        s.TenSuKien,
        s.MoTa,
        s.DiaDiem,
        s.ThoiGianBatDau,
        s.ThoiGianKetThuc,
        s.TrangThai,
        d.TenDanhMuc
    FROM sukien s
    LEFT JOIN danhmuc d ON s.ID_DanhMuc = d.ID
    WHERE s.TrangThai IN ('sap_dien_ra', 'dang_dien_ra')
      AND s.ThoiGianKetThuc >= NOW()
    ORDER BY s.ThoiGianBatDau ASC
";

$stmt = $db->prepare($sql);
$stmt->execute();
$danhSachSuKien = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy ID sự kiện được truyền từ trang Sự kiện
$eventId = isset($_GET['event_id'])
    ? (int)$_GET['event_id']
    : 0;

// Tìm sự kiện được chọn
$suKienDuocChon = null;

foreach ($danhSachSuKien as $item) {
    if ((int)$item['ID'] === $eventId) {
        $suKienDuocChon = $item;
        break;
    }
}

// Nếu không có ID hợp lệ thì chọn sự kiện đầu tiên
if (!$suKienDuocChon && !empty($danhSachSuKien)) {
    $suKienDuocChon = $danhSachSuKien[0];
}

// Hàm định dạng dữ liệu an toàn
function h($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Hàm định dạng ngày
function formatDate($date) {
    return $date
        ? date('d/m/Y', strtotime($date))
        : 'Chưa cập nhật';
}

// Hàm định dạng giờ
function formatTime($start, $end) {
    if (!$start) return 'Chưa cập nhật';

    $result = date('H:i', strtotime($start));

    if ($end) {
        $result .= ' – ' . date('H:i', strtotime($end));
    }

    return $result;
}
?>


<?php
require_once __DIR__ . '/../../includes/auth.php';

// Khởi tạo thông tin người mua
$nguoiMua = [
    'HoTen' => '',
    'Email' => '',
    'SoDienThoai' => ''
];

// Nếu đã đăng nhập, lấy thông tin từ database
if (isLoggedIn()) {
    $userId = (int) $_SESSION['user_id'];

    $stmt = getDB()->prepare("
        SELECT HoTen, Email, SoDienThoai
        FROM nguoidung
        WHERE ID = ?
    ");

    $stmt->execute([$userId]);

    $nguoiMua = $stmt->fetch(PDO::FETCH_ASSOC) ?: $nguoiMua;
}
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mua vé sự kiện | Music For Life</title>

    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="muave.css?v=4">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>


<body>

<?php
// Thanh thông tin phía trên
include __DIR__ . '/../../components/top.php';

// Header và thanh điều hướng
include ROOT_PATH . '/components/header.php';
?>

<main class="ticket-main">


    <!-- Cá hologram trang trí -->
    <div class="holo-fish fish-one" aria-hidden="true"></div>
    <div class="holo-fish fish-two" aria-hidden="true"></div>
    <div class="holo-fish fish-three" aria-hidden="true"></div>
    <div class="holo-fish fish-four" aria-hidden="true"></div>

    <div class="holo-bubbles" aria-hidden="true"></div>

    <div class="ticket-title">
        <span class="ticket-eyebrow">
            <i class="fa-solid fa-ticket"></i> MUSIC FOR LIFE
        </span>

        <h1>Đặt vé sự kiện</h1>

        <p>
            Chọn sự kiện, loại vé phù hợp và sẵn sàng
            trải nghiệm những khoảnh khắc đáng nhớ.
        </p>
    </div>

    <div class="ticket-layout">

        <!-- KHUNG SỰ KIỆN BÊN TRÁI -->
        <section class="event-card">

            <div class="event-cover">

                <span class="cover-label">SỰ KIỆN NỔI BẬT</span>

                <i class="fa-solid fa-music"></i>

                <h2 id="eventCoverTitle">
                    <?= $suKienDuocChon
                        ? h($suKienDuocChon['TenSuKien'])
                        : 'CHƯA CÓ SỰ KIỆN' ?>
                </h2>

                <p id="eventCoverSubtitle">
                    <?= $suKienDuocChon
                        ? h($suKienDuocChon['TenDanhMuc'] ?? 'Sự kiện')
                        : 'Vui lòng quay lại sau' ?>
                </p>

            </div>

            <div class="event-content">

                <span class="event-tag" id="eventCategory">
                    <?= $suKienDuocChon
                        ? h($suKienDuocChon['TenDanhMuc'] ?? 'Sự kiện')
                        : 'Chưa có danh mục' ?>
                </span>

                <h2 id="eventName">
                    <?= $suKienDuocChon
                        ? h($suKienDuocChon['TenSuKien'])
                        : 'Hiện chưa có sự kiện' ?>
                </h2>

                <div class="event-detail">
                    <i class="fa-regular fa-calendar"></i>
                    <div>
                        <span>Ngày tổ chức</span>
                        <strong id="eventDate">
                            <?= $suKienDuocChon
                                ? formatDate($suKienDuocChon['ThoiGianBatDau'])
                                : 'Chưa cập nhật' ?>
                        </strong>
                    </div>
                </div>

                <div class="event-detail">
                    <i class="fa-regular fa-clock"></i>
                    <div>
                        <span>Thời gian</span>
                        <strong id="eventTime">
                            <?= $suKienDuocChon
                                ? formatTime(
                                    $suKienDuocChon['ThoiGianBatDau'],
                                    $suKienDuocChon['ThoiGianKetThuc']
                                )
                                : 'Chưa cập nhật' ?>
                        </strong>
                    </div>
                </div>

                <div class="event-detail">
                    <i class="fa-solid fa-location-dot"></i>
                    <div>
                        <span>Địa điểm</span>
                        <strong id="eventLocation">
                            <?= $suKienDuocChon
                                ? h($suKienDuocChon['DiaDiem'])
                                : 'Chưa cập nhật' ?>
                        </strong>
                    </div>
                </div>

                <div class="event-intro">
                    <h3>Về sự kiện</h3>

                    <p id="eventDescription">
                        <?= $suKienDuocChon
                            ? h($suKienDuocChon['MoTa'])
                            : 'Hiện chưa có thông tin sự kiện.' ?>
                    </p>
                </div>

                <div class="event-benefits">
                    <div>
                        <i class="fa-solid fa-circle-check"></i>
                        Đặt vé nhanh chóng
                    </div>

                    <div>
                        <i class="fa-solid fa-shield-halved"></i>
                        Thông tin đặt vé rõ ràng
                    </div>

                    <div>
                        <i class="fa-solid fa-headset"></i>
                        Hỗ trợ khách tham dự
                    </div>
                </div>

            </div>
        </section>

        <!-- KHUNG THÔNG TIN ĐẶT VÉ -->
        <section class="booking-card">

            <div class="booking-title">
                <span class="booking-step">01 / ĐẶT CHỖ</span>

                <h2>Thông tin đặt vé</h2>

                <p>Vui lòng điền chính xác thông tin của bạn.</p>
            </div>

            <form action="#" method="post" id="bookingForm">

                <div class="form-group">
                    <label for="event">
                        Sự kiện <span>*</span>
                    </label>

                    <select
                        id="event"
                        name="ID_SuKien"
                        required
                        <?= empty($danhSachSuKien) ? 'disabled' : '' ?>
                    >

                        <?php if (empty($danhSachSuKien)): ?>

                            <option value="">
                                Hiện chưa có sự kiện
                            </option>

                        <?php else: ?>

                            <?php foreach ($danhSachSuKien as $item): ?>

                                <option
                                    value="<?= (int)$item['ID'] ?>"
                                    data-name="<?= h($item['TenSuKien']) ?>"
                                    data-category="<?= h($item['TenDanhMuc'] ?? 'Sự kiện') ?>"
                                    data-date="<?= formatDate($item['ThoiGianBatDau']) ?>"
                                    data-time="<?= formatTime($item['ThoiGianBatDau'], $item['ThoiGianKetThuc']) ?>"
                                    data-location="<?= h($item['DiaDiem']) ?>"
                                    data-description="<?= h($item['MoTa']) ?>"
                                    <?= $suKienDuocChon && (int)$item['ID'] === (int)$suKienDuocChon['ID']
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= h($item['TenSuKien']) ?>
                                </option>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </select>
                </div>

                <div class="form-group">
                    <label for="MaVe">
                        Loại vé <span>*</span>
                    </label>

                    <select id="MaVe" name="MaVe" required>
                        <option value="1" data-price="200000">
                            Vé thường — 200.000 VNĐ
                        </option>

                        <option value="2" data-price="500000">
                            Vé VIP — 500.000 VNĐ
                        </option>

                        <option value="3" data-price="1000000">
                            Vé VVIP — 1.000.000 VNĐ
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="SoLuong">
                        Số lượng vé <span>*</span>
                    </label>

                    <input
                        type="number"
                        id="SoLuong"
                        name="SoLuong"
                        min="1"
                        max="10"
                        value="1"
                        required
                    >

                    <small class="field-hint">
                        Bạn có thể đặt tối đa 10 vé trong một lần.
                    </small>
                </div>

                <div class="form-divider">
                    <h3>
                        <i class="fa-regular fa-user"></i>
                        Thông tin người mua
                    </h3>
                </div>

                
<div class="form-group">
    <label for="name">
        Họ và tên <span>*</span>
    </label>

    <input
        type="text"
        id="name"
        name="HoTen"
        value="<?= e($nguoiMua['HoTen']) ?>"
        placeholder="Nhập họ và tên"
        required
    >
</div>

<div class="form-group">
    <label for="email">
        Email <span>*</span>
    </label>

    <input
        type="email"
        id="email"
        name="Email"
        value="<?= e($nguoiMua['Email']) ?>"
        placeholder="Nhập email"
        required
    >
</div>

<div class="form-group">
    <label for="phone">
        Số điện thoại <span>*</span>
    </label>

    <input
        type="tel"
        id="phone"
        name="SoDienThoai"
        value="<?= e($nguoiMua['SoDienThoai']) ?>"
        placeholder="Nhập số điện thoại"
        required
    >
</div>


                <div class="ticket-total">
                    <div>
                        <span>Tổng thanh toán dự kiến</span>
                        <small>Đã bao gồm giá vé đã chọn</small>
                    </div>

                    <strong id="totalPrice">200.000 VNĐ</strong>
                </div>

                <div class="ticket-note">
                    <i class="fa-solid fa-circle-info"></i>

                    <p>
                        Vui lòng kiểm tra lại thông tin trước khi xác nhận.
                        Đây hiện là giao diện demo; việc lưu đăng ký và
                        thanh toán cần được tích hợp ở bước backend.
                    </p>
                </div>

                <button
                    type="submit"
                    class="btn-booking"
                    <?= empty($danhSachSuKien) ? 'disabled' : '' ?>
                >
                    <i class="fa-solid fa-ticket"></i>
                    Xác nhận đặt vé
                </button>

                <p class="secure-note">
                    <i class="fa-solid fa-lock"></i>
                    Thông tin của bạn được sử dụng cho việc đặt vé.
                </p>

            </form>
        </section>

    </div>
</main>

<?php include ROOT_PATH . '/components/footer.php'; ?>

<script src="../../assets/js/components.js"></script>
<script src="muave.js?v=4"></script>

</body>
</html>
