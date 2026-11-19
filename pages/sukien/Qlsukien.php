<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Toàn bộ xử lý dữ liệu nằm ở file riêng; file này chỉ hiển thị.
require_once __DIR__ . '/Qlsukien_controller.php';

$statusLabel = [
    'upcoming'  => 'Sắp diễn ra',
    'today'     => 'Đang diễn ra',
    'past'      => 'Đã kết thúc',
    'cancelled' => 'Đã hủy',
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token']) ?>">

    <title>Quản lý sự kiện</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="Qlsukien.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <?php
    require_once __DIR__ . '/../../config/app.php';
    require_once ROOT_PATH . '/config/database.php';
    ?>
    <?php include __DIR__ . '/../../components/top.php'; ?>
    <?php include ROOT_PATH . '/components/header.php'; ?>

    <main class="main-section ql">

    <?php if ($forbidden): ?>
        <!-- Không có quyền -->
        <section class="ql-denied">
            <i class="fa-solid fa-lock"></i>
            <h1>Bạn không có quyền truy cập trang này</h1>
            <p>Trang quản lý sự kiện chỉ dành cho quản trị viên.</p>
            <?php if (!isLoggedIn()): ?>
                <a class="ql-btn" href="<?= e(ql_base() . '/pages/taikhoan/dangnhap.php') ?>">Đăng nhập</a>
            <?php else: ?>
                <a class="ql-btn ql-btn--ghost" href="sukien.php">Về trang sự kiện</a>
            <?php endif; ?>
        </section>

    <?php else: ?>

        <?php if ($dbError): ?>
            <div class="ql-alert" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i> Không tải được dữ liệu lúc này. Hãy thử lại sau.
            </div>
        <?php endif; ?>

        <!-- ===== Banner tiêu đề ===== -->
        <header class="ql-banner">
            <div class="ql-banner__art" aria-hidden="true">
                <span class="ql-vinyl"></span>
                <i class="fa-solid fa-music ql-note ql-note--1"></i>
                <i class="fa-solid fa-music ql-note ql-note--2"></i>
            </div>
            <div class="ql-banner__text">
                <h1>Đăng và cập nhật sự kiện</h1>
                <p>Theo dõi các sự kiện <span aria-hidden="true">✦</span></p>
            </div>
            <div class="ql-banner__actions">
                <a class="ql-btn ql-btn--ghost" href="sukien.php"><i class="fa-solid fa-eye"></i> Trang người dùng</a>
                <button type="button" class="ql-btn" id="qlAdd"><i class="fa-solid fa-plus"></i> Thêm sự kiện</button>
            </div>
        </header>

        <!-- ===== Form thông tin sự kiện ===== -->
        <section class="ql-card" id="qlFormCard" aria-labelledby="qlFormTitle">
            <h2 class="ql-card__title"><span aria-hidden="true">✦</span> <span id="qlFormTitle">Thông tin sự kiện</span></h2>

            <form id="qlForm" novalidate enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="0">

                <div class="ql-grid">
                    <div class="ql-field ql-field--title">
                        <label for="fTitle">Tên sự kiện <b>*</b></label>
                        <input id="fTitle" name="title" maxlength="255" placeholder="Nhập tên sự kiện..." required>
                        <small class="ql-err" data-for="title"></small>
                    </div>

                    <div class="ql-field ql-field--cat">
                        <label for="fCat">Danh mục <b>*</b></label>
                        <select id="fCat" name="category_id" required>
                            <option value="">Chọn danh mục</option>
                            <?php foreach ($cats as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="ql-err" data-for="category_id"></small>
                    </div>

                    <div class="ql-field ql-field--start">
                        <label for="fStart">Bắt đầu <b>*</b></label>
                        <input id="fStart" type="datetime-local" name="start" required>
                        <small class="ql-err" data-for="start"></small>
                    </div>

                    <div class="ql-field ql-field--end">
                        <label for="fEnd">Kết thúc <b>*</b></label>
                        <input id="fEnd" type="datetime-local" name="end" required>
                        <small class="ql-err" data-for="end"></small>
                    </div>

                    <div class="ql-field ql-field--loc">
                        <label for="fLoc">Địa điểm <b>*</b></label>
                        <input id="fLoc" name="location" maxlength="255" placeholder="Nhập địa điểm..." required>
                        <small class="ql-err" data-for="location"></small>
                    </div>

                    <div class="ql-field ql-field--cap">
                        <label for="fCap">Số lượng tối đa <b>*</b></label>
                        <input id="fCap" type="number" name="capacity" min="1" placeholder="Nhập số lượng..." required>
                        <small class="ql-hint" id="fCapHint"></small>
                        <small class="ql-err" data-for="capacity"></small>
                    </div>

                    <div class="ql-field ql-field--img">
                        <span class="ql-label">Hình ảnh sự kiện</span>
                        <label class="ql-drop" id="fDrop" for="fImg">
                            <img id="fPreview" alt="Ảnh xem trước" hidden>
                            <span class="ql-drop__hint">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                Chọn ảnh hoặc kéo thả
                                <small>JPG, PNG, WEBP · tối đa 2MB</small>
                            </span>
                        </label>
                        <input id="fImg" class="ql-sr" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                        <label class="ql-check" id="fRemoveRow" hidden>
                            <input type="checkbox" name="remove_image" value="1"> Xóa ảnh hiện tại
                        </label>
                        <small class="ql-err" data-for="image"></small>
                    </div>

                    <div class="ql-field ql-field--desc">
                        <label for="fDesc">Mô tả sự kiện</label>
                        <textarea id="fDesc" name="desc" rows="3" placeholder="Nhập mô tả sự kiện..."></textarea>
                        <small class="ql-err" data-for="desc"></small>
                    </div>
                </div>

                <div class="ql-form__foot">
                    <small class="ql-err ql-err--form" id="qlFormError" role="alert"></small>
                    <button type="button" class="ql-btn ql-btn--soft" id="qlReset"><i class="fa-solid fa-rotate-left"></i> Đặt lại</button>
                    <button type="submit" class="ql-btn" id="qlSave"><i class="fa-regular fa-floppy-disk"></i> <span>Lưu sự kiện</span></button>
                </div>
            </form>
        </section>

        <!-- ===== Danh sách ===== -->
        <section class="ql-card ql-card--list" aria-label="Danh sách sự kiện">
            <form method="get" class="ql-filter" role="search">
                <div class="ql-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <label class="ql-sr" for="qlQ">Tìm sự kiện</label>
                    <input id="qlQ" type="search" name="q" value="<?= e($q) ?>" placeholder="Tìm kiếm sự kiện...">
                </div>

                <div class="ql-select">
                    <i class="fa-solid fa-filter"></i>
                    <label class="ql-sr" for="qlTt">Trạng thái</label>
                    <select id="qlTt" name="tt" data-autosubmit>
                        <?php foreach (['all' => 'Tất cả trạng thái', 'upcoming' => 'Sắp diễn ra', 'today' => 'Đang diễn ra', 'past' => 'Đã kết thúc', 'cancelled' => 'Đã hủy'] as $k => $lb): ?>
                            <option value="<?= $k ?>" <?= $tt === $k ? 'selected' : '' ?>><?= $lb ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ql-select">
                    <i class="fa-regular fa-calendar"></i>
                    <label class="ql-sr" for="qlTg">Thời gian</label>
                    <select id="qlTg" name="tg" data-autosubmit>
                        <?php foreach (['all' => 'Tất cả thời gian', 'today' => 'Hôm nay', 'week' => '7 ngày tới', 'month' => 'Tháng này'] as $k => $lb): ?>
                            <option value="<?= $k ?>" <?= $tg === $k ? 'selected' : '' ?>><?= $lb ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($q !== '' || $tt !== 'all' || $tg !== 'all'): ?>
                    <a class="ql-btn ql-btn--soft ql-btn--sm" href="?">Xóa lọc</a>
                <?php endif; ?>
            </form>

            <div class="ql-tablewrap">
                <table class="ql-table">
                    <thead>
                        <tr>
                            <th>Mã SK</th>
                            <th>Hình ảnh</th>
                            <th>Tên sự kiện</th>
                            <th>Danh mục</th>
                            <th>Ngày tổ chức</th>
                            <th>Địa điểm</th>
                            <th>Đăng ký</th>
                            <th>Trạng thái</th>
                            <th class="ql-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$shown): ?>
                        <tr><td colspan="9" class="ql-empty">
                            <i class="fa-regular fa-calendar-xmark"></i><br>Không có sự kiện nào khớp với bộ lọc.
                        </td></tr>
                    <?php endif; ?>

                    <?php foreach ($shown as $ev):
                        $barClass = $ev['pct'] >= 100 ? 'is-full' : ($ev['pct'] >= 80 ? 'is-warn' : '');
                    ?>
                        <tr data-event="<?= e(json_encode($ev['json'], JSON_UNESCAPED_UNICODE)) ?>">
                            <td data-label="Mã SK"><b class="ql-code">SK<?= str_pad((string)$ev['id'], 3, '0', STR_PAD_LEFT) ?></b></td>
                            <td data-label="Hình ảnh">
                                <?php if ($ev['image_url']): ?>
                                    <img class="ql-thumb" src="<?= e($ev['image_url']) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="ql-thumb ql-thumb--empty"><i class="fa-regular fa-image"></i></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Tên sự kiện"><strong><?= e($ev['title']) ?></strong></td>
                            <td data-label="Danh mục"><span class="ql-tag"><?= e($ev['category'] ?: 'Chưa phân loại') ?></span></td>
                            <td data-label="Ngày tổ chức">
                                <?= date('d/m/Y', strtotime($ev['start_at'])) ?>
                                <small>(<?= date('H:i', strtotime($ev['start_at'])) ?>)</small>
                            </td>
                            <td data-label="Địa điểm"><?= e($ev['location']) ?></td>
                            <td data-label="Đăng ký">
                                <b><?= $ev['registered'] ?></b>/<?= $ev['capacity'] ?>
                                <div class="ql-bar <?= $barClass ?>" title="<?= $ev['pct'] ?>%"><span style="width:<?= $ev['pct'] ?>%"></span></div>
                            </td>
                            <td data-label="Trạng thái">
                                <span class="ql-badge ql-badge--<?= $ev['status'] ?>"><?= $statusLabel[$ev['status']] ?></span>
                            </td>
                            <td class="ql-center ql-actions">
                                <button type="button" class="ql-act ql-act--edit js-edit" aria-label="Sửa <?= e($ev['title']) ?>">
                                    <i class="fa-solid fa-pen"></i> Sửa
                                </button>
                                <a class="ql-act ql-act--users" title="Danh sách người đăng ký" aria-label="Người đăng ký <?= e($ev['title']) ?>"
                                   href="qlchitietsukien.php?id=<?= $ev['id'] ?>"><i class="fa-solid fa-users"></i></a>
                                <button type="button" class="ql-act ql-act--del js-delete" aria-label="Xóa <?= e($ev['title']) ?>"
                                        data-id="<?= $ev['id'] ?>" data-title="<?= e($ev['title']) ?>" data-registered="<?= $ev['registered'] ?>">
                                    <i class="fa-solid fa-trash"></i> Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="ql-count">Hiển thị <?= count($shown) ?> trên <?= $total ?> sự kiện</p>

            <?php if ($pages > 1): ?>
            <nav class="ql-pager" aria-label="Phân trang">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="<?= ql_url(['trang' => $i]) ?>" class="<?= $i === $page ? 'is-active' : '' ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        </section>

        <!-- Thông báo nhanh -->
        <div class="ql-toasts" id="qlToasts" aria-live="polite"></div>

    <?php endif; ?>

    </main>

    <?php include ROOT_PATH . '/components/footer.php'; ?>
    <script src="../../assets/js/components.js"></script>
    <?php if (!$forbidden): ?>
    <script src="Qlsukien.js"></script>
    <?php endif; ?>

</body>
</html>