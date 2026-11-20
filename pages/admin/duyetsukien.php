<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid()) {
    $idYc     = (int)($_POST['id'] ?? 0);
    $hanhDong = $_POST['hanh_dong'] ?? '';
    $lyDo     = mb_substr(trim($_POST['ly_do'] ?? ''), 0, 255);

    if ($idYc > 0 && in_array($hanhDong, ['duyet', 'tu_choi'], true)) {
        $xoaAnh   = [];     // ảnh cần xóa SAU khi commit
        $thongBao = null;   // [idNguoiNhan, tieuDe, noiDung, loai]

        try {
            $db->beginTransaction();

            // 1. Lấy yêu cầu đang chờ duyệt (khóa dòng để tránh duyệt 2 lần)
            $st = $db->prepare("SELECT * FROM yeucau_sukien WHERE ID = ? AND TrangThai = 'cho_duyet' FOR UPDATE");
            $st->execute([$idYc]);
            $yc = $st->fetch();

            if ($yc) {
                $dl    = json_decode((string)$yc['DuLieu'], true) ?: [];
                $loai  = $yc['LoaiYeuCau'];                       // them | sua | xoa
                $ten   = (string)($dl['title'] ?? '');
                $idSk  = (int)$yc['ID_SuKien'];
                $nhan  = ['them' => 'thêm', 'sua' => 'chỉnh sửa', 'xoa' => 'xóa'][$loai];

                if ($hanhDong === 'tu_choi') {
                    // 2a. Từ chối: giữ nguyên bảng sukien, dọn ảnh đã tải lên kèm yêu cầu
                    $db->prepare("UPDATE yeucau_sukien
                                  SET TrangThai = 'tu_choi', LyDoTuChoi = ?, NgayXuLy = NOW(), ID_NguoiDuyet = ?
                                  WHERE ID = ?")
                       ->execute([$lyDo !== '' ? $lyDo : null, currentUser()['id'], $idYc]);
                    $xoaAnh[] = $dl['image'] ?? null;

                    $thongBao = [(int)$yc['ID_NguoiGui'], 'Yêu cầu sự kiện bị từ chối',
                        "Yêu cầu $nhan sự kiện \"$ten\" của bạn bị từ chối." . ($lyDo !== '' ? " Lý do: $lyDo" : ''),
                        'sukien_tuchoi'];
                    $_SESSION['ds_flash'] = ['ok' => true, 'msg' => "Đã từ chối yêu cầu $nhan sự kiện \"$ten\"."];

                } else {
                    // 2b. Duyệt: áp dụng thay đổi lên bảng sukien
                    if ($loai === 'them') {
                        $db->prepare("INSERT INTO sukien (TenSuKien, ID_DanhMuc, ThoiGianBatDau, ThoiGianKetThuc, DiaDiem, MoTa, AnhSuKien, SoLuongToiDa, TrangThai)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                           ->execute([$dl['title'], $dl['category_id'], $dl['start'], $dl['end'], $dl['location'],
                                      $dl['desc'], $dl['image'] ?? null, $dl['capacity'], 'sap_dien_ra']);   // 'sap_dien_ra': đổi nếu ENUM TrangThai của sukien khác
                        $idSk = (int)$db->lastInsertId();
                        $msg  = "Đã duyệt và đăng sự kiện \"$ten\".";

                    } else {
                        // sửa / xóa: khóa dòng sự kiện để số lượng đăng ký không đổi giữa chừng
                        $st = $db->prepare("SELECT TrangThai, AnhSuKien FROM sukien WHERE ID = ? FOR UPDATE");
                        $st->execute([$idSk]);
                        $sk = $st->fetch();
                        if (!$sk) throw new RuntimeException('Sự kiện không còn tồn tại, hãy từ chối yêu cầu này.');

                        $st = $db->prepare("SELECT COUNT(*) FROM dangkysukien WHERE ID_SuKien = ? AND TrangThai = 'da_dang_ky'");
                        $st->execute([$idSk]);
                        $daDangKy = (int)$st->fetchColumn();

                        if ($loai === 'sua') {
                            if ((int)$dl['capacity'] < $daDangKy) {
                                throw new RuntimeException("Hiện đã có $daDangKy người đăng ký, vượt số lượng tối đa được đề xuất. Hãy từ chối yêu cầu này.");
                            }
                            $anh = $sk['AnhSuKien'];
                            if (!empty($dl['image']))            $anh = $dl['image'];   // thay ảnh mới
                            elseif (!empty($dl['remove_image'])) $anh = null;           // chỉ xóa ảnh

                            $db->prepare("UPDATE sukien SET TenSuKien = ?, ID_DanhMuc = ?, ThoiGianBatDau = ?, ThoiGianKetThuc = ?,
                                                            DiaDiem = ?, MoTa = ?, AnhSuKien = ?, SoLuongToiDa = ?
                                          WHERE ID = ?")
                               ->execute([$dl['title'], $dl['category_id'], $dl['start'], $dl['end'], $dl['location'],
                                          $dl['desc'], $anh, $dl['capacity'], $idSk]);
                            if ($anh !== $sk['AnhSuKien']) $xoaAnh[] = $sk['AnhSuKien'];
                            $msg = "Đã duyệt chỉnh sửa sự kiện \"$ten\".";

                        } else {   // xoa
                            $st = $db->prepare("SELECT COUNT(*) FROM dangkysukien WHERE ID_SuKien = ?");
                            $st->execute([$idSk]);
                            if ((int)$st->fetchColumn() > 0) {   // còn lịch sử đăng ký -> chuyển "Đã hủy", không xóa cứng
                                $db->prepare("UPDATE sukien SET TrangThai = 'da_huy' WHERE ID = ?")->execute([$idSk]);
                                $msg = "Sự kiện \"$ten\" đã có đăng ký nên được chuyển sang trạng thái \"Đã hủy\".";
                            } else {
                                $db->prepare("DELETE FROM sukien WHERE ID = ?")->execute([$idSk]);
                                $xoaAnh[] = $sk['AnhSuKien'];
                                $msg = "Đã duyệt và xóa sự kiện \"$ten\".";
                            }
                        }
                    }

                    $db->prepare("UPDATE yeucau_sukien
                                  SET TrangThai = 'da_duyet', NgayXuLy = NOW(), ID_NguoiDuyet = ?, ID_SuKien = ?
                                  WHERE ID = ?")
                       ->execute([currentUser()['id'], $idSk, $idYc]);

                    $thongBao = [(int)$yc['ID_NguoiGui'], 'Yêu cầu sự kiện đã được duyệt',
                        "Yêu cầu $nhan sự kiện \"$ten\" của bạn đã được admin duyệt.", 'sukien_duyet'];
                    $_SESSION['ds_flash'] = ['ok' => true, 'msg' => $msg];
                }
            }
            $db->commit();

            // 3. Sau khi commit: dọn ảnh (chỉ file do hệ thống tạo, tên dạng sk_xxxxxxxxxxxxxxxx.jpg) và báo cho người gửi
            foreach ($xoaAnh as $tenAnh) {
                $duongDan = ROOT_PATH . '/assets/uploads/sukien/' . $tenAnh;
                if ($tenAnh && preg_match('/^sk_[a-f0-9]{16}\.(jpg|png|webp)$/', $tenAnh) && is_file($duongDan)) {
                    @unlink($duongDan);
                }
            }
            if ($thongBao) {
                try { guiThongBao($db, $thongBao[0], $thongBao[1], $thongBao[2], $thongBao[3]); }
                catch (Throwable $tb) { error_log('duyetsukien.php (thong bao): ' . $tb->getMessage()); }
            }
        } catch (RuntimeException $ex) {
            // Lỗi nghiệp vụ (sự kiện đã mất, vượt số lượng đăng ký...): hoàn tác và báo lại cho admin
            if ($db->inTransaction()) $db->rollBack();
            $_SESSION['ds_flash'] = ['ok' => false, 'msg' => $ex->getMessage()];
        } catch (Throwable $ex) {
            if ($db->inTransaction()) $db->rollBack();
            // Khi chạy thật nên đổi thành: error_log($ex->getMessage()); exit('Có lỗi xảy ra, vui lòng thử lại.');
            exit('Loi xu ly yeu cau: ' . $ex->getMessage() . ' (dong ' . $ex->getLine() . ' - ' . basename($ex->getFile()) . ')');
        }
    }
    header('Location: ' . BASE_URL . '/pages/admin/duyetsukien.php');
    exit;
}

// Thông báo kết quả của lần xử lý trước (hiện 1 lần rồi xóa)
$flash = $_SESSION['ds_flash'] ?? null;
unset($_SESSION['ds_flash']);

$dsYeuCau = $db->query("
    SELECT y.*, n.HoTen, n.Email, s.TenSuKien AS TenHienTai
    FROM yeucau_sukien y
    JOIN nguoidung n ON n.ID = y.ID_NguoiGui
    LEFT JOIN sukien s ON s.ID = y.ID_SuKien
    WHERE y.TrangThai = 'cho_duyet'
    ORDER BY y.NgayGui ASC
")->fetchAll();

$tenDanhMuc = $db->query("SELECT ID, TenDanhMuc FROM danhmuc")->fetchAll(PDO::FETCH_KEY_PAIR);
$nhanLoai   = ['them' => 'Thêm mới', 'sua' => 'Chỉnh sửa', 'xoa' => 'Xóa'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Duyệt sự kiện</title>
<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../pages/admin/admin.css">
<link rel="stylesheet" href="../../pages/admin/duyetsukien.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
<?php require __DIR__ . '/../../components/top.php'; ?>
<?php require __DIR__ . '/../../components/header.php'; ?>

<main class="admin-page duyetsukien-page">
  <h1>Duyệt sự kiện</h1>
  <p class="page-sub">Các yêu cầu thêm, sửa, xóa sự kiện đang chờ xử lý (<?= count($dsYeuCau) ?>)</p>

  <?php if ($flash): ?>
    <p class="alert <?= $flash['ok'] ? 'alert-success' : 'alert-error' ?>"><?= e($flash['msg']) ?></p>
  <?php endif; ?>

  <?php if (!$dsYeuCau): ?>
    <p class="alert alert-warning">Hiện không có yêu cầu nào.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-stack">
      <tr>
        <th>Loại</th><th>Người gửi</th><th>Sự kiện</th><th>Nội dung đề xuất</th><th>Ngày gửi</th><th>Xử lý</th>
      </tr>
      <?php foreach ($dsYeuCau as $r):
        $dl = json_decode((string)$r['DuLieu'], true) ?: []; ?>
      <tr>
        <td data-label="Loại">
            <span class="badge badge-<?= e($r['LoaiYeuCau']) ?>"><?= e($nhanLoai[$r['LoaiYeuCau']] ?? $r['LoaiYeuCau']) ?></span>
        </td>
        <td data-label="Người gửi">
          <?= e($r['HoTen']) ?>
          <span class="ds-sub"><?= e($r['Email']) ?></span>
        </td>
        <td data-label="Sự kiện">
          <strong><?= e($dl['title'] ?? $r['TenHienTai'] ?? '') ?></strong>
          <?php if ($r['LoaiYeuCau'] === 'sua' && $r['TenHienTai'] !== null && $r['TenHienTai'] !== ($dl['title'] ?? '')): ?>
            <span class="ds-sub">Tên hiện tại: <?= e($r['TenHienTai']) ?></span>
          <?php endif; ?>
        </td>
        <td data-label="Nội dung" class="cell-reason">
          <?php if ($r['LoaiYeuCau'] === 'xoa'): ?>
            Đề nghị xóa sự kiện này. Nếu đã có người đăng ký, sự kiện sẽ chuyển sang "Đã hủy".
          <?php else: ?>
            <ul class="ds-detail">
              <li><b>Danh mục:</b> <?= e($tenDanhMuc[$dl['category_id'] ?? 0] ?? '—') ?></li>
              <li><b>Thời gian:</b> <?= e(date('d/m/Y H:i', strtotime($dl['start'] ?? 'now'))) ?> → <?= e(date('d/m/Y H:i', strtotime($dl['end'] ?? 'now'))) ?></li>
              <li><b>Địa điểm:</b> <?= e($dl['location'] ?? '') ?></li>
              <li><b>Số lượng tối đa:</b> <?= (int)($dl['capacity'] ?? 0) ?></li>
              <li><b>Ảnh:</b> <?= !empty($dl['image']) ? 'Có ảnh mới' : (!empty($dl['remove_image']) ? 'Xóa ảnh hiện tại' : 'Giữ nguyên / không có') ?></li>
              <?php if (!empty($dl['desc'])): ?><li><b>Mô tả:</b> <?= nl2br(e($dl['desc'])) ?></li><?php endif; ?>
            </ul>
          <?php endif; ?>
        </td>
        <td data-label="Ngày gửi"><?= e($r['NgayGui']) ?></td>
        <td data-label="Xử lý">
          <form method="post" class="inline-actions">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= (int)$r['ID'] ?>">
            <input type="text" name="ly_do" class="ds-input-reason" maxlength="255" placeholder="Lý do từ chối (không bắt buộc)">
            <button name="hanh_dong" value="duyet" class="btn btn-sm btn-success"
                    onclick="return confirm('Duyệt yêu cầu này và áp dụng vào sự kiện?');">
              <i class="fa-solid fa-check"></i> Duyệt
            </button>
            <button name="hanh_dong" value="tu_choi" class="btn btn-sm btn-danger"
                    onclick="return confirm('Từ chối yêu cầu này?');">
              <i class="fa-solid fa-xmark"></i> Từ chối
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/../../components/footer.php'; ?>
<script src="../../assets/js/components.js"></script>
</body>
</html>