
document.addEventListener("DOMContentLoaded", function () {

    // Các phần tử trên trang
    const eventSelect = document.getElementById("event");
    const ticketSelect = document.getElementById("MaVe");
    const quantityInput = document.getElementById("SoLuong");
    const totalPrice = document.getElementById("totalPrice");

    const bookingForm = document.getElementById("bookingForm");
    const bookingButton = document.getElementById("bookingButton");
    const ticketHint = document.getElementById("ticketHint");

    const eventDataElement = document.getElementById("eventData");

    if (
        !eventSelect ||
        !ticketSelect ||
        !quantityInput ||
        !totalPrice ||
        !bookingForm ||
        !eventDataElement
    ) {
        return;
    }


    // Đọc dữ liệu sự kiện được PHP truyền sang.
    let eventData = {};

    try {
        eventData = JSON.parse(eventDataElement.textContent || "{}");
    } catch (error) {
        console.error("Không đọc được dữ liệu sự kiện:", error);
    }


    // Các phần tử hiển thị thông tin sự kiện bên trái.
    const eventCategory = document.getElementById("eventCategory");
    const eventCoverLabel = document.getElementById("eventCoverLabel");
    const eventName = document.getElementById("eventName");
    const eventDate = document.getElementById("eventDate");
    const eventTime = document.getElementById("eventTime");
    const eventLocation = document.getElementById("eventLocation");
    const eventDescription = document.getElementById("eventDescription");


    // Định dạng tiền Việt Nam.
    function formatMoney(amount) {
        return Number(amount || 0).toLocaleString("vi-VN") + " VNĐ";
    }


    // Lấy sự kiện đang được chọn.
    function getSelectedEvent() {
        return eventData[String(eventSelect.value)] || null;
    }


    // Tính tổng tiền.
    function calculateTotal() {

        const selectedOption =
            ticketSelect.options[ticketSelect.selectedIndex];

        const price = selectedOption
            ? Number(selectedOption.dataset.price) || 0
            : 0;

        let quantity = Number(quantityInput.value) || 1;

        if (quantity < 1) {
            quantity = 1;
        }

        if (quantity > 10) {
            quantity = 10;
        }

        const total = price * quantity;

        totalPrice.textContent = total <= 0
            ? "Miễn phí"
            : formatMoney(total);
    }


    // Hiển thị danh sách loại vé theo sự kiện.
    function renderTicketOptions(tickets) {

        // Xóa các loại vé cũ.
        ticketSelect.innerHTML = "";


        // Nếu sự kiện chưa có loại vé.
        if (!Array.isArray(tickets) || tickets.length === 0) {

            const option = document.createElement("option");

            option.value = "";
            option.textContent =
                "Sự kiện này hiện chưa mở bán vé";

            ticketSelect.appendChild(option);

            ticketSelect.disabled = true;

            if (ticketHint) {
                ticketHint.textContent =
                    "Vui lòng chọn sự kiện khác hoặc quay lại sau.";
            }

            if (bookingButton) {
                bookingButton.disabled = true;
            }

            calculateTotal();

            return;
        }


        // Thêm các loại vé của sự kiện đang chọn.
        tickets.forEach(function (ticket) {

            const option = document.createElement("option");

            option.value = ticket.id;
            option.dataset.price = ticket.price;

            option.textContent =
                ticket.name +
                " — " +
                (
                    Number(ticket.price) <= 0
                        ? "Miễn phí"
                        : formatMoney(ticket.price)
                );

            ticketSelect.appendChild(option);

        });


        // Cho phép chọn loại vé.
        ticketSelect.disabled = false;

        if (ticketHint) {
            ticketHint.textContent =
                "Các loại vé được thiết lập riêng cho sự kiện này.";
        }

        if (bookingButton) {
            bookingButton.disabled = false;
        }

        calculateTotal();
    }


    // Cập nhật thông tin sự kiện bên trái.
    function renderEventDetails() {

        const selectedEvent = getSelectedEvent();

        if (!selectedEvent) {
            return;
        }

        if (eventCategory) {
            eventCategory.textContent =
                selectedEvent.category || "Sự kiện";
        }

        if (eventCoverLabel) {
            eventCoverLabel.textContent =
                (selectedEvent.category || "Sự kiện")
                .toLocaleUpperCase("vi-VN");
        }

        if (eventName) {
            eventName.textContent =
                selectedEvent.name || "Sự kiện";
        }

        if (eventDate) {
            eventDate.textContent =
                selectedEvent.date || "Đang cập nhật";
        }

        if (eventTime) {
            eventTime.textContent =
                selectedEvent.time || "Đang cập nhật";
        }

        if (eventLocation) {
            eventLocation.textContent =
                selectedEvent.location || "Đang cập nhật";
        }

        if (eventDescription) {
            eventDescription.textContent =
                selectedEvent.description ||
                "Thông tin giới thiệu sự kiện sẽ được cập nhật sớm.";
        }


        // Đồng thời cập nhật danh sách loại vé.
        renderTicketOptions(selectedEvent.tickets);
    }


    // Khi đổi sự kiện.
    eventSelect.addEventListener("change", function () {

        renderEventDetails();

    });


    // Khi đổi loại vé.
    ticketSelect.addEventListener("change", function () {

        calculateTotal();

    });


    // Khi đổi số lượng vé.
    quantityInput.addEventListener("input", function () {

        calculateTotal();

    });


    // Cập nhật thông tin ngay khi mở trang.
    renderEventDetails();

    });
