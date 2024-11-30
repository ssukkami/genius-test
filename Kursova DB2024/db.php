<?php
$servername = "localhost";
$username = "miasu"; // ваш логін
$password = ""; // ваш пароль
$dbname = "blog"; // назва бази даних

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
