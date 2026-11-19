document.addEventListener("DOMContentLoaded", function () {

    // Hiệu ứng khi rê chuột vào các dòng trong bảng
    const rows = document.querySelectorAll("tbody tr");

    rows.forEach(function (row) {

        row.addEventListener("mouseenter", function () {
            row.style.transition = "0.2s";
        });

    });

});