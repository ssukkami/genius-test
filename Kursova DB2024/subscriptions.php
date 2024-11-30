<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ідентифікатор користувача для перегляду підписок
$view_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $_SESSION['user_id'];

try {
    // Отримати список підписок обраного користувача
    $stmt = $conn->prepare("
        SELECT U.user_id, U.username, U.profile_picture 
        FROM Follows F
        JOIN Users U ON F.following_id = U.user_id
        WHERE F.follower_id = ?
    ");
    $stmt->execute([$view_user_id]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Помилка: " . $e->getMessage();
    exit;
}

// Видалити підписку (демо-логіка)
if (isset($_POST['unsubscribe']) && isset($_POST['unfollow_user_id'])) {
    $unfollow_user_id = intval($_POST['unfollow_user_id']);
    $stmt = $conn->prepare("DELETE FROM Follows WHERE follower_id = ? AND following_id = ?");
    if ($stmt->execute([$view_user_id, $unfollow_user_id])) {
        echo "<script>alert('Ви відписалися від користувача.');</script>";
        // Оновити список підписок
        header("Refresh:0");
    }
}
?>


<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Підписки користувача</title>
    <link rel="stylesheet" href="styles.css">
    <style>
          body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-image: url('uploads/1,3.jpg');
    background-size: cover; 
    background-position: center; 
    background-repeat: repeat; /* Дублювання фону */
    background-attachment: fixed; /* Фон фіксований */
}

    /* Загальний стиль для списку підписок */
    .subscription-list {
        list-style: none;
        padding: 0;
        background-color: rgba(0, 0, 0, 0.1); /* чорний прозорий фон */
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
        opacity: 0;
        animation: fadeIn 1s forwards; /* Анімація для появлення списку */
    }

    .subscription-item form button {
    background-color: #FF5733; /* Помаранчевий колір кнопки */
    color: #fff; /* Білий текст */
    padding: 5px 10px; /* Зменшені відступи */
    border: none; /* Видалення обводки */
    border-radius: 20px; /* Округла форма */
    font-size: 14px; /* Менший розмір тексту */
    cursor: pointer; /* Вказівник на кнопку */
    margin-left: 10px; /* Відступ між кнопкою та текстом */
    transition: background-color 0.3s ease; /* Плавна зміна кольору */
}

.subscription-item form button:hover {
    background-color: #E64A19; /* Темніший відтінок при наведенні */
}

    /* Анімація для плавного появлення елементів списку */
    @keyframes fadeIn {
        to {
            opacity: 1;
        }
    }

    /* Анімація для появлення елементів списку знизу */
    @keyframes slideIn {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Стиль для аватарки - фотографія зліва */
    .subscription-item img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        margin-left: 30px; /* Збільшений відступ праворуч між аватаркою і текстом */
        border: 2px solid rgba(255, 87, 34, 0.8); /* Помаранчева обводка навколо аватарки */
        transition: transform 0.3s ease; /* Плавна зміна масштабу */
    }

    /* Анімація при наведенні на аватарку */
    .subscription-item img:hover {
        transform: scale(1.1); /* Збільшення аватарки при наведенні */
    }

    /* Стиль для посилання (тексту) */
    .subscription-item a {
        text-decoration: none;
        color: white; /* Білий колір тексту */
        font-weight: bold;
        margin-left: 10px; /* Відступ між аватаркою та текстом */
        transition: color 0.3s ease; /* Плавний перехід кольору */
    }

    /* Ефект при наведенні на посилання */
    .subscription-item a:hover {
        text-decoration: underline;
        color:#FF5733; /* Помаранчевий колір при наведенні */
    }

    /* Стиль для заголовка */
    header h1 {
        text-align: center;
        color: #fff;
        font-size: 24px;
    }

    /* Стиль для кнопки */
    .menu-button {
        background-color: rgba(255, 87, 34, 0.3); /* Помаранчевий колір */
        color: #fff;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 5px;
        margin-top: 20px;
        display: block;
        text-align: center;
        font-weight: bold;
        transition: background-color 0.3s ease; /* Плавна зміна фону кнопки */
    }

    /* Ефект при наведенні на кнопку */
    .menu-button:hover {
        background-color: #FF5733; /* Трохи темніший помаранчевий при наведенні */
    }
</style>

</head>
<body>
<header>
        <h1>ПІДПИСКИ КОРИСТУВАЧА</h1>
        <a href="profile.php" class="menu-button">ПРОФІЛЬ</a>
    </header>
    <main>
        <ul class="subscription-list">
            <?php if (!empty($subscriptions)): ?>
                <?php foreach ($subscriptions as $subscription): ?>
                    <li class="subscription-item">
                        <a href="profile.php?user_id=<?php echo htmlspecialchars($subscription['user_id']); ?>">
                            <?php echo htmlspecialchars($subscription['username']); ?>
                        </a>
                        <?php if (!empty($subscription['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($subscription['profile_picture']); ?>" alt="Аватарка">
                        <?php endif; ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="unfollow_user_id" value="<?php echo $subscription['user_id']; ?>">
                            <button type="submit" name="unsubscribe" class="menu-button">Відписатися</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>У цього користувача немає підписок.</li>
            <?php endif; ?>
        </ul>
    </main>
</body>
</html>
