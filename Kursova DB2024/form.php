<?php
session_start();
include 'db.php';

// Перевірка входу в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$stmt = $conn->prepare("
    SELECT 
        Posts.post_id,
        Posts.title AS post_title,
        Posts.content AS post_content,
        PostImages.image_url AS post_image,
        Users.user_id AS author_id, 
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
    WHERE 
        Users.user_id IN (SELECT following_id FROM Follows WHERE follower_id = :current_user_id)
    ORDER BY 
        Posts.created_at DESC
");
$stmt->execute([':current_user_id' => $_SESSION['user_id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Публікації</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        @font-face {
            font-family: 'Cy Grotesk Wide Bold';
            src: url('fonts/cy-grotesk-wide-bold.ttf') format('ttf');
            font-weight: normal;
            font-style: normal;
        }
        body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-image: url('uploads/1,3.jpg');
    background-size: cover; 
    background-position: center; 
    background-repeat: repeat; /* Дублювання фону */
    background-attachment: fixed; /* Фон фіксований */
    overflow-x: hidden; /* Прибирає нижній скролбар */
}

        header {
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
            position: static;
            top: 0;
            z-index: 1000;
        }

        header h1 {
            margin: 0;
            text-align: center;
        }

        .navigation {
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
        }

        .navigation a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-family: 'Cy Grotesk Wide', sans-serif;
            font-size: 24px;
            text-transform: uppercase;
        }  

        .navigation a:hover {
            color: #FF5733;
        }

        /* Стилі для публікацій */
        .post-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }

        .post-card {
            background: rgba(0, 0, 0, 0.6);
            border-radius: 15px; 
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
            color: white;
        }

        .post-card:hover {
            transform: translateY(-5px);
        }

        .post-image {
            width: 300px; 
            height: 300px; 
            object-fit: cover; 
            border-radius: 8px; 
            margin: 10px 0;
        }

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
            border: 2px solid #FF5733;
        }

        .author-name, .post-title, .post-details {
            color: white;
        }

        .post-title {
            font-size: 1.5em; 
            margin: 10px 0;
        }

        .post-details {
            font-size: 1em;
            line-height: 1.5; 
        }

        .like-comment-count {
            display: flex;
            justify-content: space-between;
            font-size: 0.9em;
            margin-top: 10px;
            color: #ffffff;
        }

        .like-icon {
            font-size: 1.5em; /* Збільшений розмір лайку */
            cursor: pointer; /* Курсор для клікабельності */
        }

        .view-post-link {
            color: #FF5733;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
            margin-top: 10px;
        }

        .view-post-link:hover {
            color: #ffffff;
        }
    </style>
    <script>
        function addLike(postId) {
            // AJAX-запит для додавання лайку
            fetch('add_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeCountElement = document.getElementById('like-count-' + postId);
                    likeCountElement.textContent = parseInt(likeCountElement.textContent) + 1;
                } else {
                    alert('Не вдалося додати лайк. Спробуйте ще раз.');
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Зміна фону під час скроллу
        window.onscroll = function() {
            const scrollPosition = window.scrollY;
            document.body.style.backgroundPositionY = `${scrollPosition * 0.5}px`; // Змінюємо позицію фону
        };
    </script>
</head>
<body>
    <header>
        <nav class="navigation">
            <a href="browse_blogs.php">Головна</a>
            <a href="profile.php">Мій профіль</a>
            <a href="logout.php">Вихід</a>
        </nav>
    </header>
    <main>
        <div class="post-container">
            <h2 style="color: black;">Дописи користувачів, за якими ви слідкуєте</h2> 
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="author-info">
                            <?php if ($post['author_avatar']): ?>
                                <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" alt="Аватар" class="author-avatar">
                            <?php endif; ?>
                            <a href="profile.php?user_id=<?php echo $post['author_id']; ?>" class="author-name"><?php echo htmlspecialchars($post['author_name']); ?></a>
                        </div>
                        <h2 class="post-title"><?php echo htmlspecialchars($post['post_title']); ?></h2>
                        <p class="post-details">Блог: <?php echo htmlspecialchars($post['blog_title']); ?></p>
                        <?php if ($post['post_image']): ?>
                            <img src="<?php echo htmlspecialchars($post['post_image']); ?>" alt="Зображення публікації" class="post-image">
                        <?php endif; ?>
                        <p class="post-details">
                            <?php 
                                // Отримуємо речення для відображення
                                $sentences = explode('.', $post['post_content']);
                                echo nl2br(htmlspecialchars(implode('.', array_slice($sentences, 0, 2))) . '.'); 
                            ?>
                        </p>
                        <div class="like-comment-count">
                            <span class="like-icon" style="display: inline-flex; align-items: center;" onclick="addLike(<?php echo $post['post_id']; ?>)">
                                ❤️
                            </span>
                            <span id="like-count-<?php echo $post['post_id']; ?>" style="margin-left: 5px; display: inline-flex; align-items: center;">
                                <?php echo $post['like_count']; ?>
                            </span>
                            <span style="margin-left: 1325px;">Коментарі: <?php echo $post['comment_count']; ?></span>
                        </div>
                        <a href="view_post.php?post_id=<?php echo $post['post_id']; ?>" class="view-post-link">Переглянути публікацію</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Публікацій не знайдено.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
