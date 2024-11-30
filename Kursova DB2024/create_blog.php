<?php
session_start();
include 'db.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php"); // Перенаправлення на реєстрацію, якщо користувач не увійшов
    exit;
}

// Отримання списку категорій з бази даних
$stmt = $conn->query("SELECT * FROM BlogCategories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обробка відправленої форми
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    // Перевірка наявності даних у формі
    $title = trim($_POST['title'] ?? null);
    $description = trim($_POST['description'] ?? null);
    $category_id = $_POST['category_id'] ?? null;

    // Перевірка, чи всі поля заповнені
    if ($title && $description && $category_id) {
        // Перевірка, чи користувач вже має роль Blogger
        $role_check = $conn->prepare("SELECT role_id FROM UserRoleAssignment WHERE user_id = ? AND role_id = 3");
        $role_check->execute([$user_id]);
        $role_exists = $role_check->fetchColumn();

        // Якщо користувач не має ролі Blogger, призначаємо її
        if (!$role_exists) {
            $stmt = $conn->prepare("INSERT INTO UserRoleAssignment (user_id, role_id) VALUES (?, ?)");
            try {
                $stmt->execute([$user_id, 3]); // Роль 3 — Blogger
            } catch (PDOException $e) {
                die("Помилка при призначенні ролі 'Блогер': " . $e->getMessage());
            }
        }

        // Додавання блогу в базу даних
        $stmt = $conn->prepare("INSERT INTO Blogs (user_id, title, description, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $title, $description]);

        // Отримання ID останнього доданого блогу
        $blog_id = $conn->lastInsertId();

        // Прив'язка блогу до обраної категорії
        $stmt = $conn->prepare("INSERT INTO BlogCategoryAssignment (blog_id, category_id) VALUES (?, ?)");
        $stmt->execute([$blog_id, $category_id]);

        // Перенаправлення на сторінку перегляду блогу
        header("Location: view_blog.php?blog_id=" . $blog_id);
        exit;
    } else {
        // Обробка помилки, якщо поля не заповнені
        $error_message = "Будь ласка, заповніть всі поля.";
    }
}

// Виведення форми створення блогу
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Створення блогу</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Cy Grotesk Wide', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('uploads/Black, Orange and Blue Gradient Graphic Design Portfolio Presentation (1).jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: scroll;
            color: #FFFFFF;
            background-position: center;
        }

        header {
            text-align: center;
            margin: 20px 0;
        }

        h1 {
            text-transform: uppercase;
            font-size: 2.5rem;
        }

        main {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: calc(100vh - 80px);
        }

        form {
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 15px;
            padding: 20px;
            width: 400px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column; /* Додаємо flex для центрування */
            align-items: center; /* Центруємо елементи по горизонталі */
            margin-top: -600;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-size: 1.1rem;
            text-transform: uppercase;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 1rem;
            resize: vertical; /* Дозволяє змінювати розмір текстового поля */
        }

        textarea {
            min-height: 100px; /* Мінімальна висота текстового поля */
            overflow-y: auto; /* Показує скрол при переповненні */
        }

        button {
            background-color: #FF5733;
            color: #FFFFFF;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%; /* Кнопка на всю ширину */
        }

        button:hover {
            background-color: #C70039;
        }
    </style>
</head>
<body>
    <header>
        <h1>Створити блог</h1>
    </header>
    <main>
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form method="POST" action="create_blog.php">
            <label for="title">Заголовок блогу:</label>
            <input type="text" name="title" required>

            <label for="description">Опис блогу:</label>
            <textarea name="description" required></textarea>

            <label for="category">Виберіть категорію:</label>
            <select name="category_id" required>
                <option value="">--Виберіть категорію--</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Створити блог</button>
        </form>
    </main>
</body>
</html>
