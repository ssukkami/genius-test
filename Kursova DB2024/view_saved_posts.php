<?php
session_start();
include 'db.php';

// Перевірка входу в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Отримання ID користувача
$user_id = $_SESSION['user_id'];

// SQL-запит для отримання збережених публікацій разом із інформацією про публікації
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
        savedposts
    JOIN 
        Posts ON savedposts.post_id = Posts.post_id
    LEFT JOIN 
        PostImages ON Posts.post_id = PostImages.post_id
    JOIN 
        Blogs ON Posts.blog_id = Blogs.blog_id
    JOIN 
        Users ON Blogs.user_id = Users.user_id
    WHERE 
        savedposts.user_id = ?
    GROUP BY 
        Posts.post_id
    ORDER BY 
        savedposts.saved_at DESC
");
$stmt->execute([$user_id]);
$saved_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Збережені публікації</title>
    <style>
body {
    font-family: 'Arial', sans-serif;
    margin: 0;
    padding: 0;
    background-image: url('uploads/Black, Orange and Blue Gradient Graphic Design Portfolio Presentation (1).jpg');
    background-size: cover;
    background-repeat: no-repeat;
    background-attachment: fixed;
    color: #FFFFFF;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start; /* Встановлено flex-start для вирівнювання по верхньому краю */
    min-height: 100vh;
}

/* Заголовок */
header {
    width: 100%; /* Заголовок на всю ширину */
    text-align: center;
    margin-top: 20px;
}

header h1 {
    color: #FFFFFF;
    text-transform: uppercase;
    letter-spacing: 2px;
    animation: fadeIn 1s ease-in-out;
}

/* Контейнер для всіх публікацій */
.post-container {
    display: flex;
    flex-direction: column;
    gap: 30px;
    max-width: 1200px;
    margin-top: 40px; /* Відступ від заголовка */
    padding: 20px;
}

/* Окремий контейнер для кожної публікації */
.post-card {
    display: flex;
    flex-direction: row;
    gap: 20px;
    background: rgba(0, 0, 0, 0.8);
    border-radius: 15px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
    padding: 20px;
    animation: fadeIn 0.5s ease-in-out;
}

/* Контейнер для фотографій */
.post-image-container {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    max-width: 300px;
}

/* Інформація про автора */
.author-info {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    color: #FF5733;
}

.author-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #FF5733;
}

.post-image-container img {
    width: 100%;
    height: auto;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
    cursor: pointer;
    transition: transform 0.3s;
}

.post-image-container img:hover {
    transform: scale(1.05);
}

/* Контейнер для тексту */
.post-text-container {
    flex: 2;
    color: #E0E0E0;
    line-height: 1.8;
    display: flex;
    flex-direction: column;
}

/* Заголовок публікації */
.post-title {
    font-size: 1.8em;
    color: #FFC300;
    margin-bottom: 15px;
}

/* Виділення абзаців */
.post-content p {
    margin-bottom: 15px;
    text-indent: 20px;
}

/* Оформлення цитат */
.post-content blockquote {
    margin: 20px 0;
    padding: 15px;
    background: rgba(255, 255, 255, 0.1);
    border-left: 5px solid #FF5733;
    font-style: italic;
    color: #FFC300;
}

/* Адаптивність */
@media (max-width: 768px) {
    .post-card {
        flex-direction: column;
    }
    .post-image-container {
        max-width: 100%;
    }
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
</style>

<body>
    <header>
        <h1>Збережені публікації</h1>
    </header>
    <main>
        <div class="post-container">
            <?php if (count($saved_posts) > 0): ?>
                <?php foreach ($saved_posts as $post): ?>
                    <div class="post-card">
                        <div class="post-image-container">
                            <?php if (!empty($post['post_images'])): ?>
                                <?php
                                $images = explode(',', $post['post_images']);
                                echo '<img src="' . htmlspecialchars($images[0]) . '" alt="Зображення публікації" class="post-image" onclick="openFullscreen(\'' . htmlspecialchars($images[0]) . '\')">';
                                ?>
                            <?php endif; ?>
                        </div>
                        <div class="post-text-container">
                        <div class="author-info">
                                    <?php if ($post['author_avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" alt="Аватар" class="author-avatar">
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($post['author_name']); ?></span>
                                </div>
                            <h2 class="post-title"><?php echo htmlspecialchars($post['post_title']); ?></h2>
                            <div class="post-content">
                                <p><?php echo nl2br(htmlspecialchars($post['post_content'])); ?></p>
                                <?php if (strpos($post['post_content'], '>>') !== false): ?>
                                    <blockquote>
                                        <?php echo htmlspecialchars(explode('>>', $post['post_content'])[1]); ?>
                                    </blockquote>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Ви не зберегли жодних публікацій.</p>
            <?php endif; ?>
        </div>
    </main>


    <script>
        function openFullscreen(imageSrc) {
            const fullscreenContainer = document.getElementById('fullscreen-container');
            const fullscreenImage = document.getElementById('fullscreen-image');
            fullscreenImage.src = imageSrc;
            fullscreenContainer.style.display = 'flex';
        }

        function closeFullscreen() {
            const fullscreenContainer = document.getElementById('fullscreen-container');
            fullscreenContainer.style.display = 'none';
        }
    </script>
</body>

</html>
