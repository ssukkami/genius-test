<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Отримання даних користувача
$stmt = $conn->prepare("SELECT * FROM Users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($user_id)) {
    // Отримуємо ролі користувача
    $user_roles = $conn->prepare("SELECT role_id FROM Userroleassignment WHERE user_id = ?");
    $user_roles->execute([$user_id]);
    $user_roles = $user_roles->fetchAll(PDO::FETCH_COLUMN) ?: [];

    // Призначаємо роль
    if (in_array(1, $user_roles)) {
        $user_role = 'Адміністратор';  // Текстова роль
    } elseif (in_array(3, $user_roles)) {
        $user_role = 'Блогер';  // Текстова роль
    } elseif (in_array(4, $user_roles)) {
        $user_role = 'Відвідувач';  // Текстова роль
    } else {
        $user_role = 'Невизначена роль';  // Якщо роль не знайдена
    }
}

// Оновлення профілю
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Оновлення налаштувань профілю
    if (isset($_POST['profile_settings'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt_update = $conn->prepare("UPDATE Users SET username = ?, email = ?, password = ? WHERE user_id = ?");
        $stmt_update->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $user_id]);
    }

    // Оновлення біографії
    if (isset($_POST['bio_settings'])) {
        $bio_text = $_POST['bio'];
        $stmt_update_bio = $conn->prepare("REPLACE INTO userbios (user_id, bio, updated_at) VALUES (?, ?, NOW())");
        $stmt_update_bio->execute([$user_id, $bio_text]);
    }

    // Завантаження аватарки
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["avatar"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowed_types)) {
            move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file);
            $stmt_update_avatar = $conn->prepare("UPDATE Users SET profile_picture = ? WHERE user_id = ?");
            $stmt_update_avatar->execute([$target_file, $user_id]);
        }
    }

    echo "<script>alert('Профіль оновлено!');</script>";
}

// Перевірка видимості профілю
$view_user_id = $_GET['user_id'] ?? $user_id;
$stmt = $conn->prepare("SELECT profile_visibility FROM Users WHERE user_id = ?");
$stmt->execute([$view_user_id]);
$profile_visibility = $stmt->fetchColumn();

