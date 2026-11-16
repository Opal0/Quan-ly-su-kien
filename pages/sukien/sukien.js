/* =========================================================
   sukien.js — Trang Sự kiện (Cấu trúc OOP)
   1. EventManager: Xử lý AJAX đăng ký không tải lại trang & thông báo tự ẩn
   2. EventModal: Quản lý popup form đăng ký và validate dữ liệu đầu vào
   ========================================================= */

class EventManager {
    constructor() {
        this.flashMs = 5000;
        this.init();
    }

    init() {
        this.armFlash();
        document.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    /**
     * Tự động ẩn thông báo flash sau một khoảng thời gian
     */
    armFlash() {
        const flash = document.querySelector('.sk-flash');
        if (!flash) return;

        setTimeout(() => {
            flash.classList.add('is-hiding');
            setTimeout(() => { flash.remove(); }, 400);
        }, this.flashMs);
    }

    /**
     * Kiểm tra xem form gửi lên có phải form sự kiện hợp lệ không
     */
    isEventForm(form) {
        return form.closest('.sk') &&
               form.method.toLowerCase() === 'post' &&
               form.querySelector('input[name="action"]');
    }

    /**
     * Cập nhật lại nội dung thẻ <main class="sk"> sau khi nhận kết quả từ Server
     */
    swapMain(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const fresh = doc.querySelector('main.sk');
        const current = document.querySelector('main.sk');
        
        if (!fresh || !current) return false;
        current.innerHTML = fresh.innerHTML;
        return true;
    }

    /**
     * Xử lý sự kiện submit form bằng AJAX
     */
    handleSubmit(e) {
        const form = e.target;
        if (!this.isEventForm(form)) return;
        if (e.defaultPrevented) return; // Người dùng đã bấm "Hủy" ở hộp xác nhận

        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');
        const oldText = btn ? btn.textContent : '';
        if (btn) { 
            btn.disabled = true; 
            btn.textContent = 'Đang xử lý...'; 
        }

        fetch(form.action || window.location.href, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin'
        })
        .then((res) => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text().then((html) => ({ res, html }));
        })
        .then((r) => {
            if (!this.swapMain(r.html)) {
                // Ví dụ: phiên đăng nhập hết hạn, server chuyển hướng sang trang khác
                window.location.href = r.res.url;
                return;
            }
            this.armFlash();
        })
        .catch(() => {
            if (btn) { 
                btn.disabled = false; 
                btn.textContent = oldText; 
            }
            form.submit(); // Phương án dự phòng: Gửi form theo cách truyền thống tải lại trang
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

        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Mở modal khi bấm nút đăng ký
        document.querySelectorAll('[data-register]').forEach(btn => {
            btn.addEventListener('click', () => this.open(btn));
        });

        // Đóng modal bằng nút đóng
        this.modal.querySelectorAll('[data-close]').forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });

        // Đóng khi click ra ngoài vùng modal
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.close();
        });

        // Trả lại focus cho nút bấm mở trước đó khi đóng modal
        this.modal.addEventListener('close', () => {
            if (this.opener) this.opener.focus();
        });

        // Lắng nghe sự kiện kiểm tra dữ liệu form trước khi submit
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.validateForm(e));
        }

        // Ẩn thông báo flash phụ (nếu có)
        const flash = document.querySelector('.sk-flash');
        if (flash) {
            setTimeout(() => flash.classList.add('is-hidden'), 7000);
        }
    }

    /**
     * Mở modal và thiết lập dữ liệu ban đầu
     */
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

    /**
     * Đóng modal
     */
    close() {
        this.modal.close();
    }

    /**
     * Validate dữ liệu đầu vào của Form Đăng Ký
     */
    validateForm(e) {
        const f = this.form.elements;
        let msg = '';

        if (f.name.value.trim().length < 2) {
            msg = 'Hãy nhập họ và tên.';
        } else if (!f.email.validity.valid || !f.email.value) {
            msg = 'Email chưa đúng định dạng.';
        } else if (!/^(0|\+84)\d{9}$/.test(f.phone.value.replace(/\s+/g, ''))) {
            msg = 'Số điện thoại cần có 10 chữ số, ví dụ 0912345678.';
        }

        if (msg) {
            e.preventDefault();
            this.errBox.textContent = msg;
        }
    }
}

// Khởi tạo và kích hoạt các đối tượng khi trang tải xong
document.addEventListener('DOMContentLoaded', () => {
    new EventManager();
    new EventModal();
});


