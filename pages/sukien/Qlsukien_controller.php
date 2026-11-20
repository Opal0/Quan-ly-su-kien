<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once ROOT_PATH . '/config/database.php';

final class QlUrl
{
    public static function base(): string
    {
        return defined('BASE_URL') ? rtrim(BASE_URL, '/') : '../..';
    }

    public static function query(array $override = []): string
    {
        $query = array_filter(
            array_merge($_GET, $override),
            fn($v) => $v !== '' && $v !== null
        );
        return '?' . http_build_query($query);
    }
}

final class JsonResponse
{
    public static function send(int $code, array $payload): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function fail(int $code, string $message): void
    {
        self::send($code, ['ok' => false, 'message' => $message]);
    }

    public static function success(int $code, string $message): void
    {
        self::send($code, ['ok' => true, 'message' => $message]);
    }

    public static function invalid(array $errors): void
    {
        self::send(422, ['ok' => false, 'message' => 'Dữ liệu chưa hợp lệ', 'errors' => $errors]);
    }
}

final class RoleChecker
{
    public function isAdmin(): bool
    {
        return isAdmin() && $this->hasActiveRole('admin');
    }

    public function isOrganizer(): bool
    {
        return isLoggedIn() && $this->hasActiveRole('organizer');
    }

    private function hasActiveRole(string $role): bool
    {
        try {
            $st = getDB()->prepare('SELECT VaiTro, TrangThai FROM nguoidung WHERE ID = ?');
            $st->execute([$_SESSION['user_id']]);
            $user = $st->fetch(PDO::FETCH_ASSOC);

            if (!$user || $user['VaiTro'] !== $role || $user['TrangThai'] !== 'hoat_dong') {
                $_SESSION['user_role'] = $user['VaiTro'] ?? 'user';
                return false;
            }

            return true;
        } catch (Throwable $ex) {
            error_log('RoleChecker::' . $role . ': ' . $ex->getMessage());
            return false;
        }
    }
}

final class EventImageStorage
{
    private const MAX_SIZE   = 2 * 1024 * 1024;
    private const SUBDIR     = 'assets/uploads/sukien/';
    private const EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public static function url(?string $img): string
    {
        if (!$img) {
            return '';
        }
        if (preg_match('#^(https?:)?//#', $img)) {
            return $img;
        }
        return QlUrl::base() . '/' . (strpos($img, '/') !== false ? ltrim($img, '/') : self::SUBDIR . $img);
    }

    public function save(string $field, array &$errors): ?string
    {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES[$field];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->reject($errors, 'Tải ảnh lên thất bại, hãy thử lại');
        }

        if ($file['size'] > self::MAX_SIZE) {
            return $this->reject($errors, 'Ảnh tối đa 2MB');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext  = self::EXTENSIONS[$mime] ?? null;

        if (!$ext) {
            return $this->reject($errors, 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP');
        }

        $dir = ROOT_PATH . '/' . self::SUBDIR;

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return $this->reject($errors, 'Không tạo được thư mục lưu ảnh');
        }

        $name = 'sk_' . bin2hex(random_bytes(8)) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
            return $this->reject($errors, 'Không lưu được ảnh');
        }

        return $name;
    }

    public function delete(?string $name): void
    {
        if (!$name || !preg_match('/^sk_[a-f0-9]{16}\.(jpg|png|webp)$/', $name)) {
            return;
        }

        $path = ROOT_PATH . '/' . self::SUBDIR . $name;

        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function reject(array &$errors, string $message): ?string
    {
        $errors['image'] = $message;
        return null;
    }
}

final class EventInput
{
    public string $title;
    public int $categoryId;
    public ?string $start;
    public ?string $end;
    public string $location;
    public $capacity;
    public string $desc;
    public array $errors = [];

    public static function fromPost(array $post, PDO $pdo): self
    {
        $input = new self();
        $input->title      = trim($post['title'] ?? '');
        $input->categoryId = (int)($post['category_id'] ?? 0);
        $input->start      = self::parseDateTime($post['start'] ?? '');
        $input->end        = self::parseDateTime($post['end'] ?? '');
        $input->location   = trim($post['location'] ?? '');
        $input->capacity   = filter_var($post['capacity'] ?? null, FILTER_VALIDATE_INT);
        $input->desc       = trim($post['desc'] ?? '');
        $input->validate($pdo);

        return $input;
    }

