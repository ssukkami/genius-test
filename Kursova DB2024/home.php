<?php
session_start();
include 'db.php';

// Перевірка входу в систему
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Головне вікно</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        @font-face {
            font-family: 'Cy Grotesk Wide Bold';
            src: url('fonts/cy-grotesk-wide-bold.ttf') format('ttf');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: 'Cy Grotesk Wide Bold', sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('uploads/Black, Orange and Blue Gradient Graphic Design Portfolio Presentation.jpg'); /* Вкажіть шлях до фону */
            background-size: 100%; 
            background-position: center; 
            background-repeat: no-repeat; 
        }

        .container {
            display: flex;
            height: 100vh;
            position: relative; /* Додано для відносного позиціонування контейнера */
        }

        .menu {
            width: 300px; /* Ширина меню */
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px; /* Зменшено до 10px для менших відстаней */
            margin-left: 70px; /* Зсув меню вправо */
            margin-top: 160px;
        }

        .menu a {
            color: white;
            text-decoration: none;
            text-transform: uppercase;
            font-size: 40px; /* Розмір шрифту */
            font-weight: bold; /* Жирний шрифт */
            margin-bottom: 20px; /* Встановлено менший відступ */
        }
        .search-container {
    display: flex;
    align-items: center;
    position: absolute; /* Залиште абсолютне позиціонування */
    top: 55px; /* Відстань зверху */
    left: 100px; /* Вирівнювання максимально вліво */
}

.search-input {
    padding: 10px;
    font-size: 16px;
    border-radius: 5px;
    background-color: rgba(255, 255, 255, 0.2); /* Прозорий фон */
    color: white; /* Колір тексту */
    margin-right: 10px; /* Відступ між полем та кнопкою */
}

.search-button {
    background: none;
    border: none;
    color: white;
    font-size: 50px; /* Розмір шрифту для кнопки */
    cursor: pointer;
    position: absolute; /* Залишаємо абсолютне позиціонування */
    top: 0px; /* Відстань зверху, можна налаштувати за потребою */
    left: 1150px; /* Вирівнюємо кнопку на 300 пікселів від лівого краю */
}


    </style>
</head>
<body>
    <div class="container">
        <nav class="menu">
            <a href="form.php">Меню</a>
            <a href="profile.php">Мій профіль</a>
            <a href="register.php">Реєстрація</a>
            <?php if ($is_logged_in): ?>
                <a href="logout.php">Вихід</a>
            <?php else: ?>
                <a href="login.php">Вхід</a>
            <?php endif; ?>
        </nav>
        <div class="search-container">
            <form action="search.php" method="GET">
                <input type="text" name="query" class="search-input" placeholder="Введіть текст..." required>
                <button type="submit" class="search-button">&#9883;</button> <!-- Символ для кнопки пошуку -->
            </form>
        </div>
    </div>
</body>
</html>
