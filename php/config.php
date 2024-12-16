<?php
$servername = "localhost";
$username = "root"; // По умолчанию в XAMPP
$password = ""; // По умолчанию в XAMPP
$dbname = "bookstore2"; // Имя твоей базы данных

// Создание подключения
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка подключения
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
/*echo "Connected successfully";*/
?>
