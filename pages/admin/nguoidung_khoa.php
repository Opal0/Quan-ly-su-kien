<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/logger.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
    http_response_code(400);
    exit('Yêu cầu không hợp lệ');
}

$id      = (int)($_POST['id'] ?? 0);
$hanhDong = $_POST['hanh_dong'] ?? '';          // 'khoa' | 'mo'
$adminId = (int)$_SESSION['user_id'];
$back    = 'nguoidung_chitiet.php?id=' . $id;

if (!in_array($hanhDong, ['khoa', 'mo'], true)) {
    exit('Hành động không hợp lệ');
}
if ($id === $adminId) {
    header('Location: ' . $back . '&loi=tu_khoa'); exit;   // không cho tự khoá mình
}

$st = $conn->prepare("SELECT VaiTro FROM nguoidung WHERE ID = ?");
$st->execute([$id]);
$target = $st->fetch();
if (!$target) { http_response_code(404); exit('Không tìm thấy tài khoản'); }

if ($target['VaiTro'] === 'admin') {
    header('Location: ' . $back . '&loi=khoa_admin'); exit; // không cho khoá admin khác
}

$trangThaiMoi = $hanhDong === 'khoa' ? 'khoa' : 'hoat_dong';
$conn->prepare("UPDATE nguoidung SET TrangThai = ? WHERE ID = ?")->execute([$trangThaiMoi, $id]);

// Ghi vào lịch sử của tài khoản bị tác động
ghiHoatDong(
    $conn, $id,
    $hanhDong === 'khoa' ? 'bi_khoa' : 'duoc_mo_khoa',
    'Thực hiện bởi admin ID ' . $adminId
);

header('Location: ' . $back . '&ok=1');
exit;