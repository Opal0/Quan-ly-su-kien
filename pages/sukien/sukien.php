<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Toàn bộ xử lý dữ liệu nằm ở file riêng; file này chỉ hiển thị.
require_once __DIR__ . '/Sukien_Controller.php';

/**
 * Nút hành động cho một sự kiện:
 *  - đã kết thúc / hết chỗ  -> nút vô hiệu
 *  - đã đăng ký             -> nhãn "Đã đăng ký" + nút hủy
 *  - còn lại                -> link sang trang đặt vé
 */
function sk_action(array $ev, bool $isLoggedIn, array $myRegs, string $label = 'Đặt vé'): string
{
    if ($ev['registered_by_me'] ?? false) {
        $ve = $myRegs[$ev['id']] ?? '';
        return '<div class="sk-mine">'
             . '<span class="sk-state sk-state--live"><i class="fa-solid fa-ticket"></i> Đã đăng ký'
             . ($ve !== '' ? ' · Mã vé ' . e($ve) : '') . '</span>'
             . '<form method="post" action="" class="sk-inline" onsubmit="return confirm(\'Bạn chắc chắn muốn hủy đăng ký sự kiện này?\');">'
             . '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">'
             . '<input type="hidden" name="event_id" value="' . (int)$ev['id'] . '">'
             . '<input type="hidden" name="action" value="cancel">'
             . '<button type="submit" class="sk-btn sk-btn--ghost">Hủy đăng ký</button>'
             . '</form></div>';
    }
    if ($ev['status'] === 'past' || $ev['full']) {
        return '<button class="sk-btn sk-btn--ghost" disabled>' . ($ev['status'] === 'past' ? 'Đã kết thúc' : 'Hết chỗ') . '</button>';
    }

    $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '../..';
    return '<a class="sk-btn" href="' . e($base . '/pages/muave/muave.php?event_id=' . (int)$ev['id']) . '">'
         . '<i class="fa-solid fa-ticket"></i> ' . e($label) . '</a>';
}

/**
 * Áp phích của sự kiện: ảnh thật nếu có, không thì ảnh thay thế
 * (vành đĩa than, mỗi sự kiện một sắc xanh riêng theo ID).
 */
