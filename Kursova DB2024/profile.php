<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Check if viewing another user's profile
$view_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $user_id;

// Fetch user follow status
$stmt = $conn->prepare("SELECT status FROM Follows WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$user_id, $view_user_id]);
$follow_status = $stmt->fetchColumn();

// Fetch profile visibility setting
$stmt = $conn->prepare("SELECT profile_visibility FROM Users WHERE user_id = ?");
$stmt->execute([$view_user_id]);
$profile_visibility = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['follow_request_user_id'])) {
        $follow_request_user_id = $_POST['follow_request_user_id'];

        if ($follow_status === 'approved') {
            // Якщо вже підписані, змінюємо статус на "unfollowed" і оновлюємо дату відписки
            $stmt_unfollow = $conn->prepare("UPDATE Follows SET status = 'unfollowed', unfollowed_at = NOW() WHERE follower_id = ? AND following_id = ?");
            $stmt_unfollow->execute([$user_id, $follow_request_user_id]);
            $message = "Ви відписалися від цього користувача.";
            $follow_status = null;
        } else {
            // Використання "ON DUPLICATE KEY UPDATE" для підписки або оновлення дати
            $status = ($profile_visibility === 'private') ? 'pending' : 'approved';
            $stmt_follow = $conn->prepare("
                INSERT INTO Follows (follower_id, following_id, followed_at, status)
                VALUES (?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    followed_at = VALUES(followed_at)
            ");
            $stmt_follow->execute([$user_id, $follow_request_user_id, $status]);

            $message = $status === 'approved' ? "Ви підписалися на цього користувача." : "Ваш запит на підписку відправлено.";
            $follow_status = $status;
        }
    }

    // Прийняття або відхилення запиту на підписку
    if (isset($_POST['accept_request_user_id'])) {
        $accept_request_user_id = $_POST['accept_request_user_id'];
        $stmt = $conn->prepare("UPDATE Follows SET status = 'approved' WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$accept_request_user_id, $user_id]);
        $message = "Ви прийняли запит на підписку.";
    }

    if (isset($_POST['reject_request_user_id'])) {
        $reject_request_user_id = $_POST['reject_request_user_id'];
        $stmt = $conn->prepare("UPDATE Follows SET status = 'rejected' WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$reject_request_user_id, $user_id]);
        $message = "Ви відхилили запит на підписку.";
    }
}


// Fetch user details
$stmt = $conn->prepare("SELECT * FROM Users WHERE user_id = ?");
$stmt->execute([$view_user_id]);
$view_user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user bio if profile is visible or follow request approved
if ($profile_visibility === 'open' || $follow_status === 'approved' || $view_user_id === $user_id) {
    $stmt = $conn->prepare("SELECT bio FROM UserBios WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute([$view_user_id]);
    $user_bio = $stmt->fetchColumn();
} else {
    $user_bio = "This profile is private.";
}

// Fetch user blogs and posts if profile is visible or follow request approved
$blogs = [];
$posts = [];
if ($profile_visibility === 'open' || $follow_status === 'approved' || $view_user_id === $user_id) {
    $stmt = $conn->prepare("SELECT * FROM Blogs WHERE user_id = ?");
    $stmt->execute([$view_user_id]);
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM Posts WHERE blog_id IN (SELECT blog_id FROM Blogs WHERE user_id = ?)");
    $stmt->execute([$view_user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch follow counts
$stmt = $conn->prepare("SELECT COUNT(*) FROM Follows WHERE following_id = ? AND status = 'approved'");
$stmt->execute([$view_user_id]);
$followers_count = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM Follows WHERE follower_id = ?");
$stmt->execute([$view_user_id]);
$subscriptions_count = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Профіль</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Підключення шрифту */
        @font-face {
            font-family: 'Cy Grotesk Wide';
            src: url('fonts/cy-grotesk-wide-bold.ttf') format('truetype');
            font-weight: bold;
        }

        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-image: url('uploads/Black, Orange and Blue Gradient Graphic Design Portfolio Presentation (4).jpg');
    background-size: cover; 
    background-position: center; 
    background-repeat: repeat; /* Дублювання фону */
    background-attachment: fixed; /* Фон фіксований */
}

        header {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0px;
            background-color: rgba(0, 0, 0, 0.5); /* чорний прозорий */
            font-size: 1.8em;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .menu-buttons {
            display: flex;
            justify-content: center;
            margin: 10px 0;
        }

        .menu-button {
            padding: 12px 25px;
            background: transparent; /* Прозорий фон */
            color: #FFFFFF; /* Білий текст */
            text-decoration: none;
            border: none; /* Видалення рамки */
            font-weight: bold;
            text-transform: uppercase; /* Капс */
            margin: 0 10px;
            cursor: pointer; /* Зміна курсору на pointer */
            transition: color 0.3s;
        }

        .menu-button:hover {
            color: #FF5733; /* Зміна кольору при наведенні */
        }

        /* Основний контейнер */
        main {
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .avatar img {
            border-radius: 50%; /* Кругла форма аватарки */
            border: 2px solid #FF5733; /* Біла обводка */
            object-fit: cover;
            width: 100px;
            height: 100px;
        }

        /* Контейнер для біографії */
        .bio-container {
            background-color: rgba(0, 0, 0, 0.7); /* Прозорий чорний фон */
            border-radius: 50px; /* Максимально круглі бортики */
            padding: 15px; /* Внутрішнє відступлення */
            margin-top: 15px; /* Відступ зверху */
            width: fit-content; /* Ширина відповідно до вмісту */
            color: #FFFFFF; /* Колір тексту біографії */
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); /* Тінь */
            align-items: center;
        }

        /* Стилізація заголовків для блогів та публікацій */
        h3 {
            font-size: 1.8em; /* Збільшений розмір заголовка */
            margin-top: 30px; /* Відступ зверху */
            text-align: center; /* Центрування заголовка */
        }

        /* Стилізація карток */
        .card-container {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center; /* Центруємо картки по горизонталі */
    justify-content: center; /* Центруємо по вертикалі */
    gap: 20px;
}

.card {
    background-color: rgba(0, 0, 0, 0.5);
    padding: 30px;
    border-radius: 12px;
    transition: transform 0.3s, box-shadow 0.3s;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    margin-bottom: 15px;
    color: #FFFFFF;
    text-align: center;
    font-size: 1.2em;
    line-height: 1.6;
    width: 100%;
    max-width: 600px; /* Максимальна ширина картки */
}
.card:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
        }

        /* Заголовки */
        h2 {
            font-size: 2em; /* Збільшений розмір заголовка */
            text-align: center; /* Центрування заголовка */
            margin-bottom: 10px; /* Відступ знизу */
            color: #FFFFFF; /* Білий колір заголовка */
        }

        /* Посилання і заголовки */
        a {
            color: #FF5733;
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            text-decoration: underline;
            color: #FFFFFF;
        }

        /* Повідомлення */
        .alert-message {
            color: #FF5733;
            font-weight: bold;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        }
        /* Стилізація кнопки Підписатися/Відписатися */
/* Центрування кнопки */
button {
    padding: 10px 20px; /* Відступи всередині кнопки */
    background-color: #FF5733; /* Помаранчевий фон */
    color: #FFFFFF; /* Білий текст */
    border: none; /* Без рамки */
    border-radius: 5px; /* Круглі кути */
    font-weight: bold; /* Жирний шрифт */
    text-transform: uppercase; /* Великі літери */
    cursor: pointer; /* Курсор в стилі pointer */
    transition: background-color 0.3s, transform 0.3s; /* Анімація при наведенні */
    display: block; /* Переводимо кнопку в блоковий елемент */
    margin: 0 auto; /* Центруємо кнопку */
}


button:hover {
    background-color: #FF7043; /* Світліший помаранчевий колір при наведенні */
    transform: scale(1.05); /* Збільшення кнопки при наведенні */
}
/* Виділення тексту "Кількість підписників" */
.followers-count {
    font-size: 1.2em; /* Збільшений розмір тексту */
    font-weight: bold; /* Жирний текст */
    color: #FFFFFF; /* Помаранчевий колір */
    margin-top: 15px; /* Відступ зверху */
    text-align: center; /* Центрування тексту */
}

/* Виділення тексту "Кількість підписок" */
.subscriptions-count {
    font-size: 1.2em; /* Збільшений розмір тексту */
    font-weight: bold; /* Жирний текст */
    color: #FFFFFF; /* Помаранчевий колір */
    text-align: center; /* Центрування тексту */
    margin-top: 10px; /* Відступ зверху */
}

    </style>
</head>
<body>
<header>
    <div class="menu-buttons">
        <a href="form.php" class="menu-button">Меню</a>
        <a href="create_post.php" class="menu-button">Створити публікацію</a>
        <a href="create_blog.php" class="menu-button">Створити блог</a>
        <a href="settings.php" class="menu-button">Налаштування</a>
    </div>
</header>
<main>
    <div class="avatar">
        <?php if (!empty($view_user['profile_picture'])): ?>
            <img src="<?php echo htmlspecialchars($view_user['profile_picture']); ?>" alt="Аватарка">
        <?php endif; ?>
    </div>
    
    <div class="profile-info">
        <h2><?php echo htmlspecialchars($view_user['username']); ?></h2>
        <div class="bio-container">
            <p><?php echo htmlspecialchars($user_bio); ?></p>
        </div>
        <p class="followers-count">Кількість підписників: <?php echo $followers_count; ?></p>

<p class="subscriptions-count">
    <a href="subscriptions.php?user_id=<?php echo $view_user_id; ?>">Кількість підписок: <?php echo $subscriptions_count; ?></a>
</p>

        <?php if ($view_user_id !== $user_id): ?>
        <form method="POST">
            <input type="hidden" name="follow_request_user_id" value="<?= htmlspecialchars($view_user_id) ?>">
            <?php if ($follow_status === 'pending'): ?>
                <p>Ваш запит на підписку очікує підтвердження.</p>
            <?php elseif ($follow_status === 'approved'): ?>
                <button type="submit">Відписатися</button>
            <?php else: ?>
                <button type="submit">Підписатися</button>
            <?php endif; ?>
        </form>
        <?php endif; ?>

        <?php if ($view_user_id === $user_id && isset($view_user['is_private']) && $view_user['is_private']): ?>
            <h3>Запити на підписку</h3>
            <?php
            $stmt = $conn->prepare("SELECT Follows.follower_id, Users.username FROM Follows 
                                    JOIN Users ON Follows.follower_id = Users.user_id 
                                    WHERE Follows.following_id = ? AND Follows.status = 'pending'");
            $stmt->execute([$user_id]);
            $follow_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($follow_requests as $request): ?>
                <p><?php echo htmlspecialchars($request['username']); ?></p>
                <form method="POST">
                    <input type="hidden" name="accept_request_user_id" value="<?php echo htmlspecialchars($request['follower_id']); ?>">
                    <button type="submit">Прийняти</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="reject_request_user_id" value="<?php echo htmlspecialchars($request['follower_id']); ?>">
                    <button type="submit">Відхилити</button>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!isset($view_user['is_private']) || !$view_user['is_private'] || $follow_status === 'approved'): ?>
            <h3>Блоги</h3>
            <div class="card-container">
                <?php if (count($blogs) > 0): ?>
                    <?php foreach ($blogs as $blog): ?>
                        <div class="card">
                            <h4><a href="view_blog.php?blog_id=<?php echo $blog['blog_id']; ?>"><?php echo htmlspecialchars($blog['title']); ?></a></h4>
                            <p><?php echo htmlspecialchars(substr($blog['description'], 0, 150)) . (strlen($blog['description']) > 150 ? '...' : ''); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card empty-card">Користувач не має блогів.</div>
                <?php endif; ?>
            </div>

            <h3>Публікації</h3>
            <div class="card-container">
                <?php if (count($posts) > 0): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="card">
                            <h4><a href="view_post.php?post_id=<?php echo $post['post_id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></h4>
                            <p><?php echo htmlspecialchars(substr($post['content'], 0, 150)) . (strlen($post['content']) > 150 ? '...' : ''); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card empty-card">Користувач не має публікацій.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Цей профіль є приватним. Ви можете побачити блоги та публікації лише після схвалення вашого запиту на підписку.</p>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
