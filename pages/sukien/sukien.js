class EventManager {
    constructor() {
        this.flashMs = 5000;
        this.init();
    }

    init() {
        this.armFlash();
        document.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    armFlash() {
        const flash = document.querySelector('.sk-flash');
        if (!flash) return;

        setTimeout(() => {
            flash.classList.add('is-hiding');
            setTimeout(() => flash.remove(), 400);
        }, this.flashMs);
    }

    isEventForm(form) {
        return form.closest('.sk') &&
               form.method.toLowerCase() === 'post' &&
               form.querySelector('input[name="action"]');
    }

    swapMain(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const fresh = doc.querySelector('main.sk');
        const current = document.querySelector('main.sk');

        if (!fresh || !current) return false;
        current.innerHTML = fresh.innerHTML;
        return true;
    }

    setBusy(btn, busy, text) {
        if (!btn) return;
        btn.disabled = busy;
        btn.textContent = text;
    }

    handleSubmit(e) {
        const form = e.target;
        if (!this.isEventForm(form) || e.defaultPrevented) return;

        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');
        const oldText = btn ? btn.textContent : '';
        this.setBusy(btn, true, 'Đang xử lý...');

        fetch(form.action || window.location.href, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin'
        })
            .then((res) => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text().then((html) => ({ res, html }));
            })
            .then(({ res, html }) => {
                if (!this.swapMain(html)) {
                    window.location.href = res.url;
                    return;
                }
                this.armFlash();
            })
            .catch(() => {
                this.setBusy(btn, false, oldText);
                form.submit();
            });
    }
}

class EventModal {
    constructor() {
        this.modal = document.getElementById('sk-modal');
        if (!this.modal) return;

        this.form = this.modal.querySelector('form');
        this.errBox = document.getElementById('sk-form-error');
        this.opener = null;

        this.bindEvents();
    }

    bindEvents() {
        document.querySelectorAll('[data-register]').forEach((btn) => {
            btn.addEventListener('click', () => this.open(btn));
        });

        this.modal.querySelectorAll('[data-close]').forEach((btn) => {
            btn.addEventListener('click', () => this.close());
        });

        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.close();
        });

        this.modal.addEventListener('close', () => {
            if (this.opener) this.opener.focus();
        });

        if (this.form) {
            this.form.addEventListener('submit', (e) => this.validateForm(e));
        }
    }

    open(btn) {
        this.opener = btn;
        document.getElementById('sk-event-id').value = btn.dataset.id;
        document.getElementById('sk-event-title').textContent = btn.dataset.title;
        this.errBox.textContent = '';
        this.modal.showModal();

        if (this.form && this.form.elements.name) {
            this.form.elements.name.focus();
        }
    }

    close() {
        this.modal.close();
    }

    validateForm(e) {
        const msg = this.findError(this.form.elements);

        if (msg) {
            e.preventDefault();
            this.errBox.textContent = msg;
        }
    }

    findError(f) {
        if (f.name.value.trim().length < 2) {
            return 'Hãy nhập họ và tên.';
        }
        if (!f.email.validity.valid || !f.email.value) {
            return 'Email chưa đúng định dạng.';
        }
        if (!/^(0|\+84)\d{9}$/.test(f.phone.value.replace(/\s+/g, ''))) {
            return 'Số điện thoại cần có 10 chữ số, ví dụ 0912345678.';
        }
        return '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new EventManager();
    new EventModal();
});