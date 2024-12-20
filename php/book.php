<?php
session_start();
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

                    



<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['buy'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    $user_id = $_SESSION['user_id'];

        // Проверить цену книги с учётом скидки
        $price_to_pay = $discount > 0 ? $new_price : $old_price;

        // Получить текущий баланс пользователя
        $stmt = $conn->prepare("SELECT wallet FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $wallet = $user['wallet'];

            if ($wallet >= $price_to_pay) {
                // Списать средства
                $new_wallet = $wallet - $price_to_pay;

                $update_stmt = $conn->prepare("UPDATE users SET wallet = ? WHERE id = ?");
                $update_stmt->bind_param("di", $new_wallet, $user_id);

                if ($update_stmt->execute()) {
                    echo "<script>alert('Поздравляем с покупкой книги! Спасибо за покупку.');</script>";
                } else {
                    echo "<script>alert('Ошибка при обработке покупки. Пожалуйста, попробуйте снова.');</script>";
                }

                $update_stmt->close();
            } else {
                echo "<script>alert('Недостаточно средств на вашем балансе. Пополните счёт.');</script>";
            }
        } else {
            echo "<script>alert('Пользователь не найден. Пожалуйста, войдите снова.');</script>";
        }

        $stmt->close();
    } 
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($book['title']) ? htmlspecialchars($book['title']) : 'Книга не найдена'; ?> - XXXBookShop</title>
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
                    $stmt = $conn->prepare("SELECT wallet FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();

                    echo '<span id="auth-link"><i class="fas fa-user"></i> ' . $_SESSION['username'] . '</span>';
                    echo '<div id="user-menu">
                            <ul>
                                <li><a href="user.php"> Аккаунт</a></li>
                                <li><a href="#"><i class="fas fa-shopping-cart"></i> Корзина</a></li>
                                <li><a href="#"><i class="fas fa-wallet"></i> Кошелёк: </a></li>
                                <li><a href="#"></i> ' . $user['wallet'] . ' руб.</a></li>
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
                            include 'config.php';
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
                    <form method="POST" action="">
                            <input type="hidden" name="buy" value="1">
                             <button type="submit" class="buy-button">Купить</button>
                    </form>
                    </div>
                <?php else: ?>
                    <p>Книга не найдена.</p>
                <?php endif; ?>
            </section>
        <div class="slider-container"></div>
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
