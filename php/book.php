<?php
session_start();
include 'config.php';

if (isset($_GET['id'])) {
    $book_id = $_GET['id'];
    $sql = "SELECT * FROM books WHERE id = ?";
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


// Обработка формы покупки
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buy_book'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Получение баланса пользователя
    $stmt = $conn->prepare("SELECT wallet FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user['wallet'] < $new_price) {
        echo "<script>alert('Недостаточно средств на счёте.');</script>";
        exit();
    }

    // Получение данных книги и продавца
    $stmt = $conn->prepare("SELECT user_id, file_path FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $book_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $seller_id = $book_info['user_id'];
    $file_path = $book_info['file_path'];

    // Транзакция: списание денег у покупателя, начисление продавцу
    $conn->begin_transaction();
    try {
        // Списание средств у покупателя
        $stmt = $conn->prepare("UPDATE users SET wallet = wallet - ? WHERE id = ?");
        $stmt->bind_param("di", $new_price, $user_id);
        $stmt->execute();
        $stmt->close();

        // Начисление средств продавцу
        $stmt = $conn->prepare("UPDATE users SET wallet = wallet + ? WHERE id = ?");
        $stmt->bind_param("di", $new_price, $seller_id);
        $stmt->execute();
        $stmt->close();

        // Сохранение уведомления для продавца
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, book_id, message) VALUES (?, ?, ?)");
        $message = "Вашу книгу \"{$book['title']}\" купил пользователь с логином {$user_nickname}.";

        $stmt->bind_param("iis", $seller_id, $book_id, $message);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        // Скачивание файла книги
        if (file_exists($file_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit();
        } else {
            echo "<script>alert('Файл книги не найден.');</script>";
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Ошибка при обработке покупки. Попробуйте ещё раз.');</script>";
    }
}


// Обработка формы добавления оценки и отзыва
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rate'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }



    $rating = intval($_POST['rating']);
    $review = trim($_POST['review']);
    $user_id = $_SESSION['user_id'];

    if ($rating >= 1 && $rating <= 5) {
        $stmt = $conn->prepare("INSERT INTO reviews (book_id, user_id, rating, review) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $book_id, $user_id, $rating, $review);
        if ($stmt->execute()) {
            echo "<script>alert('Ваш отзыв добавлен!');</script>";
        } else {
            echo "<script>alert('Ошибка при добавлении отзыва.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Оценка должна быть от 1 до 5.');</script>";
    }
}

// Обработка удаления книги администратором
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_book'])) {
    
        $stmt = $conn->prepare("DELETE FROM reviews WHERE book_id = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $book_id);
        if ($stmt->execute()) {
            echo "<script>alert('Книга удалена!'); window.location.href = '../index.php';</script>";
        } else {
            echo "<script>alert('Ошибка при удалении книги.');</script>";
        }
        $stmt->close();
}

// Обработка установки скидки
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_discount'])) {
        $discount = intval($_POST['discount']);
        if ($discount >= 0 && $discount <= 100) {
            $stmt = $conn->prepare("UPDATE books SET discount = ? WHERE id = ?");
            $stmt->bind_param("ii", $discount, $book_id);
            if ($stmt->execute()) {
                echo "<script>alert('Скидка установлена!'); window.location.reload();</script>";
            } else {
                echo "<script>alert('Ошибка при установке скидки.');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Скидка должна быть в диапазоне от 0 до 100%.');</script>";
        }
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
                                <li><a href="#"><i class="fas fa-wallet"></i> Кошелёк: ' . $user['wallet'] . ' руб.</a></li>';
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
                            $genresQuery = "SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != ''";
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
                        
                        <img src="images/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    </div>
                    <div class="book-info">
                        <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                        <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                        <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                        <p><strong>Описание:</strong> <?php echo htmlspecialchars($book['description']); ?></p>
                        <?php echo '<h3 class="fas fa-star">' . htmlspecialchars($book['average_rating']) . '</h3>';?>
                        <?php if ($discount > 0): ?>
                            <p><strong>Цена:</strong> <del><?php echo htmlspecialchars($old_price); ?> руб.</del> <?php echo htmlspecialchars($new_price); ?> руб.</p>
                        <?php else: ?>
                            <p><strong>Цена:</strong> <?php echo htmlspecialchars($book['price']); ?> руб.</p>
                        <?php endif; ?>
                        <form method="POST">
                        <?php
                        if (isset($_SESSION['username'])) {
                            echo '<button type="submit" name="buy_book">Купить</button>';} else {
                            echo '<a href="#" onclick="showAuthForm()"><button type="submit" name="buy_book">Купить</button></a>';
                            }
                        ?>
                        </form>



                        <?php if ($user_role === 'admin'): ?>
                            <form method="POST">
                                <label for="discount">Установить скидку (%):</label>
                                <input type="number" id="discount" name="discount" min="0" max="100" required>
                                <button type="submit" name="set_discount">Применить</button>
                            </form>
                            <form method="POST">
                                <button type="submit" name="delete_book" style="color: red;">Удалить книгу</button>
                            </form>
                        <?php endif; ?>

                      
                        <h3>Добавить отзыв</h3>
                        <form method="POST">
                            <label for="rating">Оценка (1-5):</label>
                            <input type="number" id="rating" name="rating" min="1" max="5" required>
                            <label for="review">Отзыв:</label>
                            <textarea id="review" name="review" required></textarea>
                            <?php
                            if (isset($_SESSION['username'])) {
                                echo '<button type="submit" name="buy_book">Оставить отзыв</button>';} else {
                                echo '<a href="#" onclick="showAuthForm()"><button type="submit" name="buy_book">Оставить отзыв</button></a>';
                                }
                            ?>
                        </form>

                        <h3>Отзывы:</h3>
                        <ul>
                            <?php
                            $stmt = $conn->prepare("SELECT r.rating, r.review, u.nickname         FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.book_id = ?");

                            $stmt->bind_param("i", $book_id);
                            $stmt->execute();
                            $reviews = $stmt->get_result();
                            while ($review = $reviews->fetch_assoc()): ?>
                                <li><strong><?php echo htmlspecialchars($review['nickname']); ?>:</strong> <?php echo htmlspecialchars($review['review']); ?> (Оценка: <?php echo $review['rating']; ?>/5)</li>
                            <?php endwhile; ?>
                        </ul>
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
