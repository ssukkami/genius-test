<?php
// Перевірка, чи сесія вже була запущена
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'db.php';

// Отримання даних блогу через blog_id
if (isset($_GET['blog_id'])) {
    $blog_id = $_GET['blog_id'];

    // Отримання інформації про блог разом з автором
    $stmt = $conn->prepare("
        SELECT b.title, b.description, u.username AS author 
        FROM Blogs b 
        JOIN Users u ON b.user_id = u.user_id 
        WHERE b.blog_id = ? AND (b.deactivated_at IS NULL OR b.deactivated_at > NOW())
    ");
    $stmt->execute([$blog_id]);
    $blog = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$blog) {
        echo "Публікація заблокована або деактивована";
        exit;
    }

    // Отримання публікацій в блозі
    $stmt = $conn->prepare("SELECT * FROM Posts WHERE blog_id = ?");
    $stmt->execute([$blog_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    echo "Блог не знайдено.";
    exit;
}

// Додавання рейтингу
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rating']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $rating = $_POST['rating'];

    $stmt = $conn->prepare("INSERT INTO BlogRatings (blog_id, user_id, rating, rated_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$blog_id, $user_id, $rating]);
    echo "<script>alert('Дякуємо за оцінку!'); window.location.href='browse_blogs.php';</script>";
}
?>

<style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #1a1a1a;
            color: #ddd;
            margin: 0;
            background-image: url('uploads/2.jpg');
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            background-size: cover; 
    background-position: center; 
    background-repeat: repeat; /* Дублювання фону */
    background-attachment: fixed; /* Висота на весь екран */
        }

        .blog-container {
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.7);
            padding: 20px;
            width: 90%;
            max-width: 600px;
            transition: transform 0.3s ease;
            color: #ddd;
        }

        .blog-container:hover {
            transform: scale(1.02);
        }

        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background-color: rgba(20, 20, 20, 0.9);
            color: #f0f0f0;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            text-align: center;
            width: 320px;
            z-index: 1000;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .popup.show {
            display: block;
            opacity: 1;
        }

        .star-rating {
            display: flex;
            justify-content: center;
            gap: 8px;
            font-size: 32px;
            cursor: pointer;
        }

        .star {
            color: #555;
            transition: color 0.3s;
        }

        .star.filled {
            color: #FFD700;
        }

        .popup h3 {
            font-size: 1.2em;
            margin-bottom: 15px;
            color: #bbb; /* Сірий колір для заголовка */
        }

        .popup button {
            margin-top: 15px;
            padding: 10px 15px;
            border: none;
            background-color: #808080; /* Сірий фон */
            color: #f0f0f0;
            border-radius: 30px; /* Округлі краї */
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .popup button:hover {
            background-color: #666; /* Трохи темніший сірий при наведенні */
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #ddd;
            text-decoration: underline;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #fff; /* Біліший колір при наведенні */
        }

        a {
            color: #808080;
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            color: #ddd;
        }

        h1, h2 {
            color: #bbb; /* Сірий колір заголовків */
        }
    </style>

    <script>
        let selectedRating = 0;

        function showRatingPopup() {
            document.getElementById('rating-popup').classList.add('show');
        }

        function hideRatingPopup() {
            document.getElementById('rating-popup').classList.remove('show');
        }

        function setRating(rating) {
            selectedRating = rating;
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                star.classList.toggle('filled', index < rating);
            });
        }

        function submitRating() {
            if (selectedRating === 0) {
                alert("Будь ласка, оберіть оцінку.");
                return;
            }

            document.getElementById('rating-value').value = selectedRating;
            document.getElementById('rating-form').submit();
        }
    </script>
</head>
<body>
    <div class="blog-container">
        <h1><?php echo htmlspecialchars($blog['title']); ?></h1>
        <p>Опис: <?php echo htmlspecialchars($blog['description']); ?></p>
        <p>Автор: <?php echo htmlspecialchars($blog['author']); ?></p>

        <span onclick="showRatingPopup();" class="back-link">Оцінити цей блог</span>

        <h3>Публікації в цьому блозі:</h3>
        <?php if (count($posts) > 0): ?>
            <ul>
                <?php foreach ($posts as $post): ?>
                    <li><a href="view_post.php?post_id=<?php echo $post['post_id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>У цьому блозі ще немає публікацій.</p>
        <?php endif; ?>
    </div>

    <!-- Спливаюче вікно для оцінки -->
    <div id="rating-popup" class="popup">
        <h3>Оцініть цей блог</h3>
        <div class="star-rating">
            <span class="star" onclick="setRating(1)">&#9734;</span>
            <span class="star" onclick="setRating(2)">&#9734;</span>
            <span class="star" onclick="setRating(3)">&#9734;</span>
            <span class="star" onclick="setRating(4)">&#9734;</span>
            <span class="star" onclick="setRating(5)">&#9734;</span>
        </div>
        <form id="rating-form" method="POST">
            <input type="hidden" name="rating" id="rating-value" value="0">
            <button type="button" onclick="submitRating();">Оцінити</button>
            <button type="button" onclick="hideRatingPopup();">Ні, повернутись назад</button>
        </form>
    </div>
</body>
</html>
