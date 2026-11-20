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

  /* ---------- Ô ngày giờ: dd/mm/yyyy + giờ 00-23 + phút 00-59 ----------
     Ô ẩn name="start"/"end" vẫn giữ dạng YYYY-MM-DDTHH:MM nên server không phải đổi. */
  const pad2 = n => String(n).padStart(2, '0');
  const dts = {};

  function parseDMY(str) {
    const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(str);
    if (!m) return null;
    const d = +m[1], mo = +m[2], y = +m[3];
    const dt = new Date(y, mo - 1, d);
    if (dt.getFullYear() !== y || dt.getMonth() !== mo - 1 || dt.getDate() !== d) return null;
    return `${m[3]}-${m[2]}-${m[1]}`;
  }

  $$('.ql-dt').forEach(root => {
    const name   = root.dataset.name;
    const hidden = form.elements[name];
    const text   = $('.ql-dt__date', root);
    const native = $('.ql-dt__native', root);
    const hh     = $('.ql-dt__h', root);
    const mm     = $('.ql-dt__m', root);
    const w      = { hidden, text, native, hh, mm, error: '' };
    dts[name] = w;

    hh.add(new Option('Giờ', ''));
    for (let i = 0; i < 24; i++) hh.add(new Option(pad2(i), pad2(i)));
    mm.add(new Option('Phút', ''));
    for (let i = 0; i < 60; i++) mm.add(new Option(pad2(i), pad2(i)));

    w.sync = () => {
      const raw = text.value.trim();
      w.error = '';
      hidden.value = '';
      if (!raw) return;
      const iso = parseDMY(raw);
      if (!iso) { w.error = 'Ngày không hợp lệ, hãy nhập theo dạng dd/mm/yyyy'; return; }
      if (hh.value !== '' && mm.value !== '') hidden.value = `${iso}T${hh.value}:${mm.value}`;
    };

    text.addEventListener('input', () => {
      const d = text.value.replace(/\D/g, '').slice(0, 8);
      text.value = d.length > 4 ? `${d.slice(0, 2)}/${d.slice(2, 4)}/${d.slice(4)}`
                 : d.length > 2 ? `${d.slice(0, 2)}/${d.slice(2)}`
                 : d;
      w.sync();
    });
    hh.addEventListener('change', w.sync);
    mm.addEventListener('change', w.sync);

    $('.ql-dt__cal', root).addEventListener('click', () => {
      native.value = parseDMY(text.value.trim()) || '';
      try { native.showPicker(); } catch { native.focus(); }
    });
    native.addEventListener('change', () => {
      const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(native.value);
      if (m) text.value = `${m[3]}/${m[2]}/${m[1]}`;
      w.sync();
    });
  });

  // Đổ giá trị "YYYY-MM-DDTHH:MM" vào ô ngày giờ ('' = xóa trắng)
  function dtSet(name, v) {
    const w = dts[name];
    const m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(v || '');
    w.text.value   = m ? `${m[3]}/${m[2]}/${m[1]}` : '';
    w.native.value = m ? `${m[1]}-${m[2]}-${m[3]}` : '';
    w.hh.value     = m ? m[4] : '';
    w.mm.value     = m ? m[5] : '';
    w.sync();
  }

  // Trả về thông báo lỗi, hoặc '' nếu hợp lệ
  function checkDT(name, emptyMsg) {
    const w = dts[name];
    w.sync();
    if (w.error) return w.error;
    if (!w.hidden.value) return w.text.value.trim() ? 'Vui lòng chọn giờ và phút' : emptyMsg;
    return '';
  }

  /* ---------- Chế độ thêm / sửa ---------- */
  function setMode(ev) {
    const editing = !!ev;
    $('#qlFormTitle').textContent = editing ? 'Sửa sự kiện' : 'Thông tin sự kiện';
    $('span', saveBtn).textContent = editing ? 'Cập nhật sự kiện' : 'Đăng sự kiện';
  }

  function resetForm(ev) {
    form.reset();
    clearErrors();
    dtSet('start', '');
    dtSet('end', '');
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
    dtSet('start', ev.start);
    dtSet('end', ev.end);
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
    const sErr = checkDT('start', 'Vui lòng chọn thời gian bắt đầu');
    const eErr = checkDT('end', 'Vui lòng chọn thời gian kết thúc');
    if (sErr) bad('start', sErr);
    if (eErr) bad('end', eErr);
    if (!sErr && !eErr && f.end.value < f.start.value)
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