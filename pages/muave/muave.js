document.addEventListener("DOMContentLoaded", function () {
    const ticketSelect = document.getElementById("MaVe");
    const quantityInput = document.getElementById("SoLuong");
    const totalPrice = document.getElementById("totalPrice");
    const bookingForm = document.getElementById("bookingForm");

    if (!ticketSelect || !quantityInput || !totalPrice || !bookingForm) return;

    function formatMoney(amount) {
        return amount.toLocaleString("vi-VN") + " VNĐ";
    }

    function calculateTotal() {
        const option = ticketSelect.options[ticketSelect.selectedIndex];
        const price = Number(option.dataset.price) || 0;
        let quantity = Number(quantityInput.value) || 1;

        if (quantity < 1) quantity = 1;
        if (quantity > 10) quantity = 10;

        totalPrice.textContent = formatMoney(price * quantity);
    }

    ticketSelect.addEventListener("change", calculateTotal);
    quantityInput.addEventListener("input", calculateTotal);
    calculateTotal();

    bookingForm.addEventListener("submit", function (event) {
        event.preventDefault();
        alert("Giao diện đã nhận thông tin đặt vé. Chức năng lưu đăng ký vào MySQL và thanh toán cần được tích hợp ở backend.");
    });
});
