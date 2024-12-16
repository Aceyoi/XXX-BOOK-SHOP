<?php
include 'config.php';

if (isset($_GET['id'])) {
    $book_id = $_GET['id'];
    $sql = "SELECT books.*, discounts.discount FROM books LEFT JOIN discounts ON books.id = discounts.book_id WHERE books.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        $discount = isset($book['discount']) ? $book['discount'] : 0;
        $old_price = $book['price'];
        $new_price = $old_price - ($old_price * $discount / 100);
    } else {
        echo "Книга не найдена.";
        exit();
    }
} else {
    echo "Неверный запрос.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($book['title']) ? htmlspecialchars($book['title']) : 'Книга не найдена'; ?> - XXXBookShop</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
        <div class="search-container">
                    <form id="search-form" action="php/search.php" method="GET">
                        <input type="text" id="search-input" name="query" placeholder="Поиск..." required>
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
        <h1><a href="../index.php"><img src="../images/logo2.png" alt="Logo" class="logo"></a></h1>
        <div class="auth-container">
                <?php
                session_start();

                if (isset($_SESSION['username'])) {
                    echo '<span id="auth-link"><i class="fas fa-user"></i> ' . $_SESSION['username'] . '</span>';
                    echo '<div id="user-menu">
                            <ul>
                                <li><a href="#"><i class="fas fa-shopping-cart"></i> Корзина</a></li>
                                <li><a href="#"><i class="fas fa-wallet"></i> Кошелёк</a></li>
                                <li><a href="../php/logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a></li>
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
                            include '../php/config.php';
                            $genresQuery = "SELECT DISTINCT genre FROM books";
                            $genresResult = $conn->query($genresQuery);

                            if ($genresResult->num_rows > 0) {
                                while ($genreRow = $genresResult->fetch_assoc()) {
                                    echo '<li><a href="../php/search.php?genre=' . urlencode($genreRow['genre']) . '" class="genre-link"><i class="fas fa-book-open"></i> ' . htmlspecialchars($genreRow['genre']) . '</a></li>';
                                }
                            } else {
                                echo '<li>Нет доступных жанров</li>';
                            }
                            ?>
                        </ul>
                    </li>
                    <li>
                        <i class="fas fa-tags"></i>
                        <a href="../php/search.php?discount=true">Акции</a>
                    </li>
                </ul>
            </aside>
            <section id="book-details">
                <?php if (isset($book)): ?>
                    <div class="book-image">
                        <img src="../images/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    </div>
                    <div class="book-info">
                        <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                        <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                        <?php if ($discount > 0): ?>
                            <p><strong>Цена:</strong> <del><?php echo htmlspecialchars($old_price); ?> руб.</del> <?php echo htmlspecialchars($new_price); ?> руб.</p>
                        <?php else: ?>
                            <p><strong>Цена:</strong> <?php echo htmlspecialchars($book['price']); ?> руб.</p>
                        <?php endif; ?>
                        <p><strong>Описание:</strong> <?php echo htmlspecialchars($book['description']); ?></p>
                        <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                        <button class="buy-button">Купить</button>
                    </div>
                <?php else: ?>
                    <p>Книга не найдена.</p>
                <?php endif; ?>
            </section>
        </main>
        <footer>
            <p>&copy; 2024 XXXBookShop</p>
        </footer>
    </div>
    <div id="auth-form" style="display: none;">
        <form id="login-form" action="../php/login.php" method="POST" style="display: block;">
            <h2>Вход</h2>
            <input type="text" id="login-username" name="username" placeholder="Логин или Email" required>
            <input type="password" id="login-password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
            <button type="button" onclick="showRegisterForm()">Зарегистрироваться</button>
        </form>
        <form id="register-form" action="../php/register.php" method="POST" style="display: none;">
            <h2>Регистрация</h2>
            <input type="text" id="register-nickname" name="nickname" placeholder="Ник" required>
            <input type="text" id="register-username" name="username" placeholder="Логин" required>
            <input type="password" id="register-password" name="password" placeholder="Пароль" required>
            <input type="password" id="register-confirm-password" name="confirm_password" placeholder="Подтвердите пароль" required>
            <input type="text" id="register-first-name" name="first_name" placeholder="Имя" required>
            <input type="text" id="register-last-name" name="last_name" placeholder="Фамилия" required>
            <input type="text" id="register-middle-name" name="middle_name" placeholder="Отчество">
            <input type="email" id="register-email" name="email" placeholder="Email" required>
            <input type="text" id="register-phone" name="phone" placeholder="Телефон">
            <button type="submit">Зарегистрироваться</button>
            <button type="button" onclick="showLoginForm()">Войти</button>
        </form>
    </div>
    <script src="../js/scripts.js"></script>
</body>
</html>
