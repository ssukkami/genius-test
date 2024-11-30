<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone_number = $_POST['phone_number'];
    $profile_visibility = $_POST['profile_visibility']; // 'open' або 'private'
    $registration_completed = 1; // Вказуємо, що реєстрація завершена
    $created_at = date('Y-m-d H:i:s'); // Поточна дата та час

    // Перевіряємо, чи існує користувач із таким логіном або email
    $user_check = $conn->prepare("SELECT COUNT(*) FROM Users WHERE username = ? OR email = ?");
    $user_check->execute([$username, $email]);
    $user_exists = $user_check->fetchColumn();

    if ($user_exists > 0) {
        // Якщо користувач уже існує, відображаємо повідомлення про помилку
        echo "Користувач із таким логіном або електронною поштою вже існує.";
    } else {
        // Вставка користувача в базу даних
        $stmt = $conn->prepare("INSERT INTO Users (name, lastname, username, email, password, phone_number, profile_visibility, registration_completed, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Виконуємо вставку та перевіряємо на помилки
        try {
            $stmt->execute([$name, $lastname, $username, $email, $password, $phone_number, $profile_visibility, $registration_completed, $created_at]);

            // Збереження нового user_id в сесії
            $_SESSION['user_id'] = $conn->lastInsertId();

            // Перенаправлення на сторінку вибору створення блогу
            header("Location: after_reg.php");
            exit; // Обов'язково виходимо після перенаправлення
        } catch (PDOException $e) {
            echo "Помилка при вставці даних: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Реєстрація</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
            animation: fadeIn 0.5s ease-in; /* Ефект появи для тіла */
            background-image: url('uploads/6.jpg'); /* Додайте фонове зображення */
            background-size: cover; /* Займати весь доступний простір */
            background-position: center; /* Центрувати фонове зображення */
        }

        header {
            text-align: center;
            margin-bottom: 20px;
        }

        h1 {
            color: #fff; /* Змінено колір заголовка для кращої видимості на темному фоні */
        }

        .form-container {
            background-color: rgba(0, 0, 0, 0.7); /* Прозорий чорний фон */
            padding: 30px; /* Збільшений відступ для більшого простору */
            border-radius: 30px; /* Збільшено радіус заокруглення */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 500px; /* Збільшена максимальна ширина для форми */
            margin: auto;
            opacity: 0; /* Спочатку прихована для ефекту появи */
            animation: slideIn 0.5s forwards; /* Ефект появи для форми */
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        label {
            display: block;
            margin-bottom: 6px; /* Відступ для кращого сприйняття */
            font-weight: bold;
            color: #fff; /* Змінено колір тексту для кращої видимості */
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        select {
            width: calc(100% - 12px); /* Встановити ширину текстбоксів з урахуванням padding */
            padding: 8px; /* Збільшений відступ для зручності */
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px; /* Збільшений розмір шрифту для кращої читабельності */
            transition: border-color 0.3s ease; /* Плавний перехід кольору бордюру */
            box-sizing: border-box; /* Включити padding у розрахунок ширини */
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="tel"]:focus,
        select:focus {
            border-color: #4CAF50; /* Зміна кольору бордюру при фокусі */
            outline: none; /* Вимкнення стандартного контуру */
        }

        button {
            background-color: #000; /* Чорний */
            color: white;
            padding: 12px 15px; /* Збільшений відступ кнопки */
            border: none;
            border-radius: 25px; /* Максимальне заокруглення кнопки */
            cursor: pointer;
            width: 100%;
            font-size: 18px; /* Збільшений розмір шрифту для кращої взаємодії */
            transition: background-color 0.3s ease; /* Плавний перехід кольору фону */
        }

        button:hover {
            background-color: #444; /* Зміна кольору кнопки при наведенні */
        }

        @media (max-width: 600px) {
            .form-container {
                padding: 20px; /* Зменшений відступ для менших екранів */
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Реєстрація</h1>
    </header>
    <main>
        <div class="form-container">
            <form method="POST" action="register.php">
                <label for="name">Ім'я:</label>
                <input type="text" name="name" required>

                <label for="lastname">Прізвище:</label>
                <input type="text" name="lastname" required>

                <label for="username">Логін:</label>
                <input type="text" name="username" required>

                <label for="email">Електронна пошта:</label>
                <input type="email" name="email" required>

                <label for="password">Пароль:</label>
                <input type="password" name="password" required>

                <label for="phone_number">Номер телефону:</label>
                <input type="tel" name="phone_number" required placeholder="+380" pattern="\+380[0-9]{2}[0-9]{7}" title="Формат: +380XXXXXXXXX (9 цифр після +380)">

                <label for="profile_visibility">Доступність профілю:</label>
                <select name="profile_visibility" required>
                    <option value="open">Публічний</option>
                    <option value="private">Приватний</option>
                </select>

                <button type="submit">Зареєструватися</button>
            </form>
        </div>
    </main>
</body>
</html>
