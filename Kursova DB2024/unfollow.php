<?php
session_start();
include 'db_connection.php'; // ваш файл для підключення до бази даних

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $follow_request_user_id = $_POST['follow_request_user_id'];
    $user_id = $_SESSION['user_id']; // Ваше значення user_id з сесії

    // Перевірте, чи існує підписка
    $query = "SELECT * FROM follows WHERE follower_id = ? AND following_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $follow_request_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Відписка: оновіть поле unfollowed_at і змініть статус на approved, якщо профіль відкритий
        $query = "UPDATE follows SET unfollowed_at = NOW(), status = 'approved' WHERE follower_id = ? AND following_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $follow_request_user_id);
        $stmt->execute();

        // Переадресація назад на профіль або на іншу сторінку
        header("Location: profile.php?user_id=" . $follow_request_user_id);
        exit;
    } else {
        echo "Ви не підписані на цього користувача.";
    }
}
?>
