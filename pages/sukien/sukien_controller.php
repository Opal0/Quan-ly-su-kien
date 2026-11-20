<?php
require_once __DIR__ . '/../../includes/auth.php';

class SukienController
{
    private const STATUS_CANCELLED = 'da_huy';
    private const STATUS_FINISHED  = 'da_ket_thuc';
    private const REG_ACTIVE       = 'da_dang_ky';
    private const UPLOAD_SUBDIR    = 'assets/uploads/sukien/';
    private const PER_PAGE         = 8;
    private const MONTHS           = ['', 'Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10', 'Th11', 'Th12'];

    private ?PDO $pdo = null;
    private bool $dbError = false;
    private bool $isLoggedIn;
    private array $events = [];
    private array $myRegs = [];

    public function __construct()
    {
        $this->isLoggedIn = isLoggedIn();
    }

    public static function url(array $override = []): string
    {
        $query = array_filter(
            array_merge($_GET, $override),
            fn($v) => $v !== '' && $v !== null
        );
        return '?' . http_build_query($query);
    }

    public static function imageUrl(?string $img): string
    {
        if (!$img) {
            return '';
        }
        if (preg_match('#^(https?:)?//#', $img)) {
            return $img;
        }
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '../..';
        return $base . '/' . (strpos($img, '/') !== false ? ltrim($img, '/') : self::UPLOAD_SUBDIR . $img);
    }

    public function handle(): array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $this->rejectPost();
        $this->loadEvents();
        $this->normalizeEvents();
        $this->markRegisteredByMe();

        $q   = trim($_GET['q'] ?? '');
        $cat = $_GET['loai'] ?? '';
        $tt  = $_GET['tt'] ?? 'upcoming';

        $list  = $this->filter($q, $cat, $tt);
        $total = count($list);
        $pages = max(1, (int)ceil($total / self::PER_PAGE));
        $page  = min(max(1, (int)($_GET['trang'] ?? 1)), $pages);

        return [
            'events'           => $this->events,
            'cats'             => array_values(array_unique(array_column($this->events, 'category'))),
            'shown'            => array_slice($list, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'myRegs'           => $this->myRegs,
            'dbError'          => $this->dbError,
            'isLoggedIn'       => $this->isLoggedIn,
            'thang'            => self::MONTHS,
            'flash'            => $flash,
            'q'                => $q,
            'cat'              => $cat,
            'tt'               => $tt,
            'total'            => $total,
            'pages'            => $pages,
            'page'             => $page,
            'featured'         => $this->featuredEvent(),
            'stats'            => $this->stats(),
            'showManageEvent'  => $this->canManageEvents(),
        ];
    }

