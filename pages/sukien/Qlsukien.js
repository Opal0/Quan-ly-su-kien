(() => {
  'use strict';

  const $  = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
  const pad2 = (n) => String(n).padStart(2, '0');

  class Toast {
    constructor(container) {
      this.container = container;
    }

    show(message, type = 'ok') {
      const el = document.createElement('div');
      el.className = 'ql-toast ql-toast--' + type;
      el.textContent = message;
      this.container.appendChild(el);
      setTimeout(() => el.remove(), 4000);
    }
  }

  class ApiClient {
    constructor(csrfToken) {
      this.csrfToken = csrfToken;
    }

    async post(formData) {
      const res = await fetch(location.pathname, {
        method: 'POST',
        body: formData,
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

    createFormData(fields) {
      const fd = new FormData();
      fd.append('csrf_token', this.csrfToken);
      Object.entries(fields).forEach(([key, value]) => fd.append(key, value));
      return fd;
    }
  }

  class DateTimeField {
    constructor(root, hiddenInput) {
      this.hidden = hiddenInput;
      this.text   = $('.ql-dt__date', root);
      this.native = $('.ql-dt__native', root);
      this.hour   = $('.ql-dt__h', root);
      this.minute = $('.ql-dt__m', root);
      this.error  = '';

      this.fillOptions(this.hour, 'Giờ', 24);
      this.fillOptions(this.minute, 'Phút', 60);
      this.bind(root);
    }

    static parseDMY(str) {
      const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(str);
      if (!m) return null;

      const d = +m[1], mo = +m[2], y = +m[3];
      const date = new Date(y, mo - 1, d);
      const valid = date.getFullYear() === y && date.getMonth() === mo - 1 && date.getDate() === d;

      return valid ? `${m[3]}-${m[2]}-${m[1]}` : null;
    }

    fillOptions(select, placeholder, count) {
      select.add(new Option(placeholder, ''));
      for (let i = 0; i < count; i++) select.add(new Option(pad2(i), pad2(i)));
    }

    bind(root) {
      this.text.addEventListener('input', () => {
        this.text.value = this.formatDigits(this.text.value);
        this.sync();
      });
      this.hour.addEventListener('change', () => this.sync());
      this.minute.addEventListener('change', () => this.sync());

      $('.ql-dt__cal', root).addEventListener('click', () => {
        this.native.value = DateTimeField.parseDMY(this.text.value.trim()) || '';
        try { this.native.showPicker(); } catch { this.native.focus(); }
      });

      this.native.addEventListener('change', () => {
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(this.native.value);
        if (m) this.text.value = `${m[3]}/${m[2]}/${m[1]}`;
        this.sync();
      });
    }

    formatDigits(value) {
      const d = value.replace(/\D/g, '').slice(0, 8);
      if (d.length > 4) return `${d.slice(0, 2)}/${d.slice(2, 4)}/${d.slice(4)}`;
      if (d.length > 2) return `${d.slice(0, 2)}/${d.slice(2)}`;
      return d;
    }

    sync() {
      const raw = this.text.value.trim();
      this.error = '';
      this.hidden.value = '';
      if (!raw) return;

      const iso = DateTimeField.parseDMY(raw);
      if (!iso) {
        this.error = 'Ngày không hợp lệ, hãy nhập theo dạng dd/mm/yyyy';
        return;
      }
      if (this.hour.value !== '' && this.minute.value !== '') {
        this.hidden.value = `${iso}T${this.hour.value}:${this.minute.value}`;
      }
    }

    set(value) {
      const m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(value || '');
      this.text.value   = m ? `${m[3]}/${m[2]}/${m[1]}` : '';
      this.native.value = m ? `${m[1]}-${m[2]}-${m[3]}` : '';
      this.hour.value   = m ? m[4] : '';
      this.minute.value = m ? m[5] : '';
      this.sync();
    }

    validate(emptyMessage) {
      this.sync();
      if (this.error) return this.error;
      if (!this.hidden.value) {
        return this.text.value.trim() ? 'Vui lòng chọn giờ và phút' : emptyMessage;
      }
      return '';
    }
  }

  class ImageField {
    static MAX_SIZE = 2 * 1024 * 1024;

    constructor(form, onError) {
      this.input   = form.elements.image;
      this.drop    = $('#fDrop');
      this.preview = $('#fPreview');
      this.onError = onError;
      this.bind();
    }

    bind() {
      this.input.addEventListener('change', () => this.take(this.input.files[0]));

      ['dragenter', 'dragover'].forEach((type) =>
        this.drop.addEventListener(type, (e) => {
          e.preventDefault();
          this.drop.classList.add('is-over');
        }));

      ['dragleave', 'drop'].forEach((type) =>
        this.drop.addEventListener(type, (e) => {
          e.preventDefault();
          this.drop.classList.remove('is-over');
        }));

      this.drop.addEventListener('drop', (e) => {
        const file = e.dataTransfer?.files?.[0];
        if (!file) return;

        const transfer = new DataTransfer();
        transfer.items.add(file);
        this.input.files = transfer.files;
        this.take(file);
      });
    }

    take(file) {
      this.onError('image', '');
      if (!file) return;

      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
        this.input.value = '';
        return this.onError('image', 'Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP');
      }
      if (file.size > ImageField.MAX_SIZE) {
        this.input.value = '';
        return this.onError('image', 'Ảnh tối đa 2MB');
      }
      this.show(URL.createObjectURL(file));
    }

    show(src) {
      if (src) {
        this.preview.src = src;
        this.preview.hidden = false;
        this.drop.classList.add('has-img');
        return;
      }
      this.preview.hidden = true;
      this.preview.removeAttribute('src');
      this.drop.classList.remove('has-img');
    }
  }

  class EventForm {
    constructor(api, toast) {
      this.api       = api;
      this.toast     = toast;
      this.form      = $('#qlForm');
      this.card      = $('#qlFormCard');
      this.saveBtn   = $('#qlSave');
      this.saveLabel = $('span', this.saveBtn);
      this.formError = $('#qlFormError');

      this.image = new ImageField(this.form, (name, msg) => this.setError(name, msg));
      this.dates = {
        start: this.createDateField('start'),
        end:   this.createDateField('end'),
      };

      this.bind();
    }

    createDateField(name) {
      return new DateTimeField($(`.ql-dt[data-name="${name}"]`), this.form.elements[name]);
    }

    bind() {
      $('#qlAdd').addEventListener('click', () => {
        this.reset(null);
        this.focus();
      });
      $('#qlReset').addEventListener('click', () => this.reset(null));
      this.form.addEventListener('submit', (e) => this.submit(e));
    }

    edit(eventData) {
      this.reset(eventData);
      this.focus();
    }

    focus() {
      this.card.scrollIntoView({ behavior: 'smooth', block: 'start' });
      this.form.elements.title.focus({ preventScroll: true });
    }

    clearErrors() {
      $$('.ql-err', this.form).forEach((s) => (s.textContent = ''));
      $$('.ql-field.has-error', this.form).forEach((f) => f.classList.remove('has-error'));
    }

    setError(name, message) {
      const slot = $(`.ql-err[data-for="${name}"]`, this.form);
      if (!slot) {
        this.formError.textContent = message;
        return;
      }
      slot.textContent = message;
      slot.closest('.ql-field')?.classList.toggle('has-error', !!message);
    }

    setMode(editing) {
      $('#qlFormTitle').textContent = editing ? 'Sửa sự kiện' : 'Thông tin sự kiện';
      this.saveLabel.textContent    = editing ? 'Cập nhật sự kiện' : 'Đăng sự kiện';
    }

    reset(ev) {
      const f = this.form.elements;

      this.form.reset();
      this.clearErrors();
      this.dates.start.set('');
      this.dates.end.set('');
      this.formError.textContent = '';
      this.image.show('');
      $('#fRemoveRow').hidden = true;
      $('#fCapHint').textContent = '';
      f.capacity.min = 1;
      f.id.value = ev ? ev.id : 0;
      this.setMode(!!ev);

      if (ev) this.fill(ev);
    }

    fill(ev) {
      const f = this.form.elements;

      f.title.value       = ev.title;
      f.category_id.value = ev.category_id || '';
      this.dates.start.set(ev.start);
      this.dates.end.set(ev.end);
      f.location.value    = ev.location;
      f.desc.value        = ev.desc;
      f.capacity.value    = ev.capacity;

      if (ev.registered > 0) {
        f.capacity.min = ev.registered;
        $('#fCapHint').textContent = `Đã có ${ev.registered} người đăng ký, không thể đặt thấp hơn.`;
      }
      if (ev.image_url) {
        this.image.show(ev.image_url);
        $('#fRemoveRow').hidden = false;
      }
    }

    validate() {
      const f = this.form.elements;
      let ok = true;
      const bad = (name, msg) => {
        this.setError(name, msg);
        ok = false;
      };

      if (!f.title.value.trim()) bad('title', 'Vui lòng nhập tên sự kiện');
      if (!f.category_id.value)  bad('category_id', 'Vui lòng chọn danh mục');

      const startError = this.dates.start.validate('Vui lòng chọn thời gian bắt đầu');
      const endError   = this.dates.end.validate('Vui lòng chọn thời gian kết thúc');
      if (startError) bad('start', startError);
      if (endError)   bad('end', endError);
      if (!startError && !endError && f.end.value < f.start.value) {
        bad('end', 'Thời gian kết thúc phải sau thời gian bắt đầu');
      }

      if (!f.location.value.trim()) bad('location', 'Vui lòng nhập địa điểm');

      const capacity = Number(f.capacity.value);
      const min = Number(f.capacity.min) || 1;
      if (!Number.isInteger(capacity) || capacity < min) {
        bad('capacity', min > 1
          ? `Số lượng tối đa phải từ ${min} trở lên`
          : 'Số lượng tối đa phải là số nguyên lớn hơn 0');
      }
      return ok;
    }

    async submit(e) {
      e.preventDefault();
      this.clearErrors();
      this.formError.textContent = '';
      if (!this.validate()) return;

      const oldLabel = this.saveLabel.textContent;
      this.saveBtn.disabled = true;
      this.saveLabel.textContent = 'Đang lưu...';

      try {
        const res = await this.api.post(new FormData(this.form));
        this.toast.show(res.message, 'ok');
        setTimeout(() => location.reload(), 800);
      } catch (err) {
        this.showServerErrors(err);
        this.saveBtn.disabled = false;
        this.saveLabel.textContent = oldLabel;
      }
    }

    showServerErrors(err) {
      const entries = Object.entries(err.errors);
      entries.forEach(([name, message]) => this.setError(name, message));
      if (!entries.length) this.formError.textContent = err.message;
    }
  }

  class EventTable {
    constructor(api, toast, form) {
      this.api   = api;
      this.toast = toast;
      this.form  = form;
      this.bindEdit();
      this.bindDelete();
      this.bindFilters();
    }

    bindEdit() {
      $$('.js-edit').forEach((btn) =>
        btn.addEventListener('click', () =>
          this.form.edit(JSON.parse(btn.closest('tr').dataset.event))));
    }

    bindDelete() {
      $$('.js-delete').forEach((btn) =>
        btn.addEventListener('click', () => this.remove(btn)));
    }

    bindFilters() {
      $$('[data-autosubmit]').forEach((sel) =>
        sel.addEventListener('change', () => sel.form.submit()));
    }

    confirmMessage({ title }, registered) {
      return registered > 0
        ? `Sự kiện "${title}" đang có ${registered} người đăng ký.\nHệ thống sẽ chuyển sang trạng thái "Đã hủy" thay vì xóa. Tiếp tục?`
        : `Xóa vĩnh viễn sự kiện "${title}"? Hành động này không thể hoàn tác.`;
    }

    async remove(btn) {
      const registered = Number(btn.dataset.registered);
      if (!confirm(this.confirmMessage(btn.dataset, registered))) return;

      btn.disabled = true;
      try {
        const res = await this.api.post(
          this.api.createFormData({ action: 'delete', id: btn.dataset.id })
        );
        this.toast.show(res.message, 'ok');
        setTimeout(() => location.reload(), 800);
      } catch (err) {
        this.toast.show(err.message, 'error');
        btn.disabled = false;
      }
    }
  }

  class CancelRequestButtons {
    constructor() {
      $$('.js-cancel').forEach((btn) =>
        btn.addEventListener('click', () => this.cancel(btn)));
    }

    async cancel(btn) {
      if (!confirm('Rút lại yêu cầu "' + btn.dataset.title + '"?')) return;

      const token = $('#qlForm input[type="hidden"][name*="csrf"]');
      const fd = new FormData();
      fd.append(token.name, token.value);
      fd.append('action', 'cancel_request');
      fd.append('request_id', btn.dataset.id);

      btn.disabled = true;
      try {
        const res  = await fetch(location.href, { method: 'POST', body: fd });
        const json = await res.json();
        alert(json.message);
        if (json.ok) location.reload(); else btn.disabled = false;
      } catch (err) {
        alert('Không kết nối được máy chủ.');
        btn.disabled = false;
      }
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const api   = new ApiClient($('meta[name="csrf-token"]').content);
    const toast = new Toast($('#qlToasts'));
    const form  = new EventForm(api, toast);

    new EventTable(api, toast, form);
    new CancelRequestButtons();
  });
})();