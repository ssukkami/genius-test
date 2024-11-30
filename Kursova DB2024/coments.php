<?php
session_start();
include 'db.php';

// Перевірка наявності даних поста
if (!isset($_GET['post_id'])) {
    echo "Пост не знайдено.";
    exit;
}

$post_id = $_GET['post_id'];

// Обробка форми коментаря
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "Будь ласка, увійдіть, щоб залишити коментар.";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $content = $_POST['content']; // Виправлення: використання 'content'

    try {
        $stmt = $conn->prepare("INSERT INTO Comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $content]);
        header("Location: post.php?post_id=" . $post_id); // Повернення на сторінку посту
        exit;
    } catch (PDOException $e) {
        echo "Помилка: " . $e->getMessage();
    }
}
?>

<!-- Форма для коментарів -->
<form method="POST" action="">
    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
    <textarea name="content" required></textarea> <!-- Виправлення: використання 'content' -->
    <button type="submit" name="comment">Коментувати</button>
</form>

<!-- Вивід коментарів -->
<h3>Коментарі:</h3>
<?php
try {
    $stmt = $conn->prepare("SELECT c.content, u.username, c.created_at FROM Comments c JOIN Users u ON c.user_id = u.user_id WHERE c.post_id = ?");
    $stmt->execute([$post_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($comments) {
        foreach ($comments as $comment) {
            echo "<p><strong>" . htmlspecialchars($comment['username']) . ":</strong> " . htmlspecialchars($comment['content']) . " <small>(" . $comment['created_at'] . ")</small></p>";
        }
    } else {
        echo "<p>Немає коментарів.</p>";
    }
} catch (PDOException $e) {
    echo "Помилка: " . $e->getMessage();
}
?>