    private function validate(PDO $pdo): void
    {
        if ($this->title === '' || mb_strlen($this->title) > 255) {
            $this->errors['title'] = 'Tên sự kiện không được trống và tối đa 255 ký tự';
        }

        if (!$this->categoryId || !$this->categoryExists($pdo)) {
            $this->errors['category_id'] = 'Hãy chọn danh mục hợp lệ';
        }

        if (!$this->start) {
            $this->errors['start'] = 'Thời gian bắt đầu không hợp lệ';
        }

        if (!$this->end) {
            $this->errors['end'] = 'Thời gian kết thúc không hợp lệ';
        }

        if ($this->start && $this->end && $this->end < $this->start) {
            $this->errors['end'] = 'Thời gian kết thúc phải sau thời gian bắt đầu';
        }

        if ($this->location === '' || mb_strlen($this->location) > 255) {
            $this->errors['location'] = 'Địa điểm không được trống và tối đa 255 ký tự';
        }

        if ($this->capacity === false || $this->capacity < 1) {
            $this->errors['capacity'] = 'Số lượng tối đa phải là số nguyên lớn hơn 0';
        }
    }

    private function categoryExists(PDO $pdo): bool
    {
        $st = $pdo->prepare('SELECT COUNT(*) FROM danhmuc WHERE ID = ?');
        $st->execute([$this->categoryId]);

        return (int)$st->fetchColumn() > 0;
    }

    private static function parseDateTime(string $value): ?string
    {
        $value = trim(str_replace('T', ' ', $value));
        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);

        return $ts === false ? null : date('Y-m-d H:i:s', $ts);
    }
}

