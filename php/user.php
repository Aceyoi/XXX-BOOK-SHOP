<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Запрос данных пользователя
    $stmt = $conn->prepare("SELECT last_name, first_name, middle_name, phone, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Записываем данные в сессию
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['middle_name'] = $user['middle_name'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['email'] = $user['email'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $update_fields = [];
    $params = [];

    if (!empty($_POST['nickname'])) {
        $update_fields[] = "nickname = ?";
        $params[] = $_POST['nickname'];
    }
    if (!empty($_POST['first_name'])) {
        $update_fields[] = "first_name = ?";
        $params[] = $_POST['first_name'];
    }
    if (!empty($_POST['last_name'])) {
        $update_fields[] = "last_name = ?";
        $params[] = $_POST['last_name'];
    }
    if (!empty($_POST['middle_name'])) {
        $update_fields[] = "middle_name = ?";
        $params[] = $_POST['middle_name'];
    }
    if (!empty($_POST['email'])) {
        $update_fields[] = "email = ?";
        $params[] = $_POST['email'];
    }
    if (!empty($_POST['phone'])) {
        $update_fields[] = "phone = ?";
        $params[] = $_POST['phone'];
    }
    if (!empty($_POST['wallet'])) {
        $update_fields[] = "wallet = ?";
        $params[] = $_POST['wallet'];
    }
    if (!empty($_POST['password'])) {
        if ($_POST['password'] !== $_POST['confirm_password']) {
            echo "Пароли не совпадают.";
            exit();
        }
        $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $update_fields[] = "password = ?";
        $params[] = $hashed_password;
    }

    if (!empty($update_fields)) {
        $params[] = $user_id;
        $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);

        // Генерация типов параметров (например, "sssssssi")
        $types = str_repeat("s", count($params) - 1) . "i";
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            /*echo "Данные успешно обновлены.";*/
        } else {
            echo "Ошибка обновления данных: " . $stmt->error;
        }

        $stmt->close();
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_editor'])) {
    // Проверяем, что пользователь уже не является редактором
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

        // Обновляем роль пользователя
        $stmt = $conn->prepare("UPDATE users SET role = 'editor' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $_SESSION['user_role'] = 'editor'; // Обновляем данные в сессии
        } else {
            echo "Ошибка: " . $stmt->error;
        }
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

        if (!empty($_POST['nickname'])) {
            $update_fields[] = "nickname = ?";
            $params[] = $_POST['nickname'];
        
            // Обновляем значение в сессии
            $_SESSION['username'] = $_POST['nickname'];
        }
        
        if (isset($_SESSION['username'])) {
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("SELECT role, wallet FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $user_role = $user['role'];

            echo '<span id="auth-link"><i class="fas fa-user"></i> ' . $_SESSION['username'] . '</span>';
            echo '<div id="user-menu">
                    <ul>
                        <li><a href="user.php"> Аккаунт</a></li>
                        <li><a href="#"><i class="fas fa-wallet"></i> Кошелёк: </a></li>
                        <li><a href="#"></i> ' . $user['wallet'] . ' руб.</a></li>';
            if ($user_role === 'editor' || $user_role === 'admin') {
                echo '<li><a href="editor.php"><i class="fas fa-plus-circle"></i> Выставить книгу</a></li>';}
                echo '<li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a></li>
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
                        <a href="#">Акции</a>
                        <ul class="submenu" id="categories-submenu">
                        <?php
                            $genresQuery = "SELECT DISTINCT discount FROM books";
                            $genresResult = $conn->query($genresQuery);

                            if ($genresResult->num_rows > 0) {
                                    echo '<li><a href="search.php?discount=true'  . '" class="fas fa-tags"> ' . "Скидки" . '</a></li>';
                            } else {
                                echo '<li>Нет доступных акций</li>';
                            }
                            ?>
                        </ul>
                    </li>
                </ul>
            </aside>
            <form action="user.php" method="POST" class="user">
            <h2>    
                <?php  echo isset($_SESSION['last_name']) ? htmlspecialchars($_SESSION['last_name']) : 'Не указано';?><br><br>
                <?php  echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'Не указано';?><br><br>
                <?php  echo isset($_SESSION['middle_name']) ? htmlspecialchars($_SESSION['middle_name']) : 'Не указано';?><br><br>
            </h2>
                <label for="phone">Телефон:</label>
                <?php echo isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : 'Не указан'; ?><br><br>
                <label for="email">Почта:</label>
                <?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'Не указана'; ?><br><br>
            </form>
    <form action="user.php" method="POST" class="user">
    <h2>Изменить данные</h2>
        <label for="new_username">Новый логин:</label>
        <input type="text" id="register-nickname" name="nickname" ><br><br>

        <label for="new_first_name">Имя:</label>
        <input type="text" id="register-first-name" name="first_name" ><br><br>

        <label for="new_last_name">Фамилия:</label>
        <input type="text" id="register-last-name" name="last_name" ><br><br>

        <label for="new_middle_name">Отчество:</label>
        <input type="text" id="register-middle-name" name="middle_name" ><br><br>

        <label for="new_phone">Телефон:</label>
        <input type="text" id="register-phone" name="phone" ><br><br>

        <label for="new_email">Почта:</label>
        <input type="email" id="register-email" name="email" ><br><br>

        <label for="new_password">Новый пароль:</label>
        <input type="password" id="register-password" name="password" ><br><br>

        <label for="confirm_password">Подтвердите новый пароль:</label>
        <input type="password" id="register-confirm-password" name="confirm_password" ><br><br>

        <input type="submit" value="Сохранить изменения">
    </form>
    <form action="user.php" method="POST" class="user">
    <h2>GreedIsGood</h2>
        <label for="money">KeyserSoze :</label>
        <input type="text" id="money" name="wallet" ><br><br>

        <input type="submit" value="oy yes">

    <h2>Стать редактором</h2>
    <input type="hidden" name="make_editor" value="1">
    <input type="submit" value="Да">
</form>

    <div class="slider-container"></div>
    </main>
        <footer>
            <p>&copy; 2024 XXXBookShop</p>
        </footer>
    </div>
    <script src="../js/scripts.js"></script>
</body>
</html>

