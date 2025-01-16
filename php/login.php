<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR nickname = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $username); 
     $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['nickname'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];


            header("Location: ../index.php");
            exit();
        } else {
            echo "Неверный пароль.";
        }
    } else {
        echo "Пользователь не найден.";
    }
}

?>
