<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id']; // ID публікації, яку потрібно зберегти

// Перевірка, чи вже збережено цю публікацію користувачем
$stmt = $conn->prepare("SELECT * FROM SavedPosts WHERE user_id = ? AND post_id = ?");
$stmt->execute([$user_id, $post_id]);
$savedPost = $stmt->fetch();

if (!$savedPost) {
    // Збереження нової публікації
    $stmt = $conn->prepare("INSERT INTO SavedPosts (user_id, post_id, saved_at) VALUES (?, ?, NOW())");
    if ($stmt->execute([$user_id, $post_id])) {
        echo "Публікацію збережено успішно!";
    } else {
        echo "Помилка під час збереження публікації.";
    }
} else {
    echo "Ця публікація вже збережена.";
}
?>
