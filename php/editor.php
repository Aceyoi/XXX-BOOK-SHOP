<?php
session_start();
include 'config.php';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? null;
    $author = $_POST['author'] ?? null;
    $price = $_POST['price'] ?? null;
    $description = $_POST['description'] ?? null;
    $genre = $_POST['genre'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;

    // Пути для сохранения
    $image_path = null;
    $book_path = null;

    // Обработка загрузки изображения
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $image_name = uniqid() . '_' . basename($_FILES['image_file']['name']);
        $image_path = $image_name;

        if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $image_path)) {
            echo "<p style='color:red;'>Ошибка загрузки изображения!</p>";
            $image_path = null;
        }
    }

    // Обработка загрузки файла книги
    if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] == UPLOAD_ERR_OK) {
        $book_name = uniqid() . '_' . basename($_FILES['book_file']['name']);
        $book_path = 'books/' . $book_name;

        if (!move_uploaded_file($_FILES['book_file']['tmp_name'], $book_path)) {
            echo "<p style='color:red;'>Ошибка загрузки файла книги!</p>";
            $book_path = null;
        }
    }

    // Проверяем успешность загрузки перед записью в базу данных
    if ($image_path && $book_path) {
        $stmt = $conn->prepare("INSERT INTO books (user_id, title, author, price, description, genre, image, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdssss", $user_id, $title, $author, $price, $description, $genre, $image_path, $book_path);

        if ($stmt->execute()) {
        } else {
            echo "<p style='color:red;'>Ошибка выполнения запроса: " . $stmt->error . "</p>";
        }

        $stmt->close();
    } else {
        echo "<p style='color:red;'>Ошибка: изображение или файл книги не были загружены!</p>";
    }
        $stmt->close();
}


// Получение всех книг, добавленных текущим пользователем
$user_id = $_SESSION['user_id'] ?? null;
$books = [];

if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }

    $stmt->close();
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
                        echo '<li><a href="editor.php"><i class="fas fa-plus-circle"></i> Выставить книгу</a></li>';
                    }
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
            <form method="POST" action="editor.php" class="user" enctype="multipart/form-data">
            <h1>Выставить свою книгу</h1>
                <h3><label for="title">Название:</label>
                <input type="text" id="title" name="title" required><br></h3>

                <h3><label for="author">Автор:</label>
                <input type="text" id="author" name="author" required><br></h3>

                <h3><label for="price">Цена:</label>
                <input type="number" id="price" name="price" step="0.01" required><br></h3>

                <h3><label for="description">Описание:</label>
                <textarea id="description" name="description"></textarea><br></h3>

                <h3><label for="genre">Жанр:</label>
                <input type="text" id="genre" name="genre"><br></h3>

                <h3><label for="image_file">Изображение книги:</label>
                <input type="file" id="image_file" name="image_file" accept="image/*"><br></h3>

                <h3><label for="book_file">Файл книги:</label>
                <input type="file" id="book_file" name="book_file" accept=".pdf,.epub,.txt"><br></h3>

                <h3><button type="submit">Добавить книгу</button></h3>
            </form>
            <form method="POST" action="editor.php" class="user">
                <h1>Мои книги</h1>
                <?php
                    if (!empty($books)) {
                        foreach ($books as $book) {
                            echo '<div class="book">';
                            echo '<a href="book.php?id=' . $book['id'] . '">';
                            echo '<h3>' . htmlspecialchars($book['title']) . '</h3>';
                            echo '<p><strong>Цена:</strong> ' . htmlspecialchars($book['price']) . ' руб.</p>';
                            echo '</a>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>Вы еще не добавили ни одной книги.</p>';
                    }
                    ?>
            </form>
            <form method="POST" action="editor.php" class="user">
                <h1>Сообщения о покупке</h1>
                <?php
                    $stmt = $conn->prepare("SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $notifications = $result->fetch_all(MYSQLI_ASSOC);
                    if (!empty($notifications)) {
                        foreach ($notifications as $notification) {
                            echo '<div class="notification">';
                            echo '<p>' . htmlspecialchars($notification['message']) . '</p>';
                            echo '<p><strong>Дата:</strong> ' . htmlspecialchars($notification['created_at']) . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>У вас нет уведомлений.</p>';
                    }
                    ?>
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