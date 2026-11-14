<?php
require_once __DIR__ . '/../../config/app.php';
require_once ROOT_PATH . '/includes/auth.php';


$pdo = getDB();
$pageError = '';
$events = [];

$ticketsByEvent = [];
$buyer = [
    'HoTen' => '',
    'Email' => '',
    'SoDienThoai' => ''
];

// Lấy danh sách sự kiện còn thời gian tổ chức.
try {
    $sqlEvents = "SELECT
                    s.ID,
                    s.TenSuKien,
                    COALESCE(d.TenDanhMuc, 'Sự kiện') AS TenDanhMuc,
                    s.MoTa,
                    s.DiaDiem,
                    s.ThoiGianBatDau,
                    s.ThoiGianKetThuc
                  FROM sukien s
                  LEFT JOIN danhmuc d ON d.ID = s.ID_DanhMuc
                  WHERE s.TrangThai <> 'da_huy'
                    AND s.ThoiGianKetThuc >= NOW()
                  ORDER BY s.ThoiGianBatDau ASC";

    $events = $pdo->query($sqlEvents)->fetchAll(PDO::FETCH_ASSOC);

    // Lấy các loại vé đang mở bán.
    $sqlTickets = "SELECT
                        ID,
                        ID_SuKien,
                        TenLoaiVe,
                        GiaVe,
                        SoLuongToiDa
                   FROM loaive
                   WHERE TrangThai = 1
                   ORDER BY GiaVe ASC, ID ASC";

    $allTickets = $pdo->query($sqlTickets)->fetchAll(PDO::FETCH_ASSOC);

    // Nhóm loại vé theo từng sự kiện.
    foreach ($allTickets as $ticket) {
        $eventId = (int)$ticket['ID_SuKien'];

        $ticketsByEvent[$eventId][] = [
            'id' => (int)$ticket['ID'],
            'name' => (string)$ticket['TenLoaiVe'],
            'price' => (float)$ticket['GiaVe'],
            'quantity' => (int)$ticket['SoLuongToiDa']
        ];
    }

} catch (PDOException $ex) {
    error_log('muave.php: ' . $ex->getMessage());
    $pageError = 'Không thể tải dữ liệu sự kiện hoặc loại vé. Vui lòng kiểm tra lại database.';
}

// Chọn sự kiện theo event_id trên URL.
$requestedEventId = isset($_GET['event_id'])
    ? (int)$_GET['event_id']
    : 0;

$selectedEvent = null;

foreach ($events as $event) {
    if ((int)$event['ID'] === $requestedEventId) {
        $selectedEvent = $event;
        break;
    }
}

// Nếu URL không có ID hợp lệ thì chọn sự kiện đầu tiên.
if (!$selectedEvent && !empty($events)) {
    $selectedEvent = $events[0];
}

$selectedEventId = $selectedEvent
    ? (int)$selectedEvent['ID']
    : 0;

// Lấy thông tin người mua từ tài khoản đang đăng nhập.
if (isLoggedIn()) {
    try {
        $stmtBuyer = $pdo->prepare(
            'SELECT HoTen, Email, SoDienThoai
             FROM nguoidung
             WHERE ID = ?
             LIMIT 1'
        );

        $stmtBuyer->execute([(int)$_SESSION['user_id']]);

        $dbBuyer = $stmtBuyer->fetch(PDO::FETCH_ASSOC);

        if ($dbBuyer) {
            $buyer = array_merge($buyer, $dbBuyer);
        }

    } catch (PDOException $ex) {
        error_log('muave.php buyer: ' . $ex->getMessage());
    }
}

// Chuẩn bị dữ liệu sự kiện và loại vé cho JavaScript.
$eventData = [];

foreach ($events as $event) {
    $id = (int)$event['ID'];

    $start = !empty($event['ThoiGianBatDau'])
        ? strtotime($event['ThoiGianBatDau'])
        : false;

    $end = !empty($event['ThoiGianKetThuc'])
        ? strtotime($event['ThoiGianKetThuc'])
        : false;

    $eventData[$id] = [
        'id' => $id,
        'name' => (string)$event['TenSuKien'],
        'category' => (string)$event['TenDanhMuc'],

        'description' => trim((string)$event['MoTa']) !== ''
            ? (string)$event['MoTa']
            : 'Thông tin giới thiệu sự kiện sẽ được cập nhật sớm.',

        'location' => (string)$event['DiaDiem'],

        'date' => $start
            ? date('d/m/Y', $start)
            : 'Đang cập nhật',

        'time' => $start
            ? date('H:i', $start) . ($end ? ' – ' . date('H:i', $end) : '')
            : 'Đang cập nhật',

        // Loại vé riêng của sự kiện.
        'tickets' => $ticketsByEvent[$id] ?? []
    ];
}

