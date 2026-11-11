document.addEventListener("DOMContentLoaded", function () {

    // Các phần tử sự kiện
    const eventSelect = document.getElementById("event");

    const eventCoverTitle = document.getElementById("eventCoverTitle");
    const eventCoverSubtitle = document.getElementById("eventCoverSubtitle");
    const eventCategory = document.getElementById("eventCategory");
    const eventName = document.getElementById("eventName");
    const eventDate = document.getElementById("eventDate");
    const eventTime = document.getElementById("eventTime");
    const eventLocation = document.getElementById("eventLocation");
    const eventDescription = document.getElementById("eventDescription");

    // Các phần tử tính tiền
    const ticketSelect = document.getElementById("MaVe");
    const quantityInput = document.getElementById("SoLuong");
    const totalPrice = document.getElementById("totalPrice");
    const bookingForm = document.getElementById("bookingForm");

    // Cập nhật khung sự kiện bên trái
    function updateEventInfo() {

        if (!eventSelect || !eventSelect.value) return;

        const option = eventSelect.options[eventSelect.selectedIndex];

        const name = option.dataset.name || "";
        const category = option.dataset.category || "";
        const date = option.dataset.date || "";
        const time = option.dataset.time || "";
        const location = option.dataset.location || "";
        const description = option.dataset.description || "";

        eventCoverTitle.textContent = name;
        eventCoverSubtitle.textContent = category;

        eventCategory.textContent = category;
        eventName.textContent = name;
        eventDate.textContent = date;
        eventTime.textContent = time;
        eventLocation.textContent = location;
        eventDescription.textContent = description;
    }

    // Định dạng tiền Việt Nam
    function formatMoney(amount) {
        return amount.toLocaleString("vi-VN") + " VNĐ";
    }

    // Tính tổng tiền
    function calculateTotal() {

        if (!ticketSelect || !quantityInput || !totalPrice) return;

        const option = ticketSelect.options[ticketSelect.selectedIndex];

        const price = Number(option.dataset.price) || 0;

        let quantity = Number(quantityInput.value) || 1;

        if (quantity < 1) quantity = 1;
        if (quantity > 10) quantity = 10;

        totalPrice.textContent = formatMoney(price * quantity);
    }

    // Khi thay đổi sự kiện
    if (eventSelect) {
        eventSelect.addEventListener("change", updateEventInfo);
        updateEventInfo();
    }

    // Khi thay đổi loại vé hoặc số lượng
    if (ticketSelect && quantityInput) {

        ticketSelect.addEventListener("change", calculateTotal);

        quantityInput.addEventListener("input", calculateTotal);

        calculateTotal();
    }

    // Form hiện vẫn là giao diện demo
    if (bookingForm) {

        bookingForm.addEventListener("submit", function (event) {

            event.preventDefault();

            alert(
                "Giao diện đã nhận thông tin đặt vé. " +
                "Chức năng lưu đăng ký vào MySQL và thanh toán " +
                "cần được tích hợp ở backend."
            );

        });
    }

});
