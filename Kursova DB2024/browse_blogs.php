<?php
session_start();
include 'db.php';

$user_logged_in = isset($_SESSION['user_id']);

$stmt = $conn->query("
    SELECT b.blog_id, b.title, b.description, u.username, COALESCE(AVG(r.rating), 0) AS average_rating
    FROM Blogs b
    JOIN Users u ON b.user_id = u.user_id
    LEFT JOIN BlogRatings r ON b.blog_id = r.blog_id
    WHERE b.deactivated_at IS NULL  -- Перевірка на деактивовані блоги
    GROUP BY b.blog_id, b.title, b.description, u.username
    ORDER BY 
        CASE 
            WHEN AVG(r.rating) >= 4.5 THEN 0
            ELSE 1
        END, 
        average_rating DESC
");

$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Перегляд блогів</title>
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
    background-image: url('uploads/Black, Orange and Blue Gradient Graphic Design Portfolio Presentation (6).jpg');
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
            color: #ccccff;
        }

        h1 {
            margin: 0;
            font-size: 2.5em;
        }

        nav ul {
            list-style-type: none;
            padding: 0;
            margin: 10px 0 0;
            display: flex;
            justify-content: center;
        }

        nav li {
            margin: 0 15px;
        }

        nav a {
            text-decoration: none;
            color: #ffffff;
            transition: color 0.3s ease;
        }


        h2 {
            font-size: 2em;
            margin-bottom: 20px;
        }

        section {
            max-width: 800px;
            margin: 0 auto;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        li {
            background-color: rgba(0, 0, 0, 0.7);
            margin: 10px 0;
            padding: 15px;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        li:hover {
            transform: scale(1.02); /* Збільшення елемента при наведенні */
        }

        footer {
            background-color: rgba(0, 0, 0, 0.7); /* Чорний фон з прозорістю */
            text-align: center;
            padding: 10px 0;
        }

        footer p {
            margin: 0;
        }
    </style>
</head>
<body>
<header>
        <nav class="navigation">
            <a href="home.php">ГОЛОВНА</a>
            <a href="profile.php">МІЙ ПРОФІЛЬ</a>
            <a href="logout.php">ВИХІД</a>
        </nav>
    </header>
    <main>
        <section>
            <h2>Доступні блоги</h2>
            <?php if (count($blogs) > 0): ?>
                <ul>
                    <?php foreach ($blogs as $blog): ?>
                        <li>
                            <h3><?php echo htmlspecialchars($blog['title']); ?></h3>
                            <p><?php echo htmlspecialchars($blog['description']); ?></p>
                            <p>Автор: <?php echo htmlspecialchars($blog['username']); ?></p>
                            <a href="view_blog.php?blog_id=<?php echo $blog['blog_id']; ?>">Переглянути блог</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Немає блогів для перегляду.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Блог Платформа</p>
    </footer>
</body>
</html>