final class EventRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insert(EventInput $in, ?string $image, $creatorId, string $status): void
    {
        $this->pdo->prepare(
            'INSERT INTO sukien
                (TenSuKien, ID_DanhMuc, ID_NguoiTao, ThoiGianBatDau, ThoiGianKetThuc,
                 DiaDiem, MoTa, AnhSuKien, SoLuongToiDa, TrangThai)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $in->title, $in->categoryId, $creatorId, $in->start, $in->end,
            $in->location, $in->desc, $image, $in->capacity, $status,
        ]);
    }

    public function update(int $id, EventInput $in, ?string $image): void
    {
        $this->pdo->prepare(
            'UPDATE sukien SET
                TenSuKien = ?, ID_DanhMuc = ?, ThoiGianBatDau = ?, ThoiGianKetThuc = ?,
                DiaDiem = ?, MoTa = ?, AnhSuKien = ?, SoLuongToiDa = ?
             WHERE ID = ?'
        )->execute([
            $in->title, $in->categoryId, $in->start, $in->end,
            $in->location, $in->desc, $image, $in->capacity, $id,
        ]);
    }

    public function lockForUpdate(int $id, ?int $ownerId): ?array
    {
        if ($ownerId === null) {
            $st = $this->pdo->prepare('SELECT AnhSuKien FROM sukien WHERE ID = ? FOR UPDATE');
            $st->execute([$id]);
        } else {
            $st = $this->pdo->prepare('SELECT AnhSuKien FROM sukien WHERE ID = ? AND ID_NguoiTao = ? FOR UPDATE');
            $st->execute([$id, $ownerId]);
        }

        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT TenSuKien, TrangThai, AnhSuKien FROM sukien WHERE ID = ?');
        $st->execute([$id]);

        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function countActiveRegistrations(int $id): int
    {
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM dangkysukien WHERE ID_SuKien = ? AND TrangThai = 'da_dang_ky'");
        $st->execute([$id]);

        return (int)$st->fetchColumn();
    }

    public function countAllRegistrations(int $id): int
    {
        $st = $this->pdo->prepare('SELECT COUNT(*) FROM dangkysukien WHERE ID_SuKien = ?');
        $st->execute([$id]);

        return (int)$st->fetchColumn();
    }

    public function setStatus(int $id, string $status): void
    {
        $this->pdo->prepare('UPDATE sukien SET TrangThai = ? WHERE ID = ?')->execute([$status, $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM sukien WHERE ID = ?')->execute([$id]);
    }

    public function all(): array
    {
        $sql = "SELECT s.ID               AS id,
                       s.TenSuKien        AS title,
                       s.ID_DanhMuc       AS category_id,
                       d.TenDanhMuc       AS category,
                       s.ThoiGianBatDau   AS start_at,
                       s.ThoiGianKetThuc  AS end_at,
                       s.DiaDiem          AS location,
                       s.MoTa             AS `desc`,
                       s.AnhSuKien        AS image,
                       s.SoLuongToiDa     AS capacity,
                       s.TrangThai        AS db_status,
                       (SELECT COUNT(*) FROM dangkysukien r
                         WHERE r.ID_SuKien = s.ID
                         AND r.TrangThai = 'da_dang_ky') AS registered
                FROM sukien s
                LEFT JOIN danhmuc d ON d.ID = s.ID_DanhMuc
                ORDER BY s.ThoiGianBatDau DESC";

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function categories(): array
    {
        return $this->pdo->query(
            "SELECT ID AS id, TenDanhMuc AS name
             FROM danhmuc
             WHERE TrangThai = 'hoat_dong'
             ORDER BY TenDanhMuc"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}

final class EventListBuilder
{
    private const STATUS_CANCELLED = 'da_huy';
    private const STATUS_FINISHED  = 'da_ket_thuc';
    private const PER_PAGE         = 8;
    private const STATUS_FILTERS   = ['all', 'upcoming', 'today', 'past', 'cancelled'];
    private const PERIOD_FILTERS   = ['all', 'today', 'week', 'month'];

    private array $events;
    private string $today;

    public function __construct(array $events)
    {
        $this->today  = date('Y-m-d');
        $this->events = array_map(fn($ev) => $this->normalize($ev), $events);
    }

    public function build(): array
    {
        $q   = trim($_GET['q'] ?? '');
        $cat = (string)(int)($_GET['loai'] ?? 0);
        $cat = $cat === '0' ? '' : $cat;
        $tt  = $this->choice($_GET['tt'] ?? '', self::STATUS_FILTERS);
        $tg  = $this->choice($_GET['tg'] ?? '', self::PERIOD_FILTERS);

        $list  = array_values(array_filter($this->events, fn($ev) => $this->matches($ev, $q, $cat, $tt, $tg)));
        $total = count($list);
        $pages = max(1, (int)ceil($total / self::PER_PAGE));
        $page  = min(max(1, (int)($_GET['trang'] ?? 1)), $pages);

        return [
            'events' => $this->events,
            'stats'  => $this->stats(),
            'shown'  => array_slice($list, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'q'      => $q,
            'cat'    => $cat,
            'tt'     => $tt,
            'tg'     => $tg,
            'total'  => $total,
            'pages'  => $pages,
            'page'   => $page,
        ];
    }

    private function normalize(array $ev): array
    {
        $ev['id']          = (int)$ev['id'];
        $ev['category_id'] = (int)$ev['category_id'];
        $ev['capacity']    = (int)$ev['capacity'];
        $ev['registered']  = (int)$ev['registered'];
        $ev['desc']        = (string)$ev['desc'];
        $ev['pending']     = null;
        $ev['status']      = $this->resolveStatus($ev);
        $ev['image_url']   = EventImageStorage::url($ev['image']);
        $ev['start_input'] = $ev['start_at'] ? date('Y-m-d\TH:i', strtotime($ev['start_at'])) : '';
        $ev['end_input']   = $ev['end_at'] ? date('Y-m-d\TH:i', strtotime($ev['end_at'])) : '';
        $ev['pct']         = $ev['capacity'] > 0
            ? min(100, (int)round($ev['registered'] / $ev['capacity'] * 100))
            : 0;

        $ev['json'] = [
            'id'          => $ev['id'],
            'title'       => $ev['title'],
            'category_id' => $ev['category_id'],
            'start'       => $ev['start_input'],
            'end'         => $ev['end_input'],
            'location'    => $ev['location'],
            'desc'        => $ev['desc'],
            'capacity'    => $ev['capacity'],
            'registered'  => $ev['registered'],
            'image_url'   => $ev['image_url'],
        ];

        return $ev;
    }

    private function resolveStatus(array $ev): string
    {
        $startDate = substr((string)$ev['start_at'], 0, 10);
        $endDate   = $ev['end_at'] ? substr((string)$ev['end_at'], 0, 10) : $startDate;

        if ($ev['db_status'] === self::STATUS_CANCELLED) {
            return 'cancelled';
        }
        if ($ev['db_status'] === self::STATUS_FINISHED || $endDate < $this->today) {
            return 'past';
        }
        if ($startDate <= $this->today) {
            return 'today';
        }
        return 'upcoming';
    }

    private function stats(): array
    {
        $stats = ['total' => 0, 'upcoming' => 0, 'past' => 0, 'cancelled' => 0, 'registered' => 0];

        foreach ($this->events as $ev) {
            $stats['total']++;

            if ($ev['status'] === 'upcoming' || $ev['status'] === 'today') {
                $stats['upcoming']++;
            }
            if ($ev['status'] === 'past') {
                $stats['past']++;
            }
            if ($ev['status'] === 'cancelled') {
                $stats['cancelled']++;
            } else {
                $stats['registered'] += $ev['registered'];
            }
        }

        return $stats;
    }

    private function choice(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : 'all';
    }

    private function matches(array $ev, string $q, string $cat, string $tt, string $tg): bool
    {
        if ($tt !== 'all' && $ev['status'] !== $tt) {
            return false;
        }
        if (!$this->matchesPeriod(substr((string)$ev['start_at'], 0, 10), $tg)) {
            return false;
        }
        if ($cat !== '' && (string)$ev['category_id'] !== $cat) {
            return false;
        }
        if ($q !== '' && mb_stripos($ev['title'] . ' ' . $ev['location'], $q) === false) {
            return false;
        }
        return true;
    }

    private function matchesPeriod(string $date, string $period): bool
    {
        switch ($period) {
            case 'today':
                return $date === $this->today;
            case 'week':
                return $date >= $this->today && $date <= date('Y-m-d', strtotime('+7 days'));
            case 'month':
                return substr($date, 0, 7) === substr($this->today, 0, 7);
            default:
                return true;
        }
    }
}

final class QlSukienController
{
    private const STATUS_OPEN      = 'sap_dien_ra';
    private const STATUS_CANCELLED = 'da_huy';

    private RoleChecker $roles;
    private EventImageStorage $images;
    private ?PDO $pdo = null;
    private ?EventRepository $events = null;

    public function __construct()
    {
        $this->roles  = new RoleChecker();
        $this->images = new EventImageStorage();
    }

    public function handle(): array
    {
        if (!(isLoggedIn() && ($this->roles->isAdmin() || $this->roles->isOrganizer()))) {
            return $this->denied();
        }

        $this->pdo    = getDB();
        $this->events = new EventRepository($this->pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        return $this->loadView();
    }

    private function denied(): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            JsonResponse::fail(
                403,
                isLoggedIn()
                    ? 'Bạn không có quyền đăng và quản lý sự kiện.'
                    : 'Bạn cần đăng nhập để thực hiện thao tác này.'
            );
        }

        http_response_code(isLoggedIn() ? 403 : 401);

        return $this->viewState(['forbidden' => true]);
    }

    private function viewState(array $override = []): array
    {
        return array_merge([
            'forbidden' => false,
            'dbError'   => false,
            'events'    => [],
            'cats'      => [],
            'shown'     => [],
            'stats'     => ['total' => 0, 'upcoming' => 0, 'past' => 0, 'cancelled' => 0, 'registered' => 0],
            'q'         => '',
            'cat'       => '',
            'tt'        => 'all',
            'tg'        => 'all',
            'page'      => 1,
            'pages'     => 1,
            'total'     => 0,
        ], $override);
    }

    private function handlePost(): void
    {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            JsonResponse::fail(403, 'Phiên làm việc đã hết hạn. Hãy tải lại trang và thử lại.');
        }

        $action = $_POST['action'] ?? '';
        $id     = (int)($_POST['id'] ?? 0);

        try {
            if ($action === 'save') {
                $this->save($id);
            }
            if ($action === 'delete') {
                $this->delete($id);
            }
            JsonResponse::fail(400, 'Thao tác không hợp lệ.');
        } catch (PDOException $ex) {
            error_log('qlsukien_controller.php: ' . $ex->getMessage());
            JsonResponse::fail(500, 'Lỗi máy chủ, hãy thử lại sau.');
        }
    }

    private function save(int $id): void
    {
        $input = EventInput::fromPost($_POST, $this->pdo);
        if ($input->errors) {
            JsonResponse::invalid($input->errors);
        }

        $imageErrors = [];
        $newImage    = $this->images->save('image', $imageErrors);
        if ($imageErrors) {
            JsonResponse::invalid($imageErrors);
        }

        try {
            $this->pdo->beginTransaction();

            if ($id === 0) {
                $this->create($input, $newImage);
            }
            $this->update($id, $input, $newImage);
        } catch (PDOException $ex) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->images->delete($newImage);
            throw $ex;
        }
    }

    private function create(EventInput $input, ?string $image): void
    {
        $this->events->insert($input, $image, $_SESSION['user_id'], self::STATUS_OPEN);
        $this->pdo->commit();

        JsonResponse::success(201, 'Đã thêm sự kiện "' . $input->title . '".');
    }

    private function update(int $id, EventInput $input, ?string $newImage): void
    {
        $ownerId = $this->roles->isAdmin() ? null : (int)$_SESSION['user_id'];
        $row     = $this->events->lockForUpdate($id, $ownerId);

        if (!$row) {
            $this->abort($newImage);
            JsonResponse::fail(403, 'Bạn không có quyền sửa sự kiện này.');
        }

        $registered = $this->events->countActiveRegistrations($id);

        if ($input->capacity < $registered) {
            $this->abort($newImage);
            JsonResponse::invalid(['capacity' => "Đã có $registered người đăng ký, không thể đặt thấp hơn"]);
        }

        $image = $row['AnhSuKien'];

        if ($newImage !== null) {
            $image = $newImage;
        } elseif (($_POST['remove_image'] ?? '') === '1') {
            $image = null;
        }

        $this->events->update($id, $input, $image);
        $this->pdo->commit();

        if ($image !== $row['AnhSuKien']) {
            $this->images->delete($row['AnhSuKien']);
        }

        JsonResponse::success(200, 'Đã cập nhật sự kiện "' . $input->title . '".');
    }

    private function abort(?string $newImage): void
    {
        $this->pdo->rollBack();
        $this->images->delete($newImage);
    }

    private function delete(int $id): void
    {
        if (!$this->roles->isAdmin()) {
            JsonResponse::fail(403, 'Bạn không có quyền xóa sự kiện.');
        }

        $row = $this->events->find($id);

        if (!$row) {
            JsonResponse::fail(404, 'Không tìm thấy sự kiện.');
        }

        if ($this->events->countAllRegistrations($id) > 0) {
            $this->cancelInsteadOfDelete($id, $row);
        }

        $this->events->delete($id);
        $this->images->delete($row['AnhSuKien']);

        JsonResponse::success(200, 'Đã xóa sự kiện "' . $row['TenSuKien'] . '".');
    }

    private function cancelInsteadOfDelete(int $id, array $row): void
    {
        if ($row['TrangThai'] === self::STATUS_CANCELLED) {
            JsonResponse::success(200, 'Sự kiện này đã ở trạng thái "Đã hủy".');
        }

        $this->events->setStatus($id, self::STATUS_CANCELLED);

        JsonResponse::success(200, 'Sự kiện đã có đăng ký nên được chuyển sang trạng thái "Đã hủy".');
    }

    private function loadView(): array
    {
        try {
            $events = $this->events->all();
            $cats   = $this->events->categories();
        } catch (PDOException $ex) {
            error_log('qlsukien_controller.php: ' . $ex->getMessage());
            return $this->viewState(['dbError' => true]);
        }

        return $this->viewState(array_merge(
            (new EventListBuilder($events))->build(),
            ['cats' => $cats]
        ));
    }
}