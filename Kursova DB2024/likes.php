<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like'])) {
    $post_id = $_POST['post_id'];
    $user_id = $_SESSION['user_id'];

    // Перевірка, чи користувач уже поставив лайк
    $stmt = $conn->prepare("SELECT * FROM Likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);

    if ($stmt->rowCount() === 0) {
        // Якщо лайка ще не було, додайте його
        $stmt = $conn->prepare("INSERT INTO Likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $user_id]);
    }

    header("Location: post.php?id=" . $post_id); // Redirect back to the post
    exit;
}
?>

<!-- Вставте це у файл, що відповідає за відображення посту -->
<form method="POST" action="">
    <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
    <button type="submit" name="like">👍 Лайк</button>
</form>

<!-- Вивід кількості лайків -->
<p>Кількість лайків: 
    <?php
    $stmt = $conn->prepare("SELECT COUNT(*) as likes_count FROM Likes WHERE post_id = ?");
    $stmt->execute([$post['post_id']]);
    $likes_count = $stmt->fetch(PDO::FETCH_ASSOC)['likes_count'];
    echo $likes_count;
    ?>
</p>
