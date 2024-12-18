<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nickname = $_POST['nickname'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $middle_name = $_POST['middle_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if ($new_password !== $confirm_password) {
        echo "Пароли не совпадают.";
        exit();
    }

    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("UPDATE users SET nickname = ?, password = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nickname, $hashed_password, $user_id);

    if ($stmt->execute()) {
        /*echo "Данные успешно обновлены.";*/
    } else {
        echo "Ошибка обновления данных: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XXXBookShop</title>
    <link rel="icon" href="../icon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../icon.ico" type="image/x-icon">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
        <div class="search-container">
                    <form id="search-form" action="search.php" method="GET">
                        <input type="text" id="search-input" name="query" placeholder="Поиск..." required>
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>

        <h1><a href="../index.php"><img src="../images/logo2.png" alt="Logo" class="logo"></a></h1>
            <div class="auth-container">
            <?php
                include 'config.php';

                if (isset($_SESSION['username'])) {
                    $user_id = $_SESSION['user_id'];
                    $stmt = $conn->prepare("SELECT wallet FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();

                    echo '<span id="auth-link"><i class="fas fa-user"></i> ' . $_SESSION['username'] . '</span>';
                    echo '<div id="user-menu">
                            <ul>
                                <li><a href="#"><i class="fas fa-shopping-cart"></i> Корзина</a></li>
                                <li><a href="#"><i class="fas fa-wallet"></i> Кошелёк: ' . $user['wallet'] . ' руб.</a></li>
                                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a></li>
                            </ul>
                          </div>';
                } else {
                    echo '<span id="auth-link"><a href="#" onclick="showAuthForm()"><i class="fas fa-user"></i> Войти</a></span>';
                }
                ?>
            </div>
        </header>
        <main>
        <aside class="sidebar">
                <ul class="sidebar-menu">
                    <li>
                        <i class="fas fa-book"></i>
                        <a href="../index.php">Книги</a>
                    </li>
                    <li id="categories-item">
                        <i class="fas fa-list"></i>
                        <a href="#">Категории</a>
                        <ul class="submenu" id="categories-submenu">
                            <?php
                            $genresQuery = "SELECT DISTINCT genre FROM books";
                            $genresResult = $conn->query($genresQuery);

                            if ($genresResult->num_rows > 0) {
                                while ($genreRow = $genresResult->fetch_assoc()) {
                                    echo '<li><a href="search.php?genre=' . urlencode($genreRow['genre']) . '" class="genre-link"><i class="fas fa-book-open"></i> ' . htmlspecialchars($genreRow['genre']) . '</a></li>';
                                }
                            } else {
                                echo '<li>Нет доступных жанров</li>';
                            }
                            ?>
                        </ul>
                    </li>
                    <li>
                        <i class="fas fa-tags"></i>
                        <a href="search.php?discount=true">Акции</a>
                    </li>
                </ul>
            </aside>

    <form action="user.php" method="POST">
    <h2>Изменить данные</h2>
        <label for="new_username">Новый логин:</label>
        <input type="text" id="register-nickname" name="nickname" required><br><br>

        <label for="new_nickname">Имя:</label>
        <input type="text" id="register-first-name" name="first_name" required><br><br>

        <label for="new_nickname">Фамилия:</label>
        <input type="text" id="register-last-name" name="last_name" required><br><br>

        <label for="new_nickname">Отчество:</label>
        <input type="text" id="register-middle-name" name="middle_name" required><br><br>

        <label for="new_nickname">Телефон:</label>
        <input type="text" id="register-phone" name="phone" required><br><br>

        <label for="new_nickname">Почта:</label>
        <input type="email" id="register-email" name="email" required><br><br>

        <label for="new_password">Новый пароль:</label>
        <input type="password" id="register-password" name="password" required><br><br>

        <label for="confirm_password">Подтвердите новый пароль:</label>
        <input type="password" id="register-confirm-password" name="confirm_password" required><br><br>

        <input type="submit" value="Сохранить изменения">
    </form>
    </main>
        <footer>
            <p>&copy; 2024 XXXBookShop</p>
        </footer>
    </div>
    <div id="auth-form" style="display: none;">
        <form id="login-form" action="login.php" method="POST" style="display: block;">
            <h2>Вход</h2>
            <input type="text" id="login-username" name="username" placeholder="Логин или Email" required>
            <input type="password" id="login-password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
            <button type="button" onclick="showRegisterForm()">Зарегистрироваться</button>
        </form>
        <form id="register-form" action="register.php" method="POST" style="display: none;">
            <h2>Регистрация</h2>
            <input type="text" id="register-nickname" name="nickname" placeholder="Логин" required>
            <input type="password" id="register-password" name="password" placeholder="Пароль" required>
            <input type="password" id="register-confirm-password" name="confirm_password" placeholder="Подтвердите пароль" required>
            <button type="submit">Зарегистрироваться</button>
            <button type="button" onclick="showLoginForm()">Войти</button>
        </form>
    </div>
    <script src="../js/scripts.js"></script>
</body>
</html>

