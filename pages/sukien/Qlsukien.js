/* =====================================================
   Qlsukien.js - trang quản lý sự kiện
   - Form nằm ngay trên trang: thêm mới, hoặc bấm "Sửa" để đổ dữ liệu cũ vào form
   - Kiểm tra dữ liệu phía client (server vẫn kiểm tra lại)
   - Gửi AJAX (fetch + FormData) tới chính trang này, nhận JSON
   - Kéo thả / xem trước ảnh, xác nhận xóa, thông báo nhanh
   ===================================================== */
(() => {
  'use strict';

  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

  const csrf      = $('meta[name="csrf-token"]').content;
  const form      = $('#qlForm');
  const card      = $('#qlFormCard');
  const drop      = $('#fDrop');
  const preview   = $('#fPreview');
  const fileInput = form.elements.image;
  const saveBtn   = $('#qlSave');
  const MAX_IMG   = 2 * 1024 * 1024;

  /* ---------- Thông báo nhanh ---------- */
  function toast(msg, type = 'ok') {
    const el = document.createElement('div');
    el.className = 'ql-toast ql-toast--' + type;
    el.textContent = msg;                       // textContent: chống XSS
    $('#qlToasts').appendChild(el);
    setTimeout(() => el.remove(), 4000);
  }

  /* ---------- Gọi server (AJAX) ---------- */
  async function post(formData) {
    const res = await fetch(location.pathname, {
      method: 'POST',
      body: formData,                           // multipart, kèm được file ảnh
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) {
      const err = new Error(data.message || 'Có lỗi xảy ra, hãy thử lại.');
      err.errors = data.errors || {};
      throw err;
    }
    return data;
  }

  const reloadSoon = () => setTimeout(() => location.reload(), 800);

  /* ---------- Hiển thị lỗi trong form ---------- */
  function clearErrors() {
    $$('.ql-err', form).forEach(s => (s.textContent = ''));
    $$('.ql-field.has-error', form).forEach(f => f.classList.remove('has-error'));
  }
  function setError(name, msg) {
    const slot = $(`.ql-err[data-for="${name}"]`, form);
    if (slot) {
      slot.textContent = msg;
      slot.closest('.ql-field')?.classList.toggle('has-error', !!msg);
    } else {
      $('#qlFormError').textContent = msg;
    }
  }

  /* ---------- Ảnh: xem trước ---------- */
  function showPreview(src) {
    if (src) {
      preview.src = src;
      preview.hidden = false;
      drop.classList.add('has-img');
    } else {
      preview.hidden = true;
      preview.removeAttribute('src');
      drop.classList.remove('has-img');
    }
  }

  function takeFile(f) {
    setError('image', '');
    if (!f) return;
    if (!/^image\/(jpeg|png|webp)$/.test(f.type)) {
      fileInput.value = '';
      return setError('image', 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP');
    }
    if (f.size > MAX_IMG) {
      fileInput.value = '';
      return setError('image', 'Ảnh tối đa 2MB');
    }
    showPreview(URL.createObjectURL(f));
  }

  fileInput.addEventListener('change', () => takeFile(fileInput.files[0]));

  ['dragenter', 'dragover'].forEach(t =>
    drop.addEventListener(t, e => { e.preventDefault(); drop.classList.add('is-over'); }));
  ['dragleave', 'drop'].forEach(t =>
    drop.addEventListener(t, e => { e.preventDefault(); drop.classList.remove('is-over'); }));
  drop.addEventListener('drop', e => {
    const f = e.dataTransfer?.files?.[0];
    if (!f) return;
    const dt = new DataTransfer();
    dt.items.add(f);
    fileInput.files = dt.files;
    takeFile(f);
  });

  /* ---------- Chế độ thêm / sửa ---------- */
  function setMode(ev) {
    const editing = !!ev;
    $('#qlFormTitle').textContent = editing ? 'Sửa sự kiện' : 'Thông tin sự kiện';
    $('span', saveBtn).textContent = editing ? 'Cập nhật sự kiện' : 'Lưu sự kiện';
  }

  function resetForm(ev) {
    form.reset();
    clearErrors();
    $('#qlFormError').textContent = '';
    showPreview('');
    $('#fRemoveRow').hidden = true;
    $('#fCapHint').textContent = '';
    form.elements.capacity.min = 1;
    form.elements.id.value = ev ? ev.id : 0;
    setMode(ev);

    if (!ev) return;
    form.elements.title.value       = ev.title;
    form.elements.category_id.value = ev.category_id || '';
    form.elements.start.value       = ev.start;
    form.elements.end.value         = ev.end;
    form.elements.location.value    = ev.location;
    form.elements.desc.value        = ev.desc;
    form.elements.capacity.value    = ev.capacity;

    if (ev.registered > 0) {                    // không cho đặt thấp hơn số người đã đăng ký
      form.elements.capacity.min = ev.registered;
      $('#fCapHint').textContent = `Đã có ${ev.registered} người đăng ký, không thể đặt thấp hơn.`;
    }
    if (ev.image_url) {
      showPreview(ev.image_url);
      $('#fRemoveRow').hidden = false;
    }
  }

  function goToForm() {
    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    form.elements.title.focus({ preventScroll: true });
  }

  $('#qlAdd').addEventListener('click', () => { resetForm(null); goToForm(); });
  $('#qlReset').addEventListener('click', () => resetForm(null));

  $$('.js-edit').forEach(btn =>
    btn.addEventListener('click', () => {
      resetForm(JSON.parse(btn.closest('tr').dataset.event));
      goToForm();
    })
  );

  /* ---------- Kiểm tra phía client ---------- */
  function validate() {
    const f = form.elements;
    let ok = true;
    const bad = (name, msg) => { setError(name, msg); ok = false; };

    if (!f.title.value.trim())    bad('title', 'Vui lòng nhập tên sự kiện');
    if (!f.category_id.value)     bad('category_id', 'Vui lòng chọn danh mục');
    if (!f.start.value)           bad('start', 'Vui lòng chọn thời gian bắt đầu');
    if (!f.end.value)             bad('end', 'Vui lòng chọn thời gian kết thúc');
    if (f.start.value && f.end.value && f.end.value < f.start.value)
                                  bad('end', 'Thời gian kết thúc phải sau thời gian bắt đầu');
    if (!f.location.value.trim()) bad('location', 'Vui lòng nhập địa điểm');

    const cap = Number(f.capacity.value);
    const min = Number(f.capacity.min) || 1;
    if (!Number.isInteger(cap) || cap < min)
      bad('capacity', min > 1 ? `Số lượng tối đa phải từ ${min} trở lên` : 'Số lượng tối đa phải là số nguyên lớn hơn 0');
    return ok;
  }

  /* ---------- Gửi form thêm / sửa ---------- */
  form.addEventListener('submit', async e => {
    e.preventDefault();
    clearErrors();
    $('#qlFormError').textContent = '';
    if (!validate()) return;

    const label = $('span', saveBtn);
    const oldLabel = label.textContent;
    saveBtn.disabled = true;
    label.textContent = 'Đang lưu...';
    try {
      const res = await post(new FormData(form));
      toast(res.message, 'ok');
      reloadSoon();
    } catch (err) {
      Object.entries(err.errors).forEach(([k, v]) => setError(k, v));
      if (!Object.keys(err.errors).length) $('#qlFormError').textContent = err.message;
      saveBtn.disabled = false;
      label.textContent = oldLabel;
    }
  });

  /* ---------- Xóa ---------- */
  $$('.js-delete').forEach(btn =>
    btn.addEventListener('click', async () => {
      const { id, title } = btn.dataset;
      const registered = Number(btn.dataset.registered);
      const msg = registered > 0
        ? `Sự kiện "${title}" đang có ${registered} người đăng ký.\nHệ thống sẽ chuyển sang trạng thái "Đã hủy" thay vì xóa. Tiếp tục?`
        : `Xóa vĩnh viễn sự kiện "${title}"? Hành động này không thể hoàn tác.`;
      if (!confirm(msg)) return;

      const fd = new FormData();
      fd.append('csrf_token', csrf);
      fd.append('action', 'delete');
      fd.append('id', id);

      btn.disabled = true;
      try {
        const res = await post(fd);
        toast(res.message, 'ok');
        reloadSoon();
      } catch (err) {
        toast(err.message, 'error');
        btn.disabled = false;
      }
    })
  );

  /* ---------- Bộ lọc: đổi trạng thái / thời gian thì tự lọc ---------- */
  $$('[data-autosubmit]').forEach(sel => sel.addEventListener('change', () => sel.form.submit()));
})();