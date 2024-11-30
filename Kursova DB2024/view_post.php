<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];

    // Отримання інформації про публікацію
    $stmt = $conn->prepare("SELECT * FROM Posts WHERE post_id = ? AND (deactivated_at IS NULL OR deactivated_at > NOW())");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        // Отримання інформації про блог
        $stmt = $conn->prepare("SELECT * FROM Blogs WHERE blog_id = ?");
        $stmt->execute([$post['blog_id']]);
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);

        // Отримання зображень та коментарів до публікації
        $stmt_images = $conn->prepare("SELECT image_url FROM postimages WHERE post_id = ?");
        $stmt_images->execute([$post_id]);
        $images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);

        $stmt_comments = $conn->prepare("SELECT c.*, u.username FROM Comments c JOIN Users u ON c.user_id = u.user_id WHERE c.post_id = ? ORDER BY c.created_at DESC");
        $stmt_comments->execute([$post_id]);
        $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

        $stmt_likes = $conn->prepare("SELECT COUNT(*) as likes_count FROM Likes WHERE post_id = ?");
        $stmt_likes->execute([$post_id]);
        $likes_count = $stmt_likes->fetch(PDO::FETCH_ASSOC)['likes_count'];

        $stmt_tags = $conn->prepare("SELECT t.tag_name FROM Tags t JOIN PostTags pt ON t.tag_id = pt.tag_id WHERE pt.post_id = ?");
        $stmt_tags->execute([$post_id]);
        $tags = $stmt_tags->fetchAll(PDO::FETCH_ASSOC);

        // Поточний користувач
        $user_id = $_SESSION['user_id'];

        if (isset($_GET['save_post']) && $_GET['save_post'] === 'true') {
            // Перевірка, чи користувач авторизований
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Ви повинні увійти, щоб зберегти публікацію.']);
                exit;
            }
        
            // Перевірка, чи публікація вже збережена
            $stmt_check_save = $conn->prepare("SELECT * FROM SavedPosts WHERE post_id = ? AND user_id = ?");
            $stmt_check_save->execute([$post_id, $user_id]);
        
            if ($stmt_check_save->rowCount() == 0) {
                // Додавання публікації до таблиці SavedPosts
                $stmt_save_post = $conn->prepare("INSERT INTO SavedPosts (user_id, post_id, saved_at) VALUES (?, ?, NOW())");
                if ($stmt_save_post->execute([$user_id, $post_id])) {
                    echo json_encode(['success' => true, 'message' => 'Публікацію збережено!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Не вдалося зберегти публікацію.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Публікацію вже збережено.']);
            }
            exit;
        }
        

        // Обробка додавання коментарів
        if (isset($_POST['comment_text'])) {
            $comment_text = trim($_POST['comment_text']);
            if (!empty($comment_text)) {
                $stmt_add_comment = $conn->prepare("INSERT INTO Comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
                if ($stmt_add_comment->execute([$post_id, $user_id, $comment_text])) {
                    header("Location: " . $_SERVER['PHP_SELF'] . "?post_id=" . $post_id);
                    exit;
                } else {
                    echo "Не вдалося додати коментар.";
                }
            } else {
                echo "Коментар не може бути порожнім.";
            }
        }
    } else {
        echo "Публікація заблокована або деактивована.";
        exit;
    }
} else {
    echo "Публікація не знайдена.";
    exit;
}
?>


<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($post) ? htmlspecialchars($post['title']) : 'Публікація'; ?></title>
</head>
<style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #1a1a1a;
        color: #ddd;
        margin: 0;
        padding: 20px;
    }
    .post-container {
        background-color: rgba(0, 0, 0, 0.7);
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        margin-bottom: 20px;
    }
    .post-image img {
        max-width: 40%; /* Обмеження ширини зображення */
        border-radius: 12px;
        margin-right: 20px; /* Відступ між зображенням та текстом */
        margin-bottom: 10px;
        float: left; /* Зображення обтікається текстом праворуч */
    }
    .post-content {
        color: #bbb;
        overflow: hidden; /* Дозволяє коректно відображати текст навколо зображення */
    }
    header h1 {
        color: #fff;
        text-align: center;
        font-size: 2em;
    }
    .like-comment-count {
        font-size: 0.9em;
        margin-top: 10px;
        color: #999;
        clear: both; /* Після лічильників коментарів вирівнюємо контент */
    }
    .comment-section {
        background-color: rgba(0, 0, 0, 0.7);
        border-radius: 12px;
        padding: 15px;
        margin-top: 20px;
        color: #ddd;
    }
    .comment {
        padding: 10px;
        border-bottom: 1px solid #444;
        color: #ccc;
    }
    .comment:last-child {
        border-bottom: none;
    }
    .comment-input {
        width: 100%;
        padding: 12px;
        border-radius: 8px;
        border: none;
        background-color: #333;
        color: #eee;
        margin-top: 5px;
        resize: none;
    }
    .submit-button, .like-button {
        background-color: #444;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .submit-button:hover, .like-button:hover {
        background-color: #555;
    }
    .tags h3 {
        color: #aaa;
    }
    .tag {
        color: #888;
        text-decoration: none;
        padding: 5px 10px;
        background-color: #333;
        border-radius: 12px;
        margin: 0 5px;
        display: inline-block;
        transition: background-color 0.3s;
    }
    .tag:hover {
        background-color: #444;
    }
    .heart {
        color: #e63946;
        font-size: 1.5em;
        cursor: pointer;
    }
</style>

<body>
    <header>
        <h1><?php echo htmlspecialchars($post['title']); ?></h1>
    </header>
    <div class="post-container">
        <div class="post-image">
            <?php foreach ($images as $image): ?>
                <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="Зображення публікації">
            <?php endforeach; ?>
        </div>
        <div class="post-content">
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
            <div class="like-comment-count">
                <form method="POST" class="like-form" style="display: inline;">
                    <button type="submit" name="like" class="like-button" style="border: none; background: none; cursor: pointer;">
                        <span class="heart">❤️</span>
                    </button>
                    <span class="likes-count"><?php echo $likes_count; ?></span>
                </form>
                <span>Коментарі: <?php echo count($comments); ?></span>
                <form id="savePostForm" method="POST" style="margin-top: 10px;">
                    <button type="submit" name="save_post" class="submit-button">Зберегти публікацію</button>
                </form>
                <div id="savePostMessage" style="color: green; margin-top: 10px;"></div>
            </div>

            <!-- Відображення тегів -->
            <div class="tags">
                <h3>Теги:</h3>
                <?php foreach ($tags as $tag): ?>
                    <a href="search.php?tag=<?php echo urlencode($tag['tag_name']); ?>" class="tag">#<?php echo htmlspecialchars($tag['tag_name']); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div><div class="comment-section">
    <h2>Коментарі</h2>
    <?php if (count($comments) > 0): ?>
        <?php foreach ($comments as $comment): ?>
            <div class="comment">
                <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                <p><?php echo htmlspecialchars($comment['content']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Немає коментарів. Будьте першим, хто прокоментує!</p>
    <?php endif; ?>

    <form method="POST" id="commentForm">
        <textarea name="comment_text" id="comment_text" rows="4" class="comment-input" required></textarea>
        <button type="submit" class="submit-button">Додати коментар</button>
    </form>
</div>

<script>
   document.getElementById('savePostForm').addEventListener('submit', function(event) {
    event.preventDefault();

    fetch('?post_id=<?php echo $post_id; ?>&save_post=true', {
        method: 'GET', // Використання GET для сумісності із серверною логікою
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('savePostMessage').innerText = 'Публікацію збережено!';
        } else {
            document.getElementById('savePostMessage').innerText = data.message;
        }
    })
    .catch(error => console.error('Error:', error));
});

</script>

</body>
</html>