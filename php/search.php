<?php
session_start();
include 'config.php';

$query = isset($_GET['query']) ? $_GET['query'] : '';
$genre = isset($_GET['genre']) ? $_GET['genre'] : '';
$discount = isset($_GET['discount']) ? $_GET['discount'] : '';

$sql = "SELECT * FROM books WHERE 1=1";
$params = [];
if (!empty($query)) {
    $sql .= " AND (title LIKE ? OR author LIKE ?)";
    $params[] = "%$query%";
    $params[] = "%$query%";
}

if (!empty($genre)) {
    $sql .= " AND genre = ?";
    $params[] = $genre;
}

if (!empty($discount)) {
    $sql .= " AND discount > 0";
}
$conn->close();
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
                        <a href="#">Жанры</a>
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
        <section id="content">
            <section id="books">
                    <div id="book-container" class="book-grid">
                        <?php

                        echo '<div class="category">';
                        if ($discount > 0) {
                        echo '<h2>  Акции  </h2>';
                        }
                        else {
                        echo '<h2>' . htmlspecialchars($genre) . '</h2>';
                        }
                        echo '<div class="book-grid">';

                        $stmt = $conn->prepare($sql);
                        if ($params) {
                            $stmt->bind_param(str_repeat("s", count($params)), ...$params);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<div class="book">';
                                    echo '<a href="book.php?id=' . $row['id'] . '">';
                                    echo '<img src="images/' . htmlspecialchars($row['image']) . '" alt="' . htmlspecialchars($row['title']) . '">';
                                    echo '</a>';
                                    echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
                                    echo '<p>' . htmlspecialchars($row['author']) . '</p>';
                                    if ($row['discount'] > 0) {
                                        $old_price = $row['price'];
                                        $new_price = $old_price - ($old_price * $row['discount'] / 100);
                                        echo '<p><del>' . $old_price . ' руб.</del> ' . $new_price . ' руб.</p>';
                                    } else {
                                        echo '<p>' . $row['price'] . ' руб.</p>';
                                    }
                                    echo '<h3 class="fas fa-star">' . htmlspecialchars($row['average_rating']) . '</h3>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p>Ничего не найдено.</p>';
                            }
                        ?>
                    </div>
                </section>
        </section>

            <div class="slider-container"></div>
        </main>
        <footer>
            <p>&copy; 2024 XXXBookShop</p>
        </footer>
    </div>
    <script src="../js/scripts.js"></script>
</body>
</html>