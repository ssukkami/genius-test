<?php
session_start();
include 'db.php';

// Перевірка входу в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// SQL-запит для отримання інформації про публікації разом із іменем автора, назвою блогу, кількістю лайків і коментарів, а також зображенням
$stmt = $conn->prepare("
    SELECT 
        Posts.post_id,
        Posts.title AS post_title,
        Posts.content AS post_content,
        GROUP_CONCAT(PostImages.image_url) AS post_images,
        Users.username AS author_name,
        Users.profile_picture AS author_avatar,
        Blogs.title AS blog_title,
        (SELECT COUNT(*) FROM Likes WHERE Likes.post_id = Posts.post_id) AS like_count,
        (SELECT COUNT(*) FROM Comments WHERE Comments.post_id = Posts.post_id) AS comment_count
    FROM 
        Posts
    LEFT JOIN 
        PostImages ON Posts.post_id = PostImages.post_id
    JOIN 
        Blogs ON Posts.blog_id = Blogs.blog_id
    JOIN 
        Users ON Blogs.user_id = Users.user_id
    GROUP BY 
        Posts.post_id
    ORDER BY 
        Posts.created_at DESC
");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обробка запиту на збереження публікації
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post_id'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = (int)$_POST['save_post_id'];
    $saved_at = date('Y-m-d H:i:s');

    // SQL-запит для вставки збереженого посту
    $saveStmt = $conn->prepare("INSERT INTO savedposts (user_id, post_id, saved_at) VALUES (?, ?, ?)");
    if ($saveStmt->execute([$user_id, $post_id, $saved_at])) {
        echo "<script>alert('Публікацію успішно збережено!');</script>";
    } else {
        echo "<script>alert('Помилка при збереженні публікації.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Публікації</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Загальні стилі для сторінки */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        header h1 {
            color: #333;
            text-align: center;
        }

        /* Стилі для контейнера публікацій */
        .post-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .post-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }

        .post-card:hover {
            transform: translateY(-5px);
        }

        /* Стилі для зображень публікації */
        .post-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .post-image {
            max-width: 100%;
            width: calc(50% - 10px);
            border-radius: 8px;
        }

        /* Стилі для інформації про автора */
        .author-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #ddd;
        }

        .post-title {
            font-size: 1.2em;
            margin-bottom: 10px;
            color: #333;
        }

        .post-details {
            color: #555;
            font-size: 0.9em;
        }

        /* Стилі для лічильників лайків і коментарів */
        .like-comment-count {
            display: flex;
            justify-content: space-between;
            font-size: 0.9em;
            margin-top: 10px;
            color: #333;
        }

        /* Стилі для посилання перегляду публікації */
        .view-post-link {
            color: #007BFF;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }

        .view-post-link:hover {
            color: #0056b3;
        }

        /* Стилі для кнопки збереження */
        .save-post-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .save-post-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <header><h1>Публікації</h1></header>
    <main>
        <div class="post-container">
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="author-info">
                            <?php if ($post['author_avatar']): ?>
                                <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" alt="Аватар" class="author-avatar">
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($post['author_name']); ?></span>
                        </div>
                        <h2 class="post-title"><?php echo htmlspecialchars($post['post_title']); ?></h2>
                        <p>Блог: <?php echo htmlspecialchars($post['blog_title']); ?></p>
                        <p><?php echo htmlspecialchars($post['post_content']); ?></p>
                        <?php
                        // Відображення всіх зображень публікації
                        if ($post['post_images']):
                            $images = explode(',', $post['post_images']); // Розбиваємо на масив
                            echo '<div class="post-images">';
                            foreach ($images as $image): ?>
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="Зображення публікації" class="post-image">
                            <?php endforeach; 
                            echo '</div>';
                        endif; ?>
                        <div class="like-comment-count">
                            <span>❤️ <?php echo $post['like_count']; ?></span> 
                            <span>Коментарі: <?php echo $post['comment_count']; ?></span>
                        </div>
                        <a href="view_post.php?post_id=<?php echo $post['post_id']; ?>" class="view-post-link">Переглянути публікацію</a>

                        <!-- Кнопка збереження публікації -->
                        <form method="POST" action="">
                            <input type="hidden" name="save_post_id" value="<?php echo $post['post_id']; ?>">
                            <button type="submit" class="save-post-button">Зберегти публікацію</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Немає публікацій для відображення.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