    private function rejectPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Hãy bấm "Chọn sự kiện / Đặt vé" để đăng ký tham gia.'];
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#danh-sach');
        exit;
    }

    private function loadEvents(): void
    {
        try {
            $this->pdo = getDB();
            $sql = "SELECT s.ID             AS id,
                           s.TenSuKien      AS title,
                           COALESCE(d.TenDanhMuc, 'Khác') AS category,
                           s.ThoiGianBatDau AS start_at,
                           s.ThoiGianKetThuc AS end_at,
                           s.DiaDiem        AS location,
                           s.MoTa           AS `desc`,
                           s.AnhSuKien      AS image,
                           s.SoLuongToiDa   AS capacity,
                           s.TrangThai      AS db_status,
                           (SELECT COUNT(*) FROM dangkysukien r
                             WHERE r.ID_SuKien = s.ID AND r.TrangThai = '" . self::REG_ACTIVE . "') AS registered
                    FROM sukien s
                    LEFT JOIN danhmuc d ON d.ID = s.ID_DanhMuc
                    WHERE s.TrangThai <> '" . self::STATUS_CANCELLED . "'
                      AND (d.ID IS NULL OR d.TrangThai = 'hoat_dong')
                    ORDER BY s.ThoiGianBatDau";
            $this->events = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $ex) {
            error_log('SukienController: ' . $ex->getMessage());
            $this->events  = [];
            $this->dbError = true;
        }
    }

    private function normalizeEvents(): void
    {
        $today = date('Y-m-d');
        $this->events = array_map(fn($ev) => $this->normalize($ev, $today), $this->events);
    }

    private function normalize(array $ev, string $today): array
    {
        $ev['id']         = (int)$ev['id'];
        $ev['capacity']   = (int)$ev['capacity'];
        $ev['registered'] = (int)$ev['registered'];
        $ev['desc']       = (string)$ev['desc'];
        $ev['image_url']  = self::imageUrl($ev['image'] ?? '');

        $startTs   = strtotime((string)$ev['start_at']);
        $endTs     = $ev['end_at'] ? strtotime((string)$ev['end_at']) : null;
        $startDate = date('Y-m-d', $startTs);
        $endDate   = $endTs ? date('Y-m-d', $endTs) : $startDate;

        $ev['date'] = $startDate;
        $ev['time'] = date('H:i', $startTs);
        if ($endTs) {
            $ev['time'] .= ' - ' . ($endDate === $startDate ? date('H:i', $endTs) : date('d/m H:i', $endTs));
        }

        if ($ev['db_status'] === self::STATUS_FINISHED || $endDate < $today) {
            $ev['status'] = 'past';
        } elseif ($startDate <= $today) {
            $ev['status'] = 'today';
        } else {
            $ev['status'] = 'upcoming';
        }

        $ev['full']             = $ev['capacity'] > 0 && $ev['registered'] >= $ev['capacity'];
        $ev['registered_by_me'] = false;

        return $ev;
    }

    private function markRegisteredByMe(): void
    {
        if (!$this->isLoggedIn || $this->dbError) {
            return;
        }

        try {
            $st = $this->pdo->prepare(
                "SELECT ID_SuKien FROM dangkysukien WHERE ID_NguoiDung = ? AND TrangThai = '" . self::REG_ACTIVE . "'"
            );
            $st->execute([currentUser()['id']]);
            $mine = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

            foreach ($this->events as &$ev) {
                if (in_array($ev['id'], $mine, true)) {
                    $ev['registered_by_me'] = true;
                    $this->myRegs[$ev['id']] = '';
                }
            }
            unset($ev);
        } catch (Throwable $ex) {
            error_log('SukienController (myRegs): ' . $ex->getMessage());
        }
    }

    private function filter(string $q, string $cat, string $tt): array
    {
        $list = array_filter($this->events, function ($ev) use ($q, $cat, $tt) {
            if ($tt === 'upcoming' && $ev['status'] === 'past') {
                return false;
            }
            if ($tt === 'past' && $ev['status'] !== 'past') {
                return false;
            }
            if ($cat !== '' && $ev['category'] !== $cat) {
                return false;
            }
            if ($q !== '' && mb_stripos($ev['title'] . ' ' . $ev['location'] . ' ' . $ev['desc'], $q) === false) {
                return false;
            }
            return true;
        });

        usort($list, fn($a, $b) => $tt === 'past' ? strcmp($b['date'], $a['date']) : strcmp($a['date'], $b['date']));

        return $list;
    }

    private function featuredEvent(): ?array
    {
        $featured = null;
        foreach ($this->events as $ev) {
            if ($ev['status'] !== 'past' && !$ev['full'] && (!$featured || $ev['date'] < $featured['date'])) {
                $featured = $ev;
            }
        }
        return $featured;
    }

    private function stats(): array
    {
        $past = count(array_filter($this->events, fn($ev) => $ev['status'] === 'past'));

        return [
            'upcoming'   => count($this->events) - $past,
            'past'       => $past,
            'registered' => array_sum(array_column($this->events, 'registered')),
        ];
    }

    private function canManageEvents(): bool
    {
        if (!$this->isLoggedIn) {
            return false;
        }

        try {
            $st = getDB()->prepare('SELECT VaiTro, TrangThai FROM nguoidung WHERE ID = ?');
            $st->execute([$_SESSION['user_id']]);
            $user = $st->fetch(PDO::FETCH_ASSOC);

            return $user
                && $user['TrangThai'] === 'hoat_dong'
                && in_array($user['VaiTro'], ['admin', 'organizer'], true);
        } catch (Throwable $ex) {
            error_log('Check event permission: ' . $ex->getMessage());
            return false;
        }
    }
}