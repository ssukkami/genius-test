<?php
session_start();
include 'db.php';

$user_logged_in = isset($_SESSION['user_id']);
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

$users = [];
$posts = [];
$blogs = [];
$is_post_search = false;
$is_blog_search = false;

if (!empty($query)) {
    // Пошук публікацій за тегом
    $sql_posts = "SELECT p.*, b.title AS blog_title, b.user_id, u.username AS author_username, 
                         u.profile_picture AS author_avatar, 
                         GROUP_CONCAT(DISTINCT t.tag_name) AS tags 
                  FROM Posts p
                  LEFT JOIN Blogs b ON p.blog_id = b.blog_id
                  LEFT JOIN Users u ON b.user_id = u.user_id
                  LEFT JOIN PostTags pt ON pt.post_id = p.post_id
                  LEFT JOIN Tags t ON pt.tag_id = t.tag_id
                  WHERE t.tag_name LIKE :query
                  GROUP BY p.post_id";

    $stmt_posts = $conn->prepare($sql_posts);
    $stmt_posts->execute(['query' => '%' . $query . '%']);
    $posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);

    // Пошук блогів за заголовком
    $sql_blogs = "SELECT b.blog_id, b.title, b.user_id, u.username AS author_username, 
                         u.profile_picture AS author_avatar 
                  FROM Blogs b
                  LEFT JOIN Users u ON b.user_id = u.user_id
                  WHERE b.title LIKE :query";

    $stmt_blogs = $conn->prepare($sql_blogs);
    $stmt_blogs->execute(['query' => '%' . $query . '%']);
    $blogs = $stmt_blogs->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($posts)) {
        $is_post_search = true;
    } elseif (!empty($blogs)) {
        $is_blog_search = true;
    } else {
        // Пошук користувачів за нікнеймом з витягом аватарки
        $sql_users = "SELECT user_id, username, profile_visibility, profile_picture 
                      FROM Users 
                      WHERE username LIKE :query";
        $stmt_users = $conn->prepare($sql_users);
        $stmt_users->execute(['query' => '%' . $query . '%']);
        $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Результати пошуку</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-image: url('uploads/Black, Orange and Blue Gradient Graphic Design Portfolio Presentation (6).jpg');
        background-size: cover; 
        background-position: center; 
        background-repeat: repeat;
        background-attachment: fixed;
        color: #f1f1f1;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        transition: all 0.5s ease;
    }

    main {
        max-width: 800px;
        margin: 40px auto;
        padding: 20px;
        background-color: rgba(0, 0, 0, 0.7);
        border-radius: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        opacity: 0;
        animation: fadeIn 1s forwards;
    }

    @keyframes fadeIn {
        0% {
            opacity: 0;
        }
        100% {
            opacity: 1;
        }
    }

    h1, h2 {
        text-align: center;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: bold;
        animation: slideUp 1s ease-in-out;
    }

    @keyframes slideUp {
        0% {
            opacity: 0;
            transform: translateY(30px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    section {
        margin-bottom: 20px;
        padding: 10px;
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        transition: all 0.3s ease-in-out;
    }

    section:hover {
        background-color: rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }

    ul {
        list-style: none;
        padding: 0;
    }

    li {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        background-color: rgba(0, 0, 0, 0.6);
        padding: 20px;
        margin-bottom: 15px;
        border-radius: 15px;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }

    li:hover {
        background-color: rgba(0, 0, 0, 0.8);
        transform: scale(1.05);
    }

    img.avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin-bottom: 10px;
        border: 2px solid #f1f1f1;
        transition: transform 0.3s ease;
    }

    img.avatar:hover {
        transform: scale(1.1);
    }

    a {
        color: #1e90ff;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    a:hover {
        text-decoration: underline;
        color: #ff4500;
    }

    .post-title, .blog-title {
        font-size: 1.2em;
        font-weight: bold;
        color: #ffffff;
        margin: 10px 0;
        text-transform: capitalize;
        letter-spacing: 1px;
    }

    .author-info {
        display: flex;
        align-items: center;
        font-size: 1em;
        margin-bottom: 10px;
        justify-content: flex-start; /* Align to the left */
    }

    .author-info a {
        color: #f1f1f1;
        margin-left: 10px;
    }

    .author-info a:hover {
        color: #ff4500;
    }

    small {
        display: block;
        margin-top: 10px;
        font-size: 0.9em;
        color: #bbb;
    }
</style>

<body>
    <header>
        <h1>Результати пошуку для "<?php echo htmlspecialchars($query); ?>"</h1>
    </header>

    <main>
        <!-- Публікації -->
        <?php if ($is_post_search || empty($users)): ?>
            <section>
                <h2>Публікації</h2>
                <?php if (!empty($posts)): ?>
                    <ul>
                        <?php foreach ($posts as $post): ?>
                            <li>
                                <!-- Зміщено фотографію та нікнейм в верх -->
                                <div class="author-info">
                                    <?php if (!empty($post['author_avatar'])): ?>
                                        <img src="<?php echo htmlspecialchars($post['author_avatar']); ?>" alt="Аватар автора" class="avatar">
                                    <?php else: ?>
                                        <img src="default-avatar.png" alt="Аватар автора" class="avatar">
                                    <?php endif; ?>
                                    <a href="profile.php?user_id=<?php echo $post['user_id']; ?>">
                                        <?php echo htmlspecialchars($post['author_username']); ?>
                                    </a>
                                </div>

                                <!-- Заголовок та вміст публікації -->
                                <div>
                                    <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                    <small>Теги: <?php echo htmlspecialchars($post['tags']); ?></small>
                                    <br>
                                    <a href="view_post.php?post_id=<?php echo $post['post_id']; ?>">Переглянути публікацію</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Публікації не знайдені.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Блоги -->
        <?php if (!empty($blogs)): ?>
            <section>
                <h2>Блоги</h2>
                <ul>
                    <?php foreach ($blogs as $blog): ?>
                        <li>
                            <!-- Зміщено фотографію та нікнейм в верх -->
                            <div class="author-info">
                                <?php if (!empty($blog['author_avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($blog['author_avatar']); ?>" alt="Аватар автора" class="avatar">
                                <?php else: ?>
                                    <img src="default-avatar.png" alt="Аватар автора" class="avatar">
                                <?php endif; ?>
                                <a href="profile.php?user_id=<?php echo $blog['user_id']; ?>">
                                    <?php echo htmlspecialchars($blog['author_username']); ?>
                                </a>
                            </div>

                            <!-- Заголовок та вміст блогу -->
                            <div>
                                <h3 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h3>
                                <a href="view_blog.php?blog_id=<?php echo $blog['blog_id']; ?>">Переглянути блог</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php else: ?>
            <p>Блоги не знайдені.</p>
        <?php endif; ?>

        <!-- Користувачі -->
        <?php if (empty($posts) && empty($blogs) && !empty($query)): ?>
            <section>
                <h2>Користувачі</h2>
                <?php if (!empty($users)): ?>
                    <ul>
                        <?php foreach ($users as $user): ?>
                            <li>
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Аватар користувача" class="avatar">
                                <?php else: ?>
                                    <img src="default-avatar.png" alt="Аватар користувача" class="avatar">
                                <?php endif; ?>
                                <a href="profile.php?user_id=<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Користувачі не знайдені.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
