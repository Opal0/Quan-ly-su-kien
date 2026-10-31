<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../css/dungchung.css">
    <link rel="stylesheet" href="../css/taikhoan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <title>Tài khoản</title>
</head>
<body>
    <?php include 'top.html'; ?>
    <?php include 'header.html'; ?>
    <main class="main-content">
        <h2>Thông tin tài khoản</h2>
        <form action="">
            <div class="form-row">
                <label for="name">Họ và tên </label>
                <input type="text" name="name"> 
            </div>
            <div class="form-row">
                <label for="phonenumber">Số điện thoại</label>
                <input type="text" name="phonenumber">
            </div>
            <div class="form-row">
                <label for="Email">Email</label>
                <input type="text" name="Email">
            </div>
            <div class="form-row">
                <label for="DoB">Ngày tháng năm sinh</label>
                <input type="date" name="DoB">
            </div>
            <div class="form-row">
                <label>Giới tính:</label>
                <div class="gender-options">
                    <label>
                        <input type="radio" name="sex" value="Nam"> Nam
                    </label>
                    <label>
                        <input type="radio" name="sex" value="Nữ"> Nữ
                    </label>
                </div>
            </div>
            <div class="form-button">
                <button type="submit">Cập nhật thông tin</button>
            </div>
            </form>
    </main>
    <?php include 'footer.html'; ?>
</body>
</html>