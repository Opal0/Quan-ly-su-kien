<?php
session_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once ROOT_PATH . '/config/database.php';

$pdo = getDB();

/* =========================
   KIỂM TRA QUYỀN ADMIN
========================= */
$isAdmin = false;

if (isLoggedIn()) {
    try {
        $st = $pdo->prepare("
            SELECT VaiTro, TrangThai
            FROM nguoidung
            WHERE ID = ?
        ");
        $st->execute([$_SESSION['user_id']]);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if (
            $user &&
            $user['VaiTro'] === 'admin' &&
            $user['TrangThai'] === 'hoat_dong'
        ) {
            $isAdmin = true;
        }
    } catch (Throwable $e) {
        $isAdmin = false;
    }
}

/* =========================
   NẾU KHÔNG PHẢI ADMIN
========================= */
if (!$isAdmin) {
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Không có quyền truy cập</title>

    <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
        * {
            font-family: "Times New Roman", Times, serif !important;
        }

        body {
            background: #f5f7fa;
            margin: 0;
        }

        .denied {
            width: 90%;
            max-width: 600px;
            margin: 120px auto;
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .denied i {
            font-size: 55px;
            color: #d9534f;
            margin-bottom: 20px;
        }

        .denied h1 {
            color: #333;
        }

        .denied p {
            color: #666;
            font-size: 17px;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #222;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .btn:hover {
            background: #444;
        }
    </style>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<div class="denied">
    <i class="fa-solid fa-lock"></i>

    <h1>Bạn không có quyền truy cập</h1>

    <p>
        Trang danh sách người đăng ký chỉ dành cho quản trị viên.
    </p>

    <a href="sukien.php" class="btn">
        <i class="fa-solid fa-arrow-left"></i>
        Quay lại
    </a>
</div>

</body>
</html>

<?php
    exit;
}


/* =========================
   LẤY ID SỰ KIỆN
========================= */

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($eventId <= 0) {
    die('Mã sự kiện không hợp lệ.');
}


/* =========================
   LẤY THÔNG TIN SỰ KIỆN
========================= */

$stmt = $pdo->prepare("
    SELECT
        s.ID,
        s.TenSuKien,
        s.ThoiGianBatDau,
        s.ThoiGianKetThuc,
        s.DiaDiem,
        s.SoLuongToiDa,
        d.TenDanhMuc
    FROM sukien s
    LEFT JOIN danhmuc d
        ON d.ID = s.ID_DanhMuc
    WHERE s.ID = ?
");

$stmt->execute([$eventId]);

$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die('Không tìm thấy sự kiện.');
}


/* =========================
   LẤY DANH SÁCH NGƯỜI ĐĂNG KÝ
========================= */

$stmt = $pdo->prepare("
    SELECT
        dk.ID,
        dk.ID_NguoiDung,
        dk.ID_LoaiVe,
        dk.NgayDangKy,
        dk.TrangThai,
        dk.MaVe,
        dk.SoLuong,
        dk.DonGia,
        dk.ThanhTien,
        dk.TrangThaiCheckIn,

        nd.HoTen,
        nd.Email,
        nd.SoDienThoai,

        lv.TenLoaiVe

    FROM dangkysukien dk

    INNER JOIN nguoidung nd
        ON nd.ID = dk.ID_NguoiDung

    LEFT JOIN loaive lv
        ON lv.ID = dk.ID_LoaiVe

    WHERE dk.ID_SuKien = ?

    ORDER BY dk.NgayDangKy DESC
");

$stmt->execute([$eventId]);

$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   THỐNG KÊ
========================= */

$totalPeople = 0;
$totalMoney = 0;

foreach ($registrations as $r) {
    if ($r['TrangThai'] === 'da_dang_ky') {
        $totalPeople += (int)$r['SoLuong'];
        $totalMoney += (float)$r['ThanhTien'];
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Danh sách người đăng ký</title>

<link rel="stylesheet" href="../../assets/css/style.css">

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet"
          href="qlchitietsukien.css">
</head>

<body>

<?php include __DIR__ . '/../../components/top.php'; ?>

<?php include ROOT_PATH . '/components/header.php'; ?>


<main class="registration-page">

    <!-- ===== HEADER ===== -->

    <div class="page-header">

        <div>
            <h1>
                <i class="fa-solid fa-users"></i>
                Danh sách người đăng ký
            </h1>

            <p>
                Theo dỗi người tham gia sự kiện
            </p>
        </div>

        <a href="Qlsukien.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i>
            Quay lại đăng và cập nhật sự kiện
        </a>

    </div>


    <!-- ===== THÔNG TIN SỰ KIỆN ===== -->

    <div class="event-info">

        <h2>
            <?= htmlspecialchars($event['TenSuKien']) ?>
        </h2>

        <div class="info-grid">

            <div class="info-item">
                <strong>Mã sự kiện</strong>
                <span>
                    <?= $event['ID'] ?>
                </span>
            </div>

            <div class="info-item">
                <strong>Danh mục</strong>
                <span>
                    <?= htmlspecialchars($event['TenDanhMuc'] ?? 'Chưa có') ?>
                </span>
            </div>

            <div class="info-item">
                <strong>Thời gian</strong>
                <span>
                    <?= date('d/m/Y H:i', strtotime($event['ThoiGianBatDau'])) ?>

                    <?php if (!empty($event['ThoiGianKetThuc'])): ?>
                        -
                        <?= date('d/m/Y H:i', strtotime($event['ThoiGianKetThuc'])) ?>
                    <?php endif; ?>
                </span>
            </div>

            <div class="info-item">
                <strong>Địa điểm</strong>
                <span>
                    <?= htmlspecialchars($event['DiaDiem']) ?>
                </span>
            </div>

            <div class="info-item">
                <strong>Số lượng tối đa</strong>
                <span>
                    <?= number_format($event['SoLuongToiDa']) ?> người
                </span>
            </div>

            <div class="info-item">
                <strong>Đã đăng ký</strong>
                <span>
                    <?= number_format($totalPeople) ?> người
                </span>
            </div>

        </div>

    </div>


    <!-- ===== THỐNG KÊ ===== -->

    <div class="stats">

        <div class="stat-box">

            <i class="fa-solid fa-users"></i>

            <div class="number">
                <?= number_format($totalPeople) ?>
            </div>

            <div class="label">
                Người đăng ký
            </div>

        </div>


        <div class="stat-box">

            <i class="fa-solid fa-ticket"></i>

            <div class="number">
                <?= count($registrations) ?>
            </div>

            <div class="label">
                Lượt đăng ký
            </div>

        </div>


        <div class="stat-box">

            <i class="fa-solid fa-money-bill-wave"></i>

            <div class="number">
                <?= number_format($totalMoney, 0, ',', '.') ?> đ
            </div>

            <div class="label">
                Tổng tiền đăng ký
            </div>

        </div>

    </div>


    <!-- ===== DANH SÁCH ===== -->

    <div class="table-box">

        <h2>
            <i class="fa-solid fa-list"></i>
            Danh sách người đăng ký
        </h2>

        <?php if (count($registrations) > 0): ?>

        <table>

            <thead>

            <tr>

                <th>STT</th>

                <th>Họ tên</th>

                <th>Email</th>

                <th>Số điện thoại</th>

                <th>Loại vé</th>

                <th>Số lượng</th>

                <th>Ngày đăng ký</th>

                <th>Thành tiền</th>

                <th>Trạng thái</th>

                <th>Check-in</th>

            </tr>

            </thead>

            <tbody>

            <?php foreach ($registrations as $index => $r): ?>

            <tr>

                <td>
                    <?= $index + 1 ?>
                </td>

                <td>
                    <strong>
                        <?= htmlspecialchars($r['HoTen']) ?>
                    </strong>
                </td>

                <td>
                    <?= htmlspecialchars($r['Email']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($r['SoDienThoai'] ?? 'Chưa có') ?>
                </td>

                <td>
                    <?= htmlspecialchars($r['TenLoaiVe'] ?? 'Không xác định') ?>
                </td>

                <td>
                    <?= number_format($r['SoLuong']) ?>
                </td>

                <td>
                    <?= date('d/m/Y H:i', strtotime($r['NgayDangKy'])) ?>
                </td>

                <td class="money">
                    <?= number_format($r['ThanhTien'], 0, ',', '.') ?> đ
                </td>

                <td>

                    <?php if ($r['TrangThai'] === 'da_dang_ky'): ?>

                        <span class="status status-active">
                            Đã đăng ký
                        </span>

                    <?php elseif ($r['TrangThai'] === 'da_huy'): ?>

                        <span class="status status-cancel">
                            Đã hủy
                        </span>

                    <?php else: ?>

                        <span class="status status-payment">
                            Chờ thanh toán
                        </span>

                    <?php endif; ?>

                </td>

                <td>

                    <?php if ($r['TrangThaiCheckIn'] === 'da_checkin'): ?>

                        <span class="checkin checkin-done">
                            Đã check-in
                        </span>

                    <?php else: ?>

                        <span class="checkin checkin-not">
                            Chưa check-in
                        </span>

                    <?php endif; ?>

                </td>

            </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

        <?php else: ?>

            <div class="empty">

                <i class="fa-solid fa-user-slash"></i>

                <h3>Chưa có người đăng ký</h3>

                <p>
                    Hiện tại chưa có người đăng ký sự kiện này.
                </p>

            </div>

        <?php endif; ?>

    </div>

</main>


<?php include ROOT_PATH . '/components/footer.php'; ?>
<script src="qlchitietsukien.js"></script>
</body>
</html>