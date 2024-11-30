<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        
        // Redirect to blog creation prompt after login
        header("Location: after_reg.php");
        exit;
    } else {
        $error = "Неправильний логін або пароль.";
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Увійти</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-image: url('uploads/6.jpg'); /* Додайте фонове зображення */
            background-size: cover; /* Cover the entire viewport */
            background-position: center; /* Center the background image */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            animation: fadeIn 0.5s ease-in; /* Fade-in effect for body */
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        main {
            background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent black background */
            padding: 40px;
            border-radius: 20px; /* Increased rounding */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            width: 350px; /* Set a fixed width for the container */
            opacity: 0; /* Start hidden for fade-in effect */
            animation: slideIn 0.5s forwards; /* Slide-in effect for main */
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        h1 {
            text-align: center; /* Center the header */
            color: #fff; /* Change header color to white */
            margin-bottom: 20px; /* Add margin to separate from the form */
            font-size: 24px; /* Increased font size */
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #fff; /* Change label color to white */
        }

        input[type="email"],
        input[type="password"] {
            width: calc(100% - 24px); /* Full width minus padding */
            padding: 12px; /* Increased padding */
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: border 0.3s; /* Smooth border transition */
            box-sizing: border-box; /* Include padding in width calculation */
            display: block; /* Ensure each input is treated as a block element */
            margin-left: auto; /* Center the input field */
            margin-right: auto; /* Center the input field */
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border: 1px solid #4CAF50; /* Green border on focus */
            outline: none; /* Remove outline */
        }

        button {
            background-color: black; /* Black button */
            color: white;
            padding: 12px; /* Increased padding */
            border: none;
            border-radius: 50px; /* Maximum rounding for the button */
            cursor: pointer;
            transition: background-color 0.3s; /* Smooth background color transition */
            width: 100%; /* Full width button */
            font-size: 16px; /* Increase button font size */
        }

        button:hover {
            background-color: #333; /* Darker shade on hover */
        }

        p {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <main>
        <h1>Увійти</h1> <!-- Moved header above the form -->
        <form method="POST" action="login.php">
            <label for="email">Електронна пошта:</label>
            <input type="email" name="email" required>

            <label for="password">Пароль:</label>
            <input type="password" name="password" required>

            <button type="submit">Увійти</button>
        </form>
        <?php if (isset($error)): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>
    </main>
</body>
</html>