function sk_poster(array $ev, string $extraClass = '', string $inner = ''): string
{
    $cls = trim('sk-poster ' . $extraClass);
    if (!empty($ev['image_url'])) {
        return '<div class="' . $cls . '"><img src="' . e($ev['image_url']) . '" alt="' . e($ev['title']) . '" loading="lazy">' . $inner . '</div>';
    }
    $hue = 190 + (($ev['id'] * 37) % 90);
    return '<div class="' . $cls . ' sk-poster--blank" style="--h:' . $hue . '" role="img" aria-label="' . e($ev['title']) . '">' . $inner . '</div>';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sự kiện</title>

    <link rel="stylesheet" href="../../assets/css/style.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&display=swap&subset=vietnamese">

    <!-- Nhúng thẳng sukien.css (không phụ thuộc đường dẫn / cache) -->
    <style><?php readfile(__DIR__ . '/sukien.css'); ?></style>
</head>

<body>

    <?php
    require_once __DIR__ . '/../../config/app.php';
    require_once ROOT_PATH . '/config/database.php';
    ?>
    <?php include __DIR__ . '/../../components/top.php'; ?>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <!-- Nội dung trang sự kiện -->
    <main class="main-section sk">

        <?php if ($flash): ?>
            <div class="sk-flash sk-flash--<?= e($flash['type']) ?>" role="status">
                <i class="fa-solid <?= $flash['type'] === 'ok' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <?php if ($dbError): ?>
            <div class="sk-flash sk-flash--error" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                Không tải được danh sách sự kiện lúc này. Hãy thử lại sau.
            </div>
        <?php endif; ?>

        <!-- Tiêu đề + số liệu -->
        <header class="sk-head">
            <div>
                <h1>Đêm nhạc sắp tới</h1>
                <p>Chọn đêm diễn, giữ chỗ trong một phút.</p>
            </div>
            <div class="sk-head__side">
                <dl class="sk-stats">
                    <div><dt>sắp diễn ra</dt><dd><?= count(array_filter($events, fn($x) => $x['status'] !== 'past')) ?></dd></div>
                    <div><dt>đã tổ chức</dt><dd><?= count(array_filter($events, fn($x) => $x['status'] === 'past')) ?></dd></div>
                    <div><dt>lượt đăng ký</dt><dd><?= array_sum(array_column($events, 'registered')) ?></dd></div>
                </dl>
                <?php if (isAdmin()): ?>
                    <a class="sk-btn sk-btn--sm" href="Qlsukien.php"><i class="fa-solid fa-gear"></i> Đăng và cập nhật sự kiện</a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Sự kiện nổi bật -->
        <?php if ($featured): $d = strtotime($featured['date']); ?>
        <article class="sk-feature">
            <div class="sk-feature__body">
                <span class="sk-feature__flag"><i class="fa-solid fa-star"></i> Nổi bật</span>
                <h2><?= e($featured['title']) ?></h2>
                <span class="sk-tag"><?= e($featured['category']) ?></span>
                <p class="sk-meta"><i class="fa-regular fa-clock"></i> <?= e($featured['time']) ?></p>
                <p class="sk-meta"><i class="fa-solid fa-location-dot"></i> <?= e($featured['location']) ?></p>
                <?= sk_action($featured, $isLoggedIn, $myRegs, 'Đặt vé ngay') ?>
            </div>
            <?= sk_poster($featured, 'sk-feature__art',
                '<div class="sk-date"><b>' . date('d', $d) . '</b><span>' . $thang[(int)date('n', $d)] . '</span></div>') ?>
        </article>
        <?php endif; ?>

        <!-- Bộ lọc -->
        <section class="sk-filter" id="danh-sach">
            <form method="get" class="sk-search" role="search">
                <input type="hidden" name="loai" value="<?= e($cat) ?>">
                <input type="hidden" name="tt" value="<?= e($tt) ?>">
                <label class="sr-only" for="sk-q">Tìm sự kiện</label>
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="sk-q" type="search" name="q" value="<?= e($q) ?>" placeholder="Tìm theo tên, địa điểm...">
                <button class="sk-btn sk-btn--sm" type="submit">Tìm</button>
            </form>

            <div class="sk-tabs" role="tablist" aria-label="Trạng thái">
                <?php foreach (['upcoming' => 'Sắp diễn ra', 'past' => 'Đã kết thúc', 'all' => 'Tất cả'] as $k => $label): ?>
                    <a href="<?= sk_url(['tt' => $k, 'trang' => '']) ?>#danh-sach" class="<?= $tt === $k ? 'is-active' : '' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>

            <div class="sk-chips">
                <a href="<?= sk_url(['loai' => '', 'trang' => '']) ?>#danh-sach" class="<?= $cat === '' ? 'is-active' : '' ?>">Mọi loại</a>
                <?php foreach ($cats as $c): ?>
                    <a href="<?= sk_url(['loai' => $c, 'trang' => '']) ?>#danh-sach" class="<?= $cat === $c ? 'is-active' : '' ?>"><?= e($c) ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Danh sách: 4 sự kiện mỗi hàng -->
        <section class="sk-list">
            <?php if (!$shown): ?>
                <div class="sk-empty">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    <p>Không có sự kiện nào khớp với bộ lọc.</p>
                    <a class="sk-btn sk-btn--ghost" href="?">Xóa bộ lọc</a>
                </div>
            <?php endif; ?>

            <?php foreach ($shown as $ev):
                $d    = strtotime($ev['date']);
                $left = $ev['capacity'] - $ev['registered'];
                $pct  = $ev['capacity'] > 0 ? min(100, round($ev['registered'] / $ev['capacity'] * 100)) : 0;

                $badges = '';
                if     ($ev['status'] === 'past')  $badges = '<span class="sk-state">Đã kết thúc</span>';
                elseif ($ev['status'] === 'today') $badges = '<span class="sk-state sk-state--live">Diễn ra hôm nay</span>';
                elseif ($ev['full'])               $badges = '<span class="sk-state">Hết chỗ</span>';
                elseif ($left <= 10)               $badges = '<span class="sk-state sk-state--warn">Còn ' . $left . ' chỗ</span>';

                $inner = '<div class="sk-date"><b>' . date('d', $d) . '</b><span>' . $thang[(int)date('n', $d)] . '</span></div>'
                       . ($badges !== '' ? '<div class="sk-badges">' . $badges . '</div>' : '');
            ?>
            <article class="sk-card sk-card--<?= $ev['status'] ?>">
                <?= sk_poster($ev, '', $inner) ?>

                <div class="sk-card__body">
                    <span class="sk-tag"><?= e($ev['category']) ?></span>
                    <h3><?= e($ev['title']) ?></h3>
                    <p class="sk-meta"><i class="fa-regular fa-clock"></i> <?= e($ev['time']) ?>, <?= date('d/m/Y', $d) ?></p>
                    <p class="sk-meta"><i class="fa-solid fa-location-dot"></i> <?= e($ev['location']) ?></p>
                </div>

                <div class="sk-card__foot">
                    <div class="sk-bar <?= $pct >= 80 ? 'is-warn' : '' ?>" title="<?= $ev['registered'] ?>/<?= $ev['capacity'] ?> đã đăng ký">
                        <span style="width:<?= $pct ?>%"></span>
                    </div>
                    <p class="sk-bar__label"><?= $ev['registered'] ?>/<?= $ev['capacity'] ?> người đã đăng ký</p>
                    <?= sk_action($ev, $isLoggedIn, $myRegs) ?>
                </div>
            </article>
            <?php endforeach; ?>
        </section>

        <!-- Phân trang -->
        <?php if ($pages > 1): ?>
        <nav class="sk-pager" aria-label="Phân trang">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a href="<?= sk_url(['trang' => $i]) ?>#danh-sach" class="<?= $i === $page ? 'is-active' : '' ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
        </nav>
        <?php endif; ?>

    </main>

    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script src="../../assets/js/components.js"></script>
    <script><?php readfile(__DIR__ . '/sukien.js'); ?></script>
</body>
</html>