<?php
// $host = "localhost";
// $dbname = "music4life";
// $username = "root";
// $password = "";

try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=music4life2;charset=utf8mb4",
        "root",
        ""
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  //Neu xay ra loi thi nem ra PDOException
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); //Tra ve ket qua dang mang ket hop, lay ten cot lam key

} catch (PDOException $e) {
    die("Loi ket noi database: " . $e->getMessage());
}

function getDB(): PDO {
    global $conn;
    return $conn;
}
