<?php
$servername = "localhost";
$username = "root"; // По умолчанию в XAMPP
$password = ""; // По умолчанию в XAMPP
$dbname = "bookstore"; // Имя твоей базы данных

// Создание подключения
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверка подключения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
/*echo "Connected successfully";*/
?>
