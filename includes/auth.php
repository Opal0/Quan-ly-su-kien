<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ---------- CSRF ---------- */
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrfField(): string {
    return '<input type="hidden" name="csrf" value="' . csrfToken() . '">';
}
function csrfValid(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/* ---------- Trang thai dang nhap ----------
   Session dung khoa phang: user_id, user_name, user_email, user_role */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role']  ?? 'user',
    ];
}
function currentRole(): string {
    return isLoggedIn() ? ($_SESSION['user_role'] ?? 'user') : 'guest';   // guest | user | organizer | admin
}
function isAdmin(): bool {
    return currentRole() === 'admin';
}

function loginUser(array $row): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)($row['ID'] ?? 0);
    $_SESSION['user_name']  = $row['HoTen']  ?? '';
    $_SESSION['user_email'] = $row['Email']  ?? '';
    $_SESSION['user_role']  = $row['VaiTro'] ?? 'user';
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------- Chan truy cap ---------- */
function safeRedirect(?string $url): string {
    // Chi cho phep redirect noi bo (bat dau bang BASE_URL)
    if ($url && strpos($url, BASE_URL . '/') === 0 && strpos($url, '//') !== 0) {
        return $url;
    }
    return BASE_URL . '/index.php';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/pages/taikhoan/dangnhap.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    // Kiem tra tai khoan co bi khoa / xoa trong luc dang dang nhap khong
    $st = getDB()->prepare('SELECT TrangThai, VaiTro FROM nguoidung WHERE ID = ?');
    $st->execute([$_SESSION['user_id']]);
    $u = $st->fetch();
    if (!$u || $u['TrangThai'] !== 'hoat_dong') {
        logoutUser();
        header('Location: ' . BASE_URL . '/pages/taikhoan/dangnhap.php');
        exit;
    }
    $_SESSION['user_role'] = $u['VaiTro']; // dong bo neu admin doi vai tro
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        exit('Ban khong co quyen truy cap trang nay.');
    }
}
function isOrganizer(): bool {
    return currentRole() === 'organizer';
}

function guiThongBao(PDO $db, int $userId, string $tieuDe, string $noiDung, string $loai): void {
    $db->prepare("INSERT INTO thongbao (ID_NguoiDung, TieuDe, NoiDung, LoaiThongBao) VALUES (?, ?, ?, ?)")
       ->execute([$userId, $tieuDe, $noiDung, $loai]);
}

/* Gui thong bao cho TAT CA admin dang hoat dong */
function guiThongBaoAdmin(PDO $db, string $tieuDe, string $noiDung, string $loai): void {
    $dsAdmin = $db->query("SELECT ID FROM nguoidung WHERE VaiTro = 'admin' AND TrangThai = 'hoat_dong'")
                  ->fetchAll(PDO::FETCH_COLUMN);
    foreach ($dsAdmin as $idAdmin) {
        guiThongBao($db, (int)$idAdmin, $tieuDe, $noiDung, $loai);
    }
}

function demThongBaoChuaDoc(): int {
    if (!isLoggedIn()) return 0;
    $st = getDB()->prepare("SELECT COUNT(*) FROM thongbao WHERE ID_NguoiDung = ? AND DaDoc = 0");
    $st->execute([$_SESSION['user_id']]);
    return (int)$st->fetchColumn();
}

function requireOrganizer(): void {
    requireLogin();
    if (!in_array(currentRole(), ['organizer', 'admin'], true)) {
        http_response_code(403);
        exit('Ban khong co quyen truy cap trang nay.');
    }
}
