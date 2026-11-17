
<?php
require_once __DIR__ . '/../../includes/auth.php';

// Bắt buộc đăng nhập
requireLogin();

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/taikhoan/ve_cua_toi.php');
    exit;
}

// Kiểm tra CSRF
if (!csrfValid()) {
    die('Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.');
}

$userId = (int) $_SESSION['user_id'];

$bookingId = (int) ($_POST['ID_DangKy'] ?? 0);
$paymentId = (int) ($_POST['ID_ThanhToan'] ?? 0);
$result = $_POST['ket_qua'] ?? '';

$db = getDB();

try {
    // 1. Kiểm tra dữ liệu đầu vào
    if ($bookingId <= 0 || $paymentId <= 0) {
        throw new Exception('Thông tin đơn hàng không hợp lệ.');
    }

    if (!in_array($result, ['thanh_cong', 'that_bai'], true)) {
        throw new Exception('Kết quả thanh toán không hợp lệ.');
    }

    $db->beginTransaction();

    // 2. Xác định sự kiện của đơn hàng
    $stmt = $db->prepare("
        SELECT ID_SuKien
        FROM dangkysukien
        WHERE ID = ?
          AND ID_NguoiDung = ?
    ");
    $stmt->execute([$bookingId, $userId]);

    $eventId = $stmt->fetchColumn();

    if (!$eventId) {
        throw new Exception('Không tìm thấy đơn hàng.');
    }

    // 3. Khóa sự kiện để tránh xác nhận vượt số lượng
    $stmt = $db->prepare("
        SELECT ID, ThoiGianBatDau, SoLuongToiDa, TrangThai
        FROM sukien
        WHERE ID = ?
        FOR UPDATE
    ");
    $stmt->execute([(int) $eventId]);

    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        throw new Exception('Không tìm thấy sự kiện.');
    }

    // 4. Khóa đăng ký và giao dịch thanh toán
    $stmt = $db->prepare("
        SELECT
            ds.ID,
            ds.ID_NguoiDung,
            ds.ID_SuKien,
            ds.ID_LoaiVe,
            ds.SoLuong,
            ds.ThanhTien,
            ds.TrangThai AS TrangThaiDangKy,

            tt.ID AS ID_ThanhToan,
            tt.SoTien,
            tt.TrangThai AS TrangThaiThanhToan

        FROM dangkysukien ds

        INNER JOIN thanhtoan tt
            ON tt.ID_DangKy = ds.ID

        WHERE ds.ID = ?
          AND ds.ID_NguoiDung = ?
          AND tt.ID = ?

        FOR UPDATE
    ");

    $stmt->execute([
        $bookingId,
        $userId,
        $paymentId
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Không tìm thấy giao dịch thanh toán.');
    }

    // 5. Chỉ xử lý giao dịch đang chờ
    if (
        $order['TrangThaiDangKy'] !== 'cho_thanh_toan' ||
        $order['TrangThaiThanhToan'] !== 'cho_thanh_toan'
    ) {
        throw new Exception('Đơn hàng đã được xử lý hoặc không còn chờ thanh toán.');
    }

    // 6. Kiểm tra số tiền
    if (
        (float) $order['SoTien'] <= 0 ||
        (float) $order['SoTien'] != (float) $order['ThanhTien']
    ) {
        throw new Exception('Số tiền thanh toán không hợp lệ.');
    }

    // 7. Xử lý thanh toán thành công
    if ($result === 'thanh_cong') {

        // Kiểm tra sự kiện còn nhận đăng ký
        if (in_array($event['TrangThai'], ['da_huy', 'da_ket_thuc'], true)) {
            throw new Exception('Sự kiện không còn nhận đăng ký.');
        }

        if (strtotime($event['ThoiGianBatDau']) <= time()) {
            throw new Exception('Sự kiện đã bắt đầu, không thể xác nhận vé.');
        }

        // Kiểm tra loại vé
        $ticketId = (int) $order['ID_LoaiVe'];
        $quantity = (int) $order['SoLuong'];

        if ($ticketId <= 0) {
            throw new Exception('Đơn hàng không có loại vé hợp lệ.');
        }

        $stmt = $db->prepare("
            SELECT ID, SoLuongToiDa
            FROM loaive
            WHERE ID = ?
              AND ID_SuKien = ?
              AND TrangThai = 1
            FOR UPDATE
        ");

        $stmt->execute([
            $ticketId,
            $order['ID_SuKien']
        ]);

        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            throw new Exception('Loại vé đã ngừng bán hoặc không còn hợp lệ.');
        }

        // Kiểm tra số vé loại này đã được xác nhận
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(SoLuong), 0)
            FROM dangkysukien
            WHERE ID_LoaiVe = ?
              AND TrangThai = 'da_dang_ky'
        ");

        $stmt->execute([$ticketId]);

        $soldTicket = (int) $stmt->fetchColumn();
        $ticketLimit = (int) $ticket['SoLuongToiDa'];

        if (
            $ticketLimit > 0 &&
            $soldTicket + $quantity > $ticketLimit
        ) {
            throw new Exception('Loại vé không còn đủ số lượng để xác nhận thanh toán.');
        }

        // Kiểm tra sức chứa toàn sự kiện
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(SoLuong), 0)
            FROM dangkysukien
            WHERE ID_SuKien = ?
              AND TrangThai = 'da_dang_ky'
        ");

        $stmt->execute([$order['ID_SuKien']]);

        $soldEvent = (int) $stmt->fetchColumn();
        $eventLimit = (int) $event['SoLuongToiDa'];

        if (
            $eventLimit > 0 &&
            $soldEvent + $quantity > $eventLimit
        ) {
            throw new Exception('Sự kiện không còn đủ chỗ để xác nhận thanh toán.');
        }

        // Tạo mã giao dịch mô phỏng
        $transactionCode = 'MOCK-' . strtoupper(bin2hex(random_bytes(8)));

        // Cập nhật giao dịch thanh toán
        $stmt = $db->prepare("
            UPDATE thanhtoan
            SET
                TrangThai = 'thanh_cong',
                MaGiaoDich = ?,
                NgayThanhToan = NOW()
            WHERE ID = ?
        ");

        $stmt->execute([
            $transactionCode,
            $paymentId
        ]);

        // Xác nhận đăng ký
        $stmt = $db->prepare("
            UPDATE dangkysukien
            SET TrangThai = 'da_dang_ky'
            WHERE ID = ?
              AND ID_NguoiDung = ?
        ");

        $stmt->execute([
            $bookingId,
            $userId
        ]);

        $success = true;

    } else {

        // 8. Xử lý thanh toán thất bại

        $stmt = $db->prepare("
            UPDATE thanhtoan
            SET TrangThai = 'that_bai'
            WHERE ID = ?
        ");

        $stmt->execute([$paymentId]);

        // Hủy đăng ký để giải phóng số lượng vé
        $stmt = $db->prepare("
            UPDATE dangkysukien
            SET TrangThai = 'da_huy'
            WHERE ID = ?
              AND ID_NguoiDung = ?
        ");

        $stmt->execute([
            $bookingId,
            $userId
        ]);

        $success = false;
    }

    // 9. Hoàn tất transaction
    $db->commit();

    // 10. Chuyển về Vé của tôi
    if ($success) {
        header(
            'Location: ' . BASE_URL .
            '/pages/taikhoan/ve_cua_toi.php?payment_success=1'
        );
    } else {
        header(
            'Location: ' . BASE_URL .
            '/pages/taikhoan/ve_cua_toi.php?payment_failed=1'
        );
    }

    exit;

} catch (Throwable $e) {

    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Thanh toán mô phỏng: ' . $e->getMessage());

    $error = urlencode($e->getMessage());

    header(
        'Location: ' . BASE_URL .
        '/pages/taikhoan/ve_cua_toi.php?error=' . $error
    );

    exit;
}
