class RowHover {
    constructor(selector) {
        this.rows = document.querySelectorAll(selector);
        this.bind();
    }

    bind() {
        this.rows.forEach((row) => {
            row.addEventListener('mouseenter', () => {
                row.style.transition = '0.2s';
            });
        });
    }
}

document.addEventListener('DOMContentLoaded', () => new RowHover('tbody tr'));