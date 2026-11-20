<?php
function layIP(): string {
    // Chỉ tin REMOTE_ADDR (X-Forwarded-For có thể bị giả mạo).
    // Chạy localhost/XAMPP sẽ thấy ::1 hoặc 127.0.0.1, đó là bình thường.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ghiTruyCap(PDO $conn): void {
    // Chỉ lấy đường dẫn, KHÔNG lấy query string (tránh lưu token, mã xác thực...)
    $trang = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    try {
        $st = $conn->prepare(
            "INSERT INTO nhatky_truycap (ID_NguoiDung, DiaChiIP, Trang, PhuongThuc, TrinhDuyet)
             VALUES (?, ?, ?, ?, ?)"
        );
        //mb_substr dung de cat chuoi
        $st->execute([
            $_SESSION['user_id'] ?? null,
            layIP(),
            mb_substr($trang, 0, 255),
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
            error_log("Lỗi ghi nhật ký truy cập: " . $e->getMessage());
    }
}

function ghiHoatDong(PDO $conn, int $uid, string $hanhDong, ?string $moTa = null): void {
    try {
        $conn->prepare(
            "INSERT INTO nhatky_hoatdong (ID_NguoiDung, HanhDong, MoTa, DiaChiIP) VALUES (?,?,?,?)"
        )->execute([$uid, $hanhDong, $moTa, layIP()]);
    } catch (Throwable $e) {}
}