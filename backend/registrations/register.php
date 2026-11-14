<?php
require_once __DIR__ . '/../../includes/auth.php';

// Bắt buộc đăng nhập
requireLogin();

// Chỉ xử lý POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/sukien/sukien.php');
    exit;
}

// Kiểm tra CSRF
if (!csrfValid()) {
    die('Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.');
}

$userId = (int) $_SESSION['user_id'];
$eventId = (int) ($_POST['ID_SuKien'] ?? 0);
$ticketId = (int) ($_POST['ID_LoaiVe'] ?? 0);
$quantity = (int) ($_POST['SoLuong'] ?? 1);

$db = getDB();

try {
    if ($eventId <= 0) {
        throw new Exception('Sự kiện không hợp lệ.');
    }

    if ($quantity < 1 || $quantity > 10) {
        throw new Exception('Số lượng vé phải từ 1 đến 10.');
    }

    $db->beginTransaction();

    // 1. Khóa và kiểm tra sự kiện
    $stmt = $db->prepare("
        SELECT ID, TenSuKien, ThoiGianBatDau,
               SoLuongToiDa, TrangThai
        FROM sukien
        WHERE ID = ?
        FOR UPDATE
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        throw new Exception('Không tìm thấy sự kiện.');
    }

    if (in_array($event['TrangThai'], ['da_huy', 'da_ket_thuc'], true)) {
        throw new Exception('Sự kiện không còn nhận đăng ký.');
    }

    if (strtotime($event['ThoiGianBatDau']) <= time()) {
        throw new Exception('Sự kiện đã bắt đầu, không thể đăng ký.');
    }

    // 2. Kiểm tra loại vé
    $ticket = null;

    if ($ticketId > 0) {
        $stmt = $db->prepare("
            SELECT ID, ID_SuKien, TenLoaiVe,
                   GiaVe, SoLuongToiDa
            FROM loaive
            WHERE ID = ?
              AND ID_SuKien = ?
              AND TrangThai = 1
            FOR UPDATE
        ");
        $stmt->execute([$ticketId, $eventId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            throw new Exception('Loại vé không hợp lệ hoặc đã ngừng bán.');
        }

        $unitPrice = (float) $ticket['GiaVe'];

    } else {
        // Không chọn loại vé chỉ được phép nếu sự kiện
        // chưa thiết lập loại vé đang hoạt động.
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM loaive
            WHERE ID_SuKien = ?
              AND TrangThai = 1
        ");
        $stmt->execute([$eventId]);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new Exception('Vui lòng chọn loại vé của sự kiện.');
        }

        // Đăng ký miễn phí không có loại vé
        $quantity = 1;
        $unitPrice = 0;
    }

    // 3. Kiểm tra đăng ký cũ của người dùng
    $stmt = $db->prepare("
        SELECT ID, TrangThai
        FROM dangkysukien
        WHERE ID_NguoiDung = ?
          AND ID_SuKien = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$userId, $eventId]);
    $oldBooking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($oldBooking && $oldBooking['TrangThai'] === 'da_dang_ky') {
        throw new Exception('Bạn đã đăng ký sự kiện này rồi.');
    }

    // 4. Kiểm tra số lượng còn lại của loại vé
    if ($ticket) {
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(SoLuong), 0)
            FROM dangkysukien
            WHERE ID_LoaiVe = ?
              AND TrangThai = 'da_dang_ky'
        ");
        $stmt->execute([$ticketId]);
        $soldTicket = (int) $stmt->fetchColumn();

        $ticketLimit = (int) $ticket['SoLuongToiDa'];

        if ($ticketLimit > 0 && $soldTicket + $quantity > $ticketLimit) {
            throw new Exception('Loại vé này không còn đủ số lượng.');
        }
    }

    // 5. Kiểm tra tổng sức chứa sự kiện
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(SoLuong), 0)
        FROM dangkysukien
        WHERE ID_SuKien = ?
          AND TrangThai = 'da_dang_ky'
    ");
    $stmt->execute([$eventId]);
    $soldEvent = (int) $stmt->fetchColumn();

    $eventLimit = (int) $event['SoLuongToiDa'];

    if ($eventLimit > 0 && $soldEvent + $quantity > $eventLimit) {
        throw new Exception('Sự kiện không còn đủ chỗ.');
    }

    // 6. Tính tiền tại server
    $totalPrice = $unitPrice * $quantity;
    $ticketCode = strtoupper(bin2hex(random_bytes(8)));

    // 7. Cập nhật bản ghi cũ hoặc tạo bản ghi mới
    if ($oldBooking) {
        $stmt = $db->prepare("
            UPDATE dangkysukien
            SET
                NgayDangKy = NOW(),
                TrangThai = 'da_dang_ky',
                MaVe = ?,
                ID_LoaiVe = ?,
                SoLuong = ?,
                DonGia = ?,
                ThanhTien = ?,
                TrangThaiCheckIn = 'chua_checkin',
                ThoiGianCheckIn = NULL
            WHERE ID = ?
        ");

        $stmt->execute([
            $ticketCode,
            $ticket ? $ticketId : null,
            $quantity,
            $unitPrice,
            $totalPrice,
            $oldBooking['ID']
        ]);

    } else {
        $stmt = $db->prepare("
            INSERT INTO dangkysukien (
                ID_NguoiDung,
                ID_SuKien,
                ID_LoaiVe,
                NgayDangKy,
                TrangThai,
                MaVe,
                SoLuong,
                DonGia,
                ThanhTien,
                TrangThaiCheckIn
            )
            VALUES (?, ?, ?, NOW(), 'da_dang_ky', ?, ?, ?, ?, 'chua_checkin')
        ");

        $stmt->execute([
            $userId,
            $eventId,
            $ticket ? $ticketId : null,
            $ticketCode,
            $quantity,
            $unitPrice,
            $totalPrice
        ]);
    }

    $db->commit();

    // 8. Chuyển đến trang Vé của tôi
    $result = $ticket ? 'purchased=1' : 'registered=1';

    header(
        'Location: ' . BASE_URL .
        '/pages/taikhoan/ve_cua_toi.php?' . $result
    );
    exit;

} catch (Throwable $e) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Đăng ký / đặt vé: ' . $e->getMessage());

    $error = urlencode($e->getMessage());

    if ($ticketId > 0) {
        header(
            'Location: ' . BASE_URL .
            '/pages/muave/muave.php?event_id=' . $eventId .
            '&error=' . $error
        );
    } else {
        header(
            'Location: ' . BASE_URL .
            '/pages/taikhoan/ve_cua_toi.php?error=' . $error
        );
    }

    exit;
}
