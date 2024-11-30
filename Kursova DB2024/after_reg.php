<?php
session_start();
include 'db.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php"); // Перенаправлення на реєстрацію, якщо користувач не увійшов
    exit;
}

// Отримання ID користувача
$user_id = $_SESSION['user_id'];

// Перевірка, чи вже є призначена роль для користувача
$role_check = $conn->prepare("SELECT role_id FROM UserRoleAssignment WHERE user_id = ?");
$role_check->execute([$user_id]);
$role_exists = $role_check->fetchAll(PDO::FETCH_COLUMN);

// Якщо ролі немає, призначаємо роль "Visitor" за замовчуванням
if (!in_array(4, $role_exists)) { // Якщо роль Visitor не призначена (4 — роль Visitor)
    $stmt = $conn->prepare("INSERT INTO UserRoleAssignment (user_id, role_id) VALUES (?, ?)");
    try {
        $stmt->execute([$user_id, 4]); // Роль 4 — Visitor
    } catch (PDOException $e) {
        die("Помилка при призначенні ролі 'Visitor': " . $e->getMessage());
    }
}

// Перевірка, чи користувач вже має блог
$blog_check = $conn->prepare("SELECT COUNT(*) FROM Blogs WHERE user_id = ?");
$blog_check->execute([$user_id]);
$has_blog = $blog_check->fetchColumn() > 0;

// Обробка відправленої форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Перевірка, чи користувач вибрав створити блог або переглядати інші блоги
    if (isset($_POST['create_blog'])) {
        // Якщо блогу немає і роль "Блогер" ще не призначена
        if (!$has_blog && !in_array(3, $role_exists)) { 
            // Переконайтеся, що роль "Блогер" існує в таблиці UserRoles
            $role_check_blogger = $conn->prepare("SELECT COUNT(*) FROM UserRoles WHERE role_id = ?");
            $role_check_blogger->execute([3]); // Роль 3 — Blogger
            if ($role_check_blogger->fetchColumn() > 0) { // Якщо роль Blogger існує
                // Додайте перевірку на існування ролі перед вставкою
                $check_existing = $conn->prepare("SELECT COUNT(*) FROM UserRoleAssignment WHERE user_id = ? AND role_id = ?");
                $check_existing->execute([$user_id, 3]);
                if ($check_existing->fetchColumn() == 0) { // Якщо така роль ще не призначена
                    $stmt = $conn->prepare("INSERT INTO UserRoleAssignment (user_id, role_id) VALUES (?, ?)");
                    try {
                        $stmt->execute([$user_id, 3]); // Роль 3 — Blogger
                    } catch (PDOException $e) {
                        die("Помилка при призначенні ролі 'Блогер': " . $e->getMessage());
                    }
                } else {
                    die("Роль 'Блогер' вже призначена.");
                }
            } else {
                die("Роль 'Блогер' не існує.");
            }
        }

        // Перенаправлення на створення блогу
        header("Location: create_blog.php");
        exit;
    } elseif (isset($_POST['browse_blogs'])) {
        // Перенаправлення на перегляд інших блогів
        header("Location: browse_blogs.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Після реєстрації</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('uploads/Black, Orange and Blue Gradient Graphic Design Portfolio Presentation (5).jpg'); /* Задайте ваше фонове зображення */
            background-size: cover; /* Займати весь доступний простір */
            background-position: center; /* Центрувати фонове зображення */
            margin: 0;
            padding: 0;
            height: 100vh; /* Висота viewport */
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column; /* Додано для вертикального вирівнювання */
        }

        header {
            text-align: center;
            color: #fff;
            margin-bottom: 20px; /* Відступ під заголовком */
            margin-top: -50px;
        }

        h1 {
            font-size: 2.5em;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7); /* Тінь для заголовка */
        }

 

main {
    background-color: rgba(0, 0, 0, 0.8); /* Чорний фон з прозорістю */
    border-radius: 25px; /* Збільшена округлість */
    padding: 40px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
    text-align: center;
    width: 90%; /* Ширина в процентах для адаптивності */
    max-width: 600px; /* Максимальна ширина */
    margin-top: 20px; /* Додано відступ зверху для переміщення контейнера вниз */
}


        h2 {
            color: #fff; /* Колір заголовка */
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        button {
            background-color: #808080; /* Сірий колір для кнопок */
            color: white;
            padding: 15px 20px; /* Збільшений відступ кнопки */
            border: none;
            border-radius: 5px; /* Заокруглення кнопки */
            cursor: pointer;
            font-size: 1.2em; /* Збільшений розмір шрифту для кнопок */
            transition: background-color 0.3s ease; /* Плавний перехід кольору фону */
            width: 100%; /* Кнопки на всю ширину */
            margin: 10px 0; /* Відступ між кнопками */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3); /* Тінь для кнопки */
        }

        button:hover {
            background-color: #696969; /* Темніший сірий при наведенні */
        }

        @media (max-width: 600px) {
            main {
                padding: 20px; /* Зменшений відступ для менших екранів */
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Вітаємо!</h1>
    </header>
    <main>
        <form method="POST" action="">
            <h2>Що ви хочете зробити?</h2>
            <button type="submit" name="create_blog">Створити блог</button>
            <button type="submit" name="browse_blogs">Переглядати інші блоги</button>
        </form>
    </main>
</body>
</html>