// Hàm xử lý dữ liệu hiển thị an toàn.
function muave_escape($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$initialTickets = $selectedEventId > 0
    ? ($ticketsByEvent[$selectedEventId] ?? [])
    : [];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mua vé sự kiện | Music For Life</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="muave.css?v=4">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<?php include __DIR__ . '/../../components/top.php'; ?>
<?php include ROOT_PATH . '/components/header.php'; ?>

<main class="ticket-main">

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
            Chọn loại vé phù hợp và sẵn sàng trải nghiệm
            những khoảnh khắc đáng nhớ.
        </p>
    </div>

    <?php if ($pageError !== ''): ?>
        <div class="ticket-note" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <p><?= muave_escape($pageError) ?></p>
        </div>
    <?php endif; ?>

    <div class="ticket-layout">

        <!-- KHUNG THÔNG TIN SỰ KIỆN -->
        <section class="event-card">

            <div class="event-cover">

                <span class="cover-label" id="eventCoverLabel">
                    <?= $selectedEvent
                        ? muave_escape(mb_strtoupper($selectedEvent['TenDanhMuc'], 'UTF-8'))
                        : 'SỰ KIỆN NỔI BẬT' ?>
                </span>

                <i class="fa-solid fa-music"></i>

                <h2>MUSIC FOR LIFE</h2>

                <p>Âm nhạc kết nối cảm xúc</p>
            </div>

            <div class="event-content">

                <span class="event-tag" id="eventCategory">
                    <?= $selectedEvent
                        ? muave_escape($selectedEvent['TenDanhMuc'])
                        : 'Chưa có sự kiện' ?>
                </span>

                <h2 id="eventName">
                    <?= $selectedEvent
                        ? muave_escape($selectedEvent['TenSuKien'])
                        : 'Hiện chưa có sự kiện phù hợp' ?>
                </h2>

                <div class="event-detail">
                    <i class="fa-regular fa-calendar"></i>

                    <div>
                        <span>Ngày tổ chức</span>

                        <strong id="eventDate">
                            <?= $selectedEvent && $selectedEvent['ThoiGianBatDau']
                                ? date('d/m/Y', strtotime($selectedEvent['ThoiGianBatDau']))
                                : 'Đang cập nhật' ?>
                        </strong>
                    </div>
                </div>

                <div class="event-detail">
                    <i class="fa-regular fa-clock"></i>

                    <div>
                        <span>Thời gian</span>

                        <strong id="eventTime">
                            <?php if ($selectedEvent && $selectedEvent['ThoiGianBatDau']): ?>

                                <?= date('H:i', strtotime($selectedEvent['ThoiGianBatDau'])) ?>

                                <?= $selectedEvent['ThoiGianKetThuc']
                                    ? ' – ' . date('H:i', strtotime($selectedEvent['ThoiGianKetThuc']))
                                    : '' ?>

                            <?php else: ?>

                                Đang cập nhật

                            <?php endif; ?>
                        </strong>
                    </div>
                </div>

                <div class="event-detail">
                    <i class="fa-solid fa-location-dot"></i>

                    <div>
                        <span>Địa điểm</span>

                        <strong id="eventLocation">
                            <?= $selectedEvent
                                ? muave_escape($selectedEvent['DiaDiem'])
                                : 'Đang cập nhật' ?>
                        </strong>
                    </div>
                </div>

                <div class="event-intro">
                    <h3>Về sự kiện</h3>

                    <p id="eventDescription">
                        <?= $selectedEvent && trim((string)$selectedEvent['MoTa']) !== ''
                            ? nl2br(muave_escape($selectedEvent['MoTa']))
                            : 'Thông tin giới thiệu sự kiện sẽ được cập nhật sớm.' ?>
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


        <!-- KHUNG ĐẶT VÉ -->
        <section class="booking-card">

            <div class="booking-title">
                <span class="booking-step">01 / ĐẶT CHỖ</span>

                <h2>Thông tin đặt vé</h2>

                <p>Vui lòng điền chính xác thông tin của bạn.</p>
            </div>
            <?php if (!empty($_GET['error'])): ?>
    <div class="ticket-note" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <p><?= muave_escape($_GET['error']) ?></p>
    </div>
<?php endif; ?>
            <form
    action="<?= BASE_URL ?>/backend/registrations/register.php"
    method="post"
    id="bookingForm"
>

    <?= csrfField() ?>

                <!-- CHỌN SỰ KIỆN -->
                <div class="form-group">

                    <label for="event">
                        Sự kiện <span>*</span>
                    </label>

                    <select
                        id="event"
                        name="ID_SuKien"
                        required
                        <?= empty($events) ? 'disabled' : '' ?>
                    >

                        <?php if (empty($events)): ?>

                            <option value="">
                                Hiện chưa có sự kiện sắp diễn ra
                            </option>

                        <?php else: ?>

                            <?php foreach ($events as $event): ?>

                                <option
                                    value="<?= (int)$event['ID'] ?>"
                                    <?= (int)$event['ID'] === $selectedEventId ? 'selected' : '' ?>
                                >
                                    <?= muave_escape($event['TenSuKien']) ?>
                                </option>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </select>
                </div>


                <!-- CHỌN LOẠI VÉ THEO SỰ KIỆN -->
                <div class="form-group">

                    <label for="MaVe">
                        Loại vé <span>*</span>
                    </label>

                    <select
                        id="MaVe"
                        name="ID_LoaiVe"
                        required
                        <?= empty($initialTickets) ? 'disabled' : '' ?>
                    >

                        <?php if (empty($initialTickets)): ?>

                            <option value="">
                                Sự kiện này hiện chưa mở bán vé
                            </option>

                        <?php else: ?>

                            <?php foreach ($initialTickets as $ticket): ?>

                                <option
                                    value="<?= (int)$ticket['ID'] ?>"
                                    data-price="<?= (float)$ticket['GiaVe'] ?>"
                                >
                                    <?= muave_escape($ticket['TenLoaiVe']) ?>

                                    —

                                    <?= (float)$ticket['GiaVe'] <= 0
                                        ? 'Miễn phí'
                                        : number_format((float)$ticket['GiaVe'], 0, ',', '.') . ' VNĐ' ?>
                                </option>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </select>

                    <small class="field-hint" id="ticketHint">
                        <?= empty($initialTickets)
                            ? 'Vui lòng chọn sự kiện khác hoặc quay lại sau.'
                            : 'Các loại vé được thiết lập riêng cho sự kiện này.' ?>
                    </small>

                </div>


                <!-- SỐ LƯỢNG -->
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


                <!-- THÔNG TIN NGƯỜI MUA -->
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
                        value="<?= muave_escape($buyer['HoTen']) ?>"
                        placeholder="Ví dụ: Nguyễn Văn An"
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
                        value="<?= muave_escape($buyer['Email']) ?>"
                        placeholder="name@example.com"
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
                        value="<?= muave_escape($buyer['SoDienThoai']) ?>"
                        placeholder="Nhập số điện thoại"
                        required
                    >

                </div>


                <!-- TỔNG TIỀN -->
                <div class="ticket-total">

                    <div>
                        <span>Tổng thanh toán dự kiến</span>
                        <small>Đã bao gồm giá vé đã chọn</small>
                    </div>

                    <strong id="totalPrice">
                        <?= !empty($initialTickets)
                            ? (
                                (float)$initialTickets[0]['GiaVe'] <= 0
                                    ? 'Miễn phí'
                                    : number_format((float)$initialTickets[0]['GiaVe'], 0, ',', '.') . ' VNĐ'
                            )
                            : '0 VNĐ' ?>
                    </strong>

                </div>


                <!-- GHI CHÚ -->
                <div class="ticket-note">
                    <i class="fa-solid fa-circle-info"></i>

                    <p>
                        Vui lòng kiểm tra lại thông tin trước khi xác nhận.
                    </p>
                </div>


                <!-- NÚT ĐẶT VÉ -->
                <button
                    type="submit"
                    class="btn-booking"
                    id="bookingButton"
                    <?= empty($initialTickets) ? 'disabled' : '' ?>
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


<!-- DỮ LIỆU SỰ KIỆN CHO JAVASCRIPT -->
<script type="application/json" id="eventData"><?=
    json_encode(
        $eventData,
        JSON_UNESCAPED_UNICODE |
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_HEX_AMP
    )
?></script>


<?php include ROOT_PATH . '/components/footer.php'; ?>

<script src="../../assets/js/components.js"></script>
<script src="muave.js?v=5"></script>

</body>
</html>
