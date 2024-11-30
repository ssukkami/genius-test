<?php
session_start();
include 'db.php';

// Перенаправлення на вхід, якщо користувач не авторизований
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Отримуємо всі публікації користувача
$stmt = $conn->prepare("SELECT post_id, title FROM Posts WHERE user_id = ?");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обробка оновлення публікації
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    $post_id = intval($_POST['post_id']);
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $new_tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : []; // Розділяємо теги за комою

    // Оновлення заголовку та контенту публікації
    $stmt = $conn->prepare("UPDATE Posts SET title = ?, content = ? WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$title, $content, $post_id, $user_id]);

    // Видаляємо старі зв'язки тегів для публікації
    $stmt = $conn->prepare("DELETE FROM PostTags WHERE post_id = ?");
    $stmt->execute([$post_id]);

    // Обробка тегів
    foreach ($new_tags as $tag_name) {
        $tag_name = trim(ltrim($tag_name, '#')); // Видаляємо пробіли і початковий символ #

        if (!empty($tag_name)) {
            // Перевіряємо, чи існує вже цей тег
            $stmt = $conn->prepare("SELECT tag_id FROM Tags WHERE tag_name = ?");
            $stmt->execute([$tag_name]);
            $tag = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tag) {
                // Тег існує, використовуємо його ID
                $tag_id = $tag['tag_id'];
            } else {
                // Тег не існує, додаємо його в таблицю Tags
                $stmt = $conn->prepare("INSERT INTO Tags (tag_name) VALUES (?)");
                $stmt->execute([$tag_name]);
                $tag_id = $conn->lastInsertId(); // Отримуємо ID нового тегу
            }

            // Додаємо зв'язок між постом і тегом у PostTags
            $stmt = $conn->prepare("INSERT INTO PostTags (post_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $tag_id]);
        }
    }

    $message = "Публікацію оновлено успішно.";
}

// Перевірка, чи вибраний ID публікації для редагування
if (isset($_GET['post_id'])) {
    $post_id = intval($_GET['post_id']);
    $stmt = $conn->prepare("SELECT * FROM Posts WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die("Публікацію не знайдено.");
    }

    // Отримуємо теги для публікації
    $stmt = $conn->prepare("SELECT t.tag_name FROM PostTags pt JOIN Tags t ON pt.tag_id = t.tag_id WHERE pt.post_id = ?");
    $stmt->execute([$post_id]);
    $selected_tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    $post = null;
    $selected_tags = [];
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Редагувати публікацію</title>
    <link rel="stylesheet" href="styles.css">
    <style>
body {
    background-image: url('uploads/Black, Orange and Blue Gradient Graphic Design Portfolio Presentation (1).jpg');
    background-size: cover;
    background-repeat: no-repeat;
    background-attachment: scroll;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    background-position: center; /* Центрування фону */
}

header {
    position: fixed;
    top: 10px;
    left: 10px;
}

.menu-button {
    color: #FFFFFF;
    text-decoration: none;
    font-size: 1.2em;
    transition: color 0.3s ease;
}

.menu-button:hover {
    color: #FF5733; /* Помаранчевий відтінок при наведенні */
}

main {
    max-width: 600px;
    width: 90%;
    background-color: rgba(0, 0, 0, 0.8); /* Прозорий чорний фон */
    border-radius: 15px;
    padding: 30px;
    color: #FFFFFF; /* Білий текст */
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.8s ease-in-out; /* Анімація появи */
}

h2 {
    font-size: 1.8em;
    margin-bottom: 20px;
    text-align: center;
    text-transform: uppercase;
    color: #FFFFFF; /* Помаранчевий заголовок */
    border-bottom: 2px solid #FF5733;
    padding-bottom: 10px;
}

label {
    display: block;
    margin-bottom: 8px;
    text-transform: uppercase;
    font-weight: bold;
    color: #FFFFFF; /* Білий текст для підписів */
}

input[type="text"],
textarea,
select {
    width: 100%;
    padding: 12px;
    border: 1px solid #FF5733;
    border-radius: 8px;
    background-color: rgba(255, 255, 255, 0.1);
    color: #FFFFFF; /* Білий текст */
    font-size: 1em;
    margin-bottom: 15px;
    transition: border 0.3s ease, background-color 0.3s ease;
}

input[type="text"],
textarea,
select {
    width: 100%;
    padding: 12px;
    border: 1px solid #FF5733;
    border-radius: 8px;
    background-color: rgba(255, 255, 255, 0.1);
    color: #FFFFFF;
    font-size: 1em;
    margin-bottom: 15px;
    box-sizing: border-box; /* Ensures padding is included in width */
    transition: border 0.3s ease, background-color 0.3s ease;
}

textarea {
    height: 100px; /* Ensures uniform height for textarea */
}

button {
    width: 100%;
    padding: 12px;
    background-color: #FF5733;
    color: #FFFFFF;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-size: 1em;
    text-transform: uppercase;
    transition: background-color 0.3s ease, transform 0.2s ease;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.3);
    box-sizing: border-box; /* Ensures padding is included in width */
}


button:hover {
    background-color: #FFC300;
    transform: translateY(-2px); /* Легкий підйом кнопки */
}

button:active {
    transform: translateY(0); /* Повернення при натисканні */
}

.alert-message {
    background-color: rgba(255, 87, 51, 0.1); /* Помаранчевий прозорий фон */
    color: #FFFFFF; /* Білий текст */
    padding: 10px;
    border-left: 4px solid #FF5733;
    border-radius: 5px;
    margin-top: 15px;
    animation: slideDown 0.5s ease; /* Ефект появи */
}

/* Анімації */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

    </style>
</head>
<body>
<header>
    <a href="profile.php" class="menu-button">Назад до профілю</a>
</header>
<main>
    <h2>Редагувати публікацію</h2>

    <form method="GET">
        <label for="post_select">Виберіть публікацію для редагування:</label>
        <select name="post_id" id="post_select" onchange="this.form.submit()">
            <option value="">-- Виберіть публікацію --</option>
            <?php foreach ($posts as $p): ?>
                <option value="<?= $p['post_id'] ?>" <?= (isset($post) && $post['post_id'] == $p['post_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($post): ?>
        <form method="POST">
            <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
            <input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>
            <textarea name="content" required><?= htmlspecialchars($post['content']) ?></textarea>
            
            <label for="tags">Редагувати теги:</label>
            <input type="text" name="tags" value="<?= htmlspecialchars(implode(', ', $selected_tags)) ?>" required placeholder="#тег1, #тег2, #тег3">

            <button type="submit" name="update_post">Оновити</button>
        </form>
        <?php if ($message): ?>
            <div class="alert-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
    <?php else: ?>
        <p>Будь ласка, виберіть публікацію для редагування.</p>
    <?php endif; ?>
</main>
</body>
</html>
