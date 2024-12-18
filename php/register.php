<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nickname = $_POST['nickname'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    /*$first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $middle_name = $_POST['middle_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];*/

    if ($password !== $confirm_password) {
        echo "Пароли не совпадают.";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    /*$stmt = $conn->prepare("INSERT INTO users (nickname, first_name, last_name, middle_name, password, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nickname, $first_name, $last_name, $middle_name, $hashed_password, $email, $phone);*/

    $stmt = $conn->prepare("INSERT INTO users (nickname, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $nickname, $hashed_password);


    if ($stmt->execute()) {
        echo "Регистрация успешна.";
        header("Location: ../index.php");
    } else {
        echo "Ошибка регистрации: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
