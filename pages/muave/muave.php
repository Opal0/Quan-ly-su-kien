<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mua vé sự kiện | Music For Life</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="muave.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
<?php
require_once __DIR__ . '/../../config/app.php';
require_once ROOT_PATH . '/config/database.php';
include __DIR__ . '/../../components/top.html';
include ROOT_PATH . '/components/header.php';
?>

<main class="ticket-main">
    <div class="ticket-title">
        <span class="ticket-eyebrow"><i class="fa-solid fa-ticket"></i> MUSIC FOR LIFE</span>
        <h1>Đặt vé sự kiện</h1>
        <p>Chọn loại vé phù hợp và sẵn sàng trải nghiệm những khoảnh khắc đáng nhớ.</p>
    </div>

    <div class="ticket-layout">
        <section class="event-card">
            <div class="event-cover">
                <span class="cover-label">SỰ KIỆN NỔI BẬT</span>
                <i class="fa-solid fa-music"></i>
                <h2>MUSIC FESTIVAL</h2>
                <p>Âm nhạc kết nối cảm xúc</p>
            </div>

            <div class="event-content">
                <span class="event-tag">Âm nhạc</span>
                <h2>Đêm nhạc hội mùa hè 2026</h2>

                <div class="event-detail">
                    <i class="fa-regular fa-calendar"></i>
                    <div>
                        <span>Ngày tổ chức</span>
                        <strong>20/12/2026</strong>
                    </div>
                </div>
                <div class="event-detail">
                    <i class="fa-regular fa-clock"></i>
                    <div>
                        <span>Thời gian</span>
                        <strong>18:00 – 22:00</strong>
                    </div>
                </div>
                <div class="event-detail">
                    <i class="fa-solid fa-location-dot"></i>
                    <div>
                        <span>Địa điểm</span>
                        <strong>Cần Thơ</strong>
                    </div>
                </div>

                <div class="event-intro">
                    <h3>Về sự kiện</h3>
                    <p>Một đêm nhạc sôi động với nhiều tiết mục biểu diễn hấp dẫn.
                       Hãy chọn vé và đặt chỗ ngay hôm nay để không bỏ lỡ chương trình.</p>
                </div>

                <div class="event-benefits">
                    <div><i class="fa-solid fa-circle-check"></i> Đặt vé nhanh chóng</div>
                    <div><i class="fa-solid fa-shield-halved"></i> Thông tin đặt vé rõ ràng</div>
                    <div><i class="fa-solid fa-headset"></i> Hỗ trợ khách tham dự</div>
                </div>
            </div>
        </section>

        <section class="booking-card">
            <div class="booking-title">
                <span class="booking-step">01 / ĐẶT CHỖ</span>
                <h2>Thông tin đặt vé</h2>
                <p>Vui lòng điền chính xác thông tin của bạn.</p>
            </div>

            <form action="#" method="post" id="bookingForm">
                <div class="form-group">
                    <label for="event">Sự kiện <span>*</span></label>
                    <select id="event" name="ID_SuKien" required>
                        <option value="1">Đêm nhạc hội mùa hè 2026</option>
                        <option value="2">Hội thảo công nghệ 2026</option>
                        <option value="3">Ngày hội việc làm</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="MaVe">Loại vé <span>*</span></label>
                    <select id="MaVe" name="MaVe" required>
                        <option value="1" data-price="200000">Vé thường — 200.000 VNĐ</option>
                        <option value="2" data-price="500000">Vé VIP — 500.000 VNĐ</option>
                        <option value="3" data-price="1000000">Vé VVIP — 1.000.000 VNĐ</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="SoLuong">Số lượng vé <span>*</span></label>
                    <input type="number" id="SoLuong" name="SoLuong"
                           min="1" max="10" value="1" required>
                    <small class="field-hint">Bạn có thể đặt tối đa 10 vé trong một lần.</small>
                </div>

                <div class="form-divider">
                    <h3><i class="fa-regular fa-user"></i> Thông tin người mua</h3>
                </div>

                <div class="form-group">
                    <label for="name">Họ và tên <span>*</span></label>
                    <input type="text" id="name" name="HoTen"
                           placeholder="Ví dụ: Nguyễn Văn An" required>
                </div>

                <div class="form-group">
                    <label for="email">Email <span>*</span></label>
                    <input type="email" id="email" name="Email"
                           placeholder="name@example.com" required>
                </div>

                <div class="form-group">
                    <label for="phone">Số điện thoại <span>*</span></label>
                    <input type="tel" id="phone" name="SoDienThoai"
                           placeholder="Nhập số điện thoại" required>
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
                    <p>Vui lòng kiểm tra lại thông tin trước khi xác nhận. Đây hiện là giao diện demo; việc lưu đăng ký và thanh toán cần được tích hợp ở bước backend.</p>
                </div>

                <button type="submit" class="btn-booking">
                    <i class="fa-solid fa-ticket"></i> Xác nhận đặt vé
                </button>
                <p class="secure-note"><i class="fa-solid fa-lock"></i> Thông tin của bạn được sử dụng cho việc đặt vé.</p>
            </form>
        </section>
    </div>
</main>

<?php include ROOT_PATH . '/components/footer.php'; ?>

<script src="../../assets/js/components.js"></script>
<script src="muave.js"></script>
</body>
</html>
