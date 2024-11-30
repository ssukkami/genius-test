<?php
session_start();
include 'db.php';

// Перевірка, чи користувач адміністратор
if (!isset($_SESSION['user_id'])) {
    echo "Доступ заборонено. Ви не авторизовані.";
    exit;
}

$role_stmt = $conn->prepare("SELECT role_id FROM UserRoleAssignment WHERE user_id = ? AND role_id = 1");
$role_stmt->execute([$_SESSION['user_id']]);
$is_admin = $role_stmt->fetch(PDO::FETCH_ASSOC);

// Функція для отримання публікацій або блогів, які не заблоковані для звичайних користувачів
function getPosts($conn, $is_admin) {
    if ($is_admin) {
        // Якщо адміністратор, показуємо всі публікації, включаючи заблоковані
        $stmt = $conn->query("SELECT post_id, title, content FROM Posts");
    } else {
        // Якщо не адміністратор, показуємо тільки незаблоковані публікації
        $stmt = $conn->query("SELECT post_id, title, content FROM Posts WHERE deactivated_at IS NULL");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getBlogs($conn, $is_admin) {
    if ($is_admin) {
        // Якщо адміністратор, показуємо всі блоги, включаючи заблоковані
        $stmt = $conn->query("SELECT blog_id, title FROM Blogs");
    } else {
        // Якщо не адміністратор, показуємо тільки незаблоковані блоги
        $stmt = $conn->query("SELECT blog_id, title FROM Blogs WHERE deactivated_at IS NULL");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUsers($conn, $is_admin) {
    if ($is_admin) {
        // Якщо адміністратор, показуємо всі користувачів, включаючи заблокованих
        $stmt = $conn->query("SELECT user_id, username FROM Users");
    } else {
        // Якщо не адміністратор, показуємо тільки незаблокованих користувачів
        $stmt = $conn->query("SELECT user_id, username FROM Users WHERE deactivated_at IS NULL");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getComments($conn, $is_admin) {
    if ($is_admin) {
        // Якщо адміністратор, показуємо всі коментарі, включаючи заблоковані
        $stmt = $conn->query("SELECT comment_id, content FROM Comments");
    } else {
        // Якщо не адміністратор, показуємо тільки незаблоковані коментарі
        $stmt = $conn->query("SELECT comment_id, content FROM Comments WHERE deactivated_at IS NULL");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



// Блокування публікації
if (isset($_POST['block_post']) && !empty($_POST['post_id'])) {
    $post_id = (int)$_POST['post_id'];
    $block_type_id = 2; // Тип блокування для публікацій
    $reason = $_POST['reason'];

    // Отримуємо user_id автора публікації
    $user_stmt = $conn->prepare("SELECT user_id FROM Posts WHERE post_id = ?");
    $user_stmt->execute([$post_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_id = $user['user_id'];

        // Блокуємо публікацію (записуємо деактивацію)
        $stmt_deactivate = $conn->prepare("UPDATE Posts SET deactivated_at = NOW() WHERE post_id = ?");
        $stmt_deactivate->execute([$post_id]);

        // Додаємо запис в таблицю блокувань
        $stmt_block = $conn->prepare("INSERT INTO userblocks (user_id, blocked_by, block_type_id, reason, blocked_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt_block->execute([$user_id, $_SESSION['user_id'], $block_type_id, $reason])) {
            echo "Публікація заблокована.";
        } else {
            echo "Не вдалося заблокувати публікацію.";
        }
    } else {
        echo "Публікацію не знайдено.";
    }
}

// Блокування блогу
if (isset($_POST['block_blog']) && !empty($_POST['blog_id'])) {
    $blog_id = (int)$_POST['blog_id'];
    $block_type_id = 3; // Тип блокування для блогів
    $reason = $_POST['reason'];

    if (empty($reason)) {
        echo "Причину блокування потрібно вказати.";
        exit;
    }
    // Отримуємо user_id автора блогу
    $user_stmt = $conn->prepare("SELECT user_id FROM Blogs WHERE blog_id = ?");
    $user_stmt->execute([$blog_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_id = $user['user_id'];

        // Блокуємо блог (записуємо деактивацію)
        $stmt_deactivate = $conn->prepare("UPDATE Blogs SET deactivated_at = NOW() WHERE blog_id = ?");
        $stmt_deactivate->execute([$blog_id]);

        // Додаємо запис в таблицю блокувань
        $stmt_block = $conn->prepare("INSERT INTO userblocks (user_id, blocked_by, block_type_id, reason, blocked_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt_block->execute([$user_id, $_SESSION['user_id'], $block_type_id, $reason])) {
            echo "Блог заблокований.";
        } else {
            echo "Не вдалося заблокувати блог.";
        }
    } else {
        echo "Блог не знайдено.";
    }
}

// Видалення користувача
if (isset($_POST['delete_user']) && !empty($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $delete_reason = trim($_POST['reason']);

    if (empty($delete_reason)) {
        echo "Причину видалення потрібно вказати.";
        exit;
    }

    try {
        $conn->beginTransaction();

        // Перевірка, чи існує користувач 
        $user_stmt = $conn->prepare("SELECT username FROM Users WHERE user_id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Видалення користувача
            $stmt_delete = $conn->prepare("UPDATE Users SET deactivated_at = NOW() WHERE user_id= ?");
            $stmt_delete->execute([$user_id]);

            // Додавання запису в таблицю блокувань або історії видалень
            $block_type_id = 1; // Тип блокування для видалених користувачів
            $stmt_log_delete = $conn->prepare("INSERT INTO userblocks (user_id, blocked_by, block_type_id, reason, blocked_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt_log_delete->execute([$user_id, $_SESSION['user_id'], $block_type_id, $delete_reason])) {
                echo "Користувач успішно видалений.";
            } else {
                echo "Не вдалося додати запис про видалення користувача.";
            }
        } else {
            echo "Користувача не знайдено.";
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        echo "Сталася помилка: " . $e->getMessage();
    }
}

// Блокування коментаря
if (isset($_POST['block_comment']) && !empty($_POST['comment_id'])) {
    $comment_id = (int)$_POST['comment_id'];
    $block_type_id = 4; // Тип блокування для коментарів
    $reason = $_POST['reason'];

    if (empty($reason)) {
        echo "Причину блокування потрібно вказати.";
        exit;
    }

    // Отримуємо user_id автора коментаря
    $user_stmt = $conn->prepare("SELECT user_id FROM Comments WHERE comment_id = ?");
    $user_stmt->execute([$comment_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_id = $user['user_id'];

        // Блокуємо коментар (записуємо деактивацію)
        $stmt_deactivate = $conn->prepare("UPDATE Comments SET deactivated_at = NOW() WHERE comment_id = ?");
        $stmt_deactivate->execute([$comment_id]);

        // Додаємо запис в таблицю блокувань
        $stmt_block = $conn->prepare("INSERT INTO userblocks (user_id, blocked_by, block_type_id, reason, blocked_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt_block->execute([$user_id, $_SESSION['user_id'], $block_type_id, $reason])) {
            echo "Коментар заблокований.";
        } else {
            echo "Не вдалося заблокувати коментар.";
        }
    } else {
        echo "Коментар не знайдено.";
    }
}

// Отримуємо публікації та блоги для відображення
$posts = getPosts($conn, $is_admin);
$blogs = getBlogs($conn, $is_admin);
$comments = getComments($conn, $is_admin);
$users = getUsers($conn, $is_admin);
?>


<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Адміністраторська панель</title>
    <style>
    /* Загальний стиль */
    body {
        font-family: Arial, sans-serif;
        background-color: #1a1a1a; /* Темний фон */
        color: #ddd; /* Світлий текст */
        margin: 0;
        padding: 20px;
    }

    h1, h2 {
        color: #fff; /* Світлі заголовки */
        transition: color 0.5s ease;
    }

    /* Стиль для форм */
    form {
        margin-bottom: 20px;
        padding: 20px;
        background: rgba(0, 0, 0, 0.7); /* Темніший прозорий фон для форм */
        border: 1px solid #444; /* Світла обводка */
        border-radius: 8px; /* Заокруглені кути */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5); /* Тінь */
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    form:hover {
        transform: scale(1.02);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.7); /* Посилення тіні при наведенні */
    }

    /* Стиль для міток */
    label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #ddd; /* Світлий текст для міток */
    }

    /* Випадаючі списки */
    select {
        padding: 10px;
        font-size: 16px;
        margin-top: 10px;
        display: inline-block;
        width: 100%;
        border-radius: 5px;
        background-color: rgba(0, 0, 0, 0.5); /* Темний фон для випадаючих списків */
        border: 1px solid #444; /* Легка обводка */
        color: #ddd; /* Світлий текст */
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }

    select:hover {
        background-color: rgba(0, 0, 0, 0.6); /* Темніший фон при наведенні */
        border-color: #d9534f; /* Помаранчевий колір обводки */
    }

    /* Кнопки */
    button {
        padding: 10px 15px;
        font-size: 16px;
        margin-top: 10px;
        background-color: #d9534f; /* Помаранчевий колір для кнопок */
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }

    button:hover {
        background-color: #c9302c; /* Темніший відтінок помаранчевого при наведенні */
        transform: scale(1.05); /* Легка анімація при наведенні */
    }

    button:active {
        background-color: #c9302c; /* Підтвердження натискання */
        transform: scale(1); /* Зменшення ефекту при натисканні */
    }

    button:focus {
        outline: none; /* Видалення обведення при фокусі */
    }

    /* Анімація появи елементів */
    form, select, button {
        opacity: 0;
        animation: fadeIn 0.8s forwards;
    }

    form:nth-child(1) {
        animation-delay: 0.2s;
    }

    select:nth-child(2) {
        animation-delay: 0.4s;
    }

    button:nth-child(3) {
        animation-delay: 0.6s;
    }

    @keyframes fadeIn {
        0% {
            opacity: 0;
        }
        100% {
            opacity: 1;
        }
    }

</style>

</head>
<body>
    <h1>Панель адміністратора</h1>

    <h2>Блокувати блог</h2>
    <form method="post">
        <label for="blog_id">Виберіть блог:</label>
        <select name="blog_id" required>
            <?php foreach ($blogs as $blog): ?>
                <option value="<?php echo $blog['blog_id']; ?>">
                    <?php echo htmlspecialchars($blog['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label for="reason">Причина блокування:</label>
        <select name="reason" required>
            <option value="Спам">Спам</option>
            <option value="Неправомірна активність">Неправомірна активність</option>
            <option value="Порушення правил спільноти">Порушення правил спільноти</option>
            <option value="Образлива поведінка або мова ворожнечі">Образлива поведінка або мова ворожнечі</option>
            <option value="Шахрайство">Шахрайство</option>
            <option value="Непристойний або небажаний контент">Непристойний або небажаний контент</option>
            <option value="Порушення конфіденційності">Порушення конфіденційності</option>
            <option value="Інше">Інше</option>
        </select>

        <button type="submit" name="block_blog">Заблокувати блог</button>
    </form>

    <h2>Блокувати допис</h2>
    <form method="post">
        <label for="post_id">Виберіть допис:</label>
        <select name="post_id" required>
            <?php foreach ($posts as $post): ?>
                <option value="<?php echo $post['post_id']; ?>">
                    <?php echo htmlspecialchars($post['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="reason">Причина блокування:</label>
        <select name="reason" required>
            <option value="Спам">Спам</option>
            <option value="Неправомірна активність">Неправомірна активність</option>
            <option value="Порушення правил спільноти">Порушення правил спільноти</option>
            <option value="Образлива поведінка або мова ворожнечі">Образлива поведінка або мова ворожнечі</option>
            <option value="Шахрайство">Шахрайство</option>
            <option value="Непристойний або небажаний контент">Непристойний або небажаний контент</option>
            <option value="Порушення конфіденційності">Порушення конфіденційності</option>
            <option value="Інше">Інше</option>
        </select>

        <button type="submit" name="block_post">Заблокувати допис</button>
    </form>

    <h2>Видалити користувача</h2>
<form method="post">
    <label for="user_id">Виберіть користувача:</label>
    <select name="user_id" id="user_id" required>
        <option value="" disabled selected>Оберіть користувача</option>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="reason">Причина видалення:</label>
    <select name="reason" id="reason" required>
        <option value="" disabled selected>Оберіть причину</option>
        <option value="Спам">Спам</option>
        <option value="Неправомірна активність">Неправомірна активність</option>
        <option value="Порушення правил спільноти">Порушення правил спільноти</option>
        <option value="Образлива поведінка або мова ворожнечі">Образлива поведінка або мова ворожнечі</option>
        <option value="Шахрайство">Шахрайство</option>
        <option value="Непристойний або небажаний контент">Непристойний або небажаний контент</option>
        <option value="Порушення конфіденційності">Порушення конфіденційності</option>
        <option value="Інше">Інше</option>
    </select>

    <button type="submit" name="delete_user">Заблокувати користувача</button>
    </form>
    <h2>Блокувати коментар</h2>
<form method="post">
    <label for="comment_id">Виберіть коментар:</label>
    <select name="comment_id" required>
        <?php foreach ($comments as $comment): ?>
            <option value="<?php echo $comment['comment_id']; ?>">
                <?php echo htmlspecialchars($comment['content']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="reason">Причина блокування:</label>
    <select name="reason" required>
        <option value="Спам">Спам</option>
        <option value="Неправомірна активність">Неправомірна активність</option>
        <option value="Порушення правил спільноти">Порушення правил спільноти</option>
        <option value="Образлива поведінка або мова ворожнечі">Образлива поведінка або мова ворожнечі</option>
        <option value="Шахрайство">Шахрайство</option>
        <option value="Непристойний або небажаний контент">Непристойний або небажаний контент</option>
        <option value="Порушення конфіденційності">Порушення конфіденційності</option>
        <option value="Інше">Інше</option>
    </select>

    <button type="submit" name="block_comment">Заблокувати коментар</button>
</form>

</body>
</html>
