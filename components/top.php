
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';
ghiTruyCap($conn);

$role = currentRole();
$user = currentUser();
$B = BASE_URL;
$dsThongBao = [];
$soChuaDoc  = 0;
if ($role !== 'guest') {
    $st = getDB()->prepare(
        "SELECT ID, TieuDe, NoiDung, LoaiThongBao, DaDoc, NgayTao
         FROM thongbao WHERE ID_NguoiDung = ?
         ORDER BY NgayTao DESC, ID DESC LIMIT 8"
    );
    $st->execute([$user['id']]);
    $dsThongBao = $st->fetchAll();
    $soChuaDoc  = demThongBaoChuaDoc();
}
?>
<link rel="stylesheet" href="<?= $B ?>/assets/css/style.css?v=<?= filemtime(ROOT_PATH . '/assets/css/style.css') ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<div class="top_container">
    <div class="top_left">
        <i class="fa-solid fa-location-dot"></i> 208/5 3/2, Phường Ninh Kiều, TP Cần Thơ |
        <i class="fa-solid fa-phone"></i> 0772474933 |
        <i class="fa-solid fa-clock"></i> 8:00 - 22:00
    </div>

    <div class="top_right">
        <div id="user-area">
            <?php if ($role === 'guest'): ?>
                <a href="<?= $B ?>/pages/taikhoan/dangky.php">
                    <i class="fa-solid fa-user-plus"></i> Đăng ký
                </a>
                <span> | </span>
                <a href="<?= $B ?>/pages/taikhoan/dangnhap.php">
                    <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
                </a>
            <?php else: ?>
                <div class="notif-menu" id="notifMenu">
                    <button type="button" class="notif-toggle" id="notifToggle"
                            aria-haspopup="true" aria-expanded="false"
                            data-csrf="<?= csrfToken() ?>"
                            data-url="<?= $B ?>/pages/taikhoan/docthongbao.php">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($soChuaDoc > 0): ?>
                            <span class="notif-badge" id="notifBadge"><?= $soChuaDoc > 9 ? '9+' : $soChuaDoc ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="notif-dropdown" id="notifDropdown" hidden>
                        <div class="notif-header">Thông báo</div>

                        <?php if (!$dsThongBao): ?>
                            <div class="notif-empty">Bạn chưa có thông báo nào.</div>
                        <?php endif; ?>

                        <?php foreach ($dsThongBao as $tb): ?>
                            <?php
                            // Thông báo được duyệt thì bấm vào dẫn sang trang đăng sự kiện
                            $href = ($tb['LoaiThongBao'] === 'organizer_duyet')
                                ? $B . '/pages/sukien/quanly.php'
                                : $B . '/pages/taikhoan/thongbao.php';
                            ?>
                            <a class="notif-item <?= !$tb['DaDoc'] ? 'unread' : '' ?>" href="<?= $href ?>">
                                <div class="notif-title"><?= e($tb['TieuDe']) ?></div>
                                <div class="notif-text"><?= e(mb_strimwidth($tb['NoiDung'], 0, 90, '...', 'UTF-8')) ?></div>
                                <div class="notif-time"><?= e(date('d/m/Y H:i', strtotime($tb['NgayTao']))) ?></div>
                            </a>
                        <?php endforeach; ?>

                        <a class="notif-all" href="<?= $B ?>/pages/taikhoan/thongbao.php">Xem tất cả</a>
                    </div>
                </div>
                <div class="user-menu" id="userMenu">
                    <button type="button"
                            class="user-menu-toggle"
                            id="userMenuToggle"
                            aria-haspopup="true"
                            aria-expanded="false">

                        <i class="fa-solid fa-circle-user"></i>
                        <?= e($user['name'] ?? 'Người dùng') ?>
                        <i class="fa-solid fa-caret-down dropdown-arrow"></i>
                    </button>

                    <div class="user-dropdown" id="userDropdown" hidden>

                        <a href="<?= $B ?>/pages/taikhoan/taikhoan.php">
                            <i class="fa-solid fa-user"></i> Tài khoản của tôi
                        </a>

                        <a href="<?= $B ?>/pages/taikhoan/doimatkhau.php">
                            <i class="fa-solid fa-key"></i> Đổi mật khẩu
                        </a>

                        <?php if ($role === 'user'): ?>
                            <a href="<?= $B ?>/pages/taikhoan/dangkylamnguoitochuc.php">
                                <i class="fa-solid fa-bullhorn"></i> Đăng ký làm người tổ chức
                            </a>

                        <?php elseif ($role === 'organizer'): ?>
                            <a href="<?= $B ?>/pages/sukien/Qlsukien.php">
                                <i class="fa-solid fa-calendar-plus"></i> Chuyển sang trang đăng sự kiện
                            </a>

                        <?php elseif ($role === 'admin'): ?>
                            <a href="<?= $B ?>/pages/admin/sukien.php">
                                <i class="fa-solid fa-gear"></i> Trang quản trị
                            </a>
                        <?php endif; ?>

                        <form method="post" action="<?= $B ?>/pages/taikhoan/dangxuat.php">
                            <?= csrfField() ?>
                            <button type="submit" class="logout-button">
                                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                            </button>
                        </form>

                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>
<script>
(function () {
    function dongThongBao() {
        var panel  = document.getElementById('notifDropdown');
        var toggle = document.getElementById('notifToggle');
        if (panel)  panel.hidden = true;
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }

    document.addEventListener('click', function (ev) {
        var toggle = ev.target.closest('#notifToggle');
        var panel  = document.getElementById('notifDropdown');
        if (!panel) return;

        if (toggle) {
            if (!panel.hidden) { dongThongBao(); return; }

            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');

            var badge = document.getElementById('notifBadge');
            if (badge) {
                fetch(toggle.dataset.url, {
                    method: 'POST',
                    body: new URLSearchParams({ csrf: toggle.dataset.csrf })
                }).then(function () { badge.remove(); }).catch(function () {});
            }
            return;
        }

        if (!ev.target.closest('#notifDropdown')) dongThongBao();
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') dongThongBao();
    });
})();
</script>