// Отримання запитів на підписку лише для приватних профілів
$pending_requests = [];
if ($profile_visibility === 'private' && $view_user_id === $user_id) {
    $stmt = $conn->prepare("
        SELECT F.follower_id, U.username, U.profile_picture
        FROM Follows F
        JOIN Users U ON F.follower_id = U.user_id
        WHERE F.following_id = ? AND F.status = 'pending'
    ");
    $stmt->execute([$view_user_id]);
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Обробка запитів на підписку
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_request_user_id'])) {
        $follower_id = $_POST['accept_request_user_id'];
        $stmt = $conn->prepare("UPDATE Follows SET status = 'approved' WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $view_user_id]);
    } elseif (isset($_POST['reject_request_user_id'])) {
        $follower_id = $_POST['reject_request_user_id'];
        $stmt = $conn->prepare("DELETE FROM Follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $view_user_id]);
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Налаштування профілю</title>
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
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
}

.container {
    width: 80%;
    max-width: 600px;
    background-color: rgba(0, 0, 0, 0.7);
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    position: relative;
}

h1 {
    text-align: center;
    font-size: 36px;
    margin-bottom: 20px;
}

h2 {
    font-size: 28px;
    margin-top: 20px;
    margin-bottom: 10px;
    border-bottom: 2px solid #FF5733;
    padding-bottom: 5px;
}

/* Текстові поля */
.input-field,
input[type="text"], input[type="file"], input[type="password"], textarea, select {
    width: 100%;
    padding: 15px;
    border-radius: 25px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    margin-bottom: 10px;
    font-size: 16px;
    background-color: rgba(0, 0, 0, 0.6); /* Прозорі чорні текстові поля */
    color: #FFFFFF;
    box-sizing: border-box;
    max-width: 500px; /* Усі текстові поля мають однакову ширину, як у кнопок */
}

/* Кнопки */
.submit-button, .link-button, .upload-button {
    width: 100%;
    max-width: 500px; /* Установлено однакову максимальну ширину для кнопок */
    padding: 15px;
    border-radius: 25px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    font-size: 20px;
    background-color: #FF5733; /* Помаранчеві кнопки */
    color: #FFFFFF;
    box-sizing: border-box;
    text-transform: uppercase;
    margin-bottom: 10px;
    text-align: center; /* Вирівнювання тексту в кнопках */
    cursor: pointer;
}

.submit-button:hover, .link-button:hover, .upload-button:hover {
    background-color: #FF4500; /* Помаранчевий колір при наведенні */
}

.hidden-file-input {
    display: none;
}

.form-elements,
.action-buttons {
    display: flex;
    flex-direction: column; /* Розміщує елементи в колонку */
    align-items: stretch; /* Розтягує елементи до повної ширини */
    gap: 15px; /* Відстань між елементами */
    width: 100%; /* Елементи займають всю ширину контейнера */
    max-width: 500px; /* Задає максимальну ширину для елементів */
    margin: 0 auto; /* Центрує елементи в контейнері */
}
.subscription-request {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
    background-color: rgba(0, 0, 0, 0.6);
    border-radius: 8px;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.5);
    position: relative; /* Для точного позиціонування кнопок */
}

.subscription-request img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin-right: 15px;
    border: 3px solid #FF5733; /* Помаранчева обводка для фотографії */
}

.subscription-request .username {
    font-size: 18px;
    color: #FFFFFF; /* Білий колір нікнейму */
    font-weight: bold;
    transition: color 0.3s ease;
    flex: 1; /* Забезпечує заповнення простору текстом */
}

.subscription-request .username:hover {
    color: #FF5733; /* Помаранчевий колір нікнейму при наведенні */
}

.subscription-request form {
    margin-left: 10px; /* Відступ між формами */
}

.subscription-request .submit-button {
    padding: 5px 10px; /* Зменшений розмір кнопок */
    font-size: 14px; /* Менший розмір тексту */
    border-radius: 5px;
    background-color: #FF5733;
    color: #FFFFFF;
    cursor: pointer;
}

.subscription-request .submit-button:hover {
    background-color: #FF4500;
}

.role-display {
    position: fixed;
    top: 10px;
    right: 10px;
    background-color: rgba(0, 0, 0, 0.7);
    padding: 5px 10px;
    border-radius: 5px;
}

</style>

<body>
<header>
    <h1>Налаштування профілю</h1>
</header>
<div class="role-display">
    Роль: <strong><?php echo $user_role; ?></strong>
</div>
<div class="settings-container">
    <div class="action-buttons">
        <button class="link-button" onclick="toggleSection('profile_settings')">Змінити персональні дані</button>
        <div id="profile_settings" class="section" style="display:none;">
            <form method="POST">
                <label class="label">ІМ'Я КОРИСТУВАЧА</label>
                <input class="input-field" type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                <label class="label">ЕЛЕКТРОНА ПОШТА</label>
                <input class="input-field" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                <label class="label">ПАРОЛЬ</label>
                <input class="input-field" type="password" name="password" required>
                <button type="submit" name="profile_settings" class="submit-button">Так, оновити</button>
            </form>
        </div>

        <button class="link-button" onclick="toggleSection('bio_settings')">Змінити біографію</button>
        <div id="bio_settings" class="section" style="display:none;">
            <form method="POST">
                <label class="label">БІОГРАФІЯ</label>
                <textarea class="input-field" name="bio"><?= htmlspecialchars($bio_text ?? '') ?></textarea>
                <button type="submit" name="bio_settings" class="submit-button">Так, оновити</button>
            </form>
        </div>

        <button class="link-button" onclick="toggleSection('avatar_settings')">Змінити аватарку</button>
        <div id="avatar_settings" class="section" style="display:none;">
            <form method="POST" enctype="multipart/form-data">
                <label class="label">ВИБЕРІТЬ ФОТОГРАФІЮ</label>
                <input class="input-field" type="file" name="avatar" accept="image/*">
                <button type="submit" class="submit-button">Так, змінити</button>
            </form>
        </div>
        <button class="link-button" onclick="window.location.href='edit_post.php'">Оновити публікацію</button>
        <button class="link-button" onclick="window.location.href='view_saved_posts.php'">Переглянути збережені публікації</button>

        <?php if ($user_role === 'Адміністратор'): ?>
            <button class="link-button" onclick="window.location.href='admin_panel.php'">Адмін-панель</button>
        <?php endif; ?>
    </div>

    <?php if ($profile_visibility === 'private' && $view_user_id === $user_id): ?>
        <h2>Запити на підписку</h2>
        <?php if (!empty($pending_requests)): ?>
            <ul class="pending-request-list">
                <?php foreach ($pending_requests as $request): ?>
                    <li class="subscription-request">
                        <?php if (!empty($request['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($request['profile_picture']); ?>" alt="Аватарка">
                        <?php endif; ?>
                        <a href="profile.php?user_id=<?= htmlspecialchars($request['follower_id']); ?>" class="username">
                            <?= htmlspecialchars($request['username']); ?>
                        </a>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="accept_request_user_id" value="<?= htmlspecialchars($request['follower_id']); ?>">
                            <button type="submit" class="submit-button">Прийняти</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="reject_request_user_id" value="<?= htmlspecialchars($request['follower_id']); ?>">
                            <button type="submit" class="submit-button">Відхилити</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Немає запитів на підписку.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function toggleSection(id) {
    var section = document.getElementById(id);
    section.style.display = section.style.display === "none" ? "block" : "none";
}
</script>

</body>
</html>
