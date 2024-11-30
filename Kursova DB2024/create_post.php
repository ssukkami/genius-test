<?php
session_start();
include 'db.php';

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit;
}

$image_urls = []; // Масив для зберігання URL-адрес зображень

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blog_id = $_POST['blog_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $is_draft = isset($_POST['is_draft']) ? 1 : 0;
    $tags = $_POST['tags']; // Отримання тегів
    $user_id = $_SESSION['user_id']; // Отримання ID користувача

    // Обробка зображень
    if (isset($_FILES['images'])) {
        $image_dir = 'uploads/'; // Директорія для зберігання зображень
        $max_images = 5;

        // Перевірка кількості зображень
        if (count($_FILES['images']['name']) > $max_images) {
            echo "<script>alert('Ви можете завантажити не більше $max_images зображень!');</script>";
            exit;
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                // Перевірка типу файлу
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['images']['type'][$key];

                if (in_array($file_type, $allowed_types)) {
                    // Генерація унікального імені файлу
                    $image_name = uniqid('img_', true) . '.' . pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                    $image_path = $image_dir . $image_name;

                    // Збереження оригінального зображення
                    if (move_uploaded_file($tmp_name, $image_path)) {
                        $image_urls[] = $image_path; // Додаємо шлях до масиву зображень
                    } else {
                        echo "<script>alert('Не вдалося зберегти зображення: {$image_name}!');</script>";
                    }
                } else {
                    echo "<script>alert('Непідтримуваний формат зображення: $file_type!');</script>";
                }
            }
        }
    }

    // Додавання публікації до бази даних з user_id
    $stmt = $conn->prepare("INSERT INTO Posts (blog_id, title, content, is_draft, user_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$blog_id, $title, $content, $is_draft, $user_id]);
    $post_id = $conn->lastInsertId(); // Отримання ID новоствореної публікації

    // Додавання зображень до таблиці postimages
    foreach ($image_urls as $image_url) {
        $stmt = $conn->prepare("INSERT INTO postimages (post_id, image_url) VALUES (?, ?)");
        $stmt->execute([$post_id, $image_url]);
    }

    // Додавання тегів до таблиці Tags та зв’язку з постом
    $tagList = explode(',', $tags);
    foreach ($tagList as $tag) {
        $tag = trim($tag);
        
        // Перевірка, чи існує тег, інакше додаємо новий
        $stmt = $conn->prepare("SELECT tag_id FROM Tags WHERE tag_name = ?");
        $stmt->execute([$tag]);
        $existingTag = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingTag) {
            $tag_id = $existingTag['tag_id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO Tags (tag_name) VALUES (?)");
            $stmt->execute([$tag]);
            $tag_id = $conn->lastInsertId();
        }

        // Додавання зв’язку між тегом і постом
        $stmt = $conn->prepare("INSERT INTO PostTags (post_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $tag_id]);
    }

    // Перенаправлення на сторінку блогу
    header("Location: view_blog.php?blog_id=" . $blog_id);
    exit;
}

// Отримання блогів користувача для вибору
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM Blogs WHERE user_id = ?");
$stmt->execute([$user_id]);
$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Створення публікації</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Cy Grotesk Wide', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('uploads/Black, Orange and Blue Gradient Graphic Design Portfolio Presentation (1).jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: scroll;
            color: #FFFFFF;
            background-position: center;
        }
        header {
            text-align: center;
            margin: 20px 0;
        }
        h1 {
            text-transform: uppercase;
            font-size: 2.5rem;
        }
        main {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: calc(100vh - 80px);
        }
        form {
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 15px;
            padding: 20px;
            width: 400px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-size: 1.1rem;
            text-transform: uppercase;
        }
        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 1rem;
            resize: vertical;
        }
        textarea {
            min-height: 100px;
            overflow-y: auto;
        }
        button {
            background-color: #FF5733;
            color: #FFFFFF;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        button:hover {
            background-color: #C70039;
        }
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .image-preview img {
            max-width: 100px;
            margin-right: 10px;
        }
        .remove-image {
            cursor: pointer;
            color: red;
            margin-left: 5px;
        }
    </style>
    <script>
        function previewImages() {
            const previewContainer = document.getElementById('image-preview');
            previewContainer.innerHTML = '';

            const files = document.querySelector('input[type="file"]').files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;

                    const removeLink = document.createElement('span');
                    removeLink.innerText = 'Видалити';
                    removeLink.classList.add('remove-image');
                    removeLink.onclick = function () {
                        const updatedFiles = Array.from(files).filter((_, index) => index !== i);
                        const dataTransfer = new DataTransfer();
                        updatedFiles.forEach(file => dataTransfer.items.add(file));
                        document.querySelector('input[type="file"]').files = dataTransfer.files;
                        previewImages();
                    };

                    const div = document.createElement('div');
                    div.appendChild(img);
                    div.appendChild(removeLink);
                    previewContainer.appendChild(div);
                };

                reader.readAsDataURL(file);
            }
        }
    </script>
</head>
<body>
    <header>
        <h1>Створення публікації</h1>
    </header>
    <main>
        <form action="" method="POST" enctype="multipart/form-data">
            <label for="blog_id">Оберіть блог:</label>
            <select name="blog_id" required>
                <option value="">Оберіть блог</option>
                <?php foreach ($blogs as $blog): ?>
                    <option value="<?= htmlspecialchars($blog['blog_id']) ?>"><?= htmlspecialchars($blog['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="title">Заголовок:</label>
            <input type="text" name="title" required>
            <label for="content">Контент:</label>
            <textarea name="content" required></textarea>
            <label for="tags">Теги (розділяйте комами):</label>
            <input type="text" name="tags" id="tags" placeholder="Наприклад, природа, подорожі, поради">
            <label for="images">Зображення:</label>
            <input type="file" name="images[]" multiple accept="image/*" onchange="previewImages()">
            <div id="image-preview" class="image-preview"></div>
            <label for="is_draft">
                <input type="checkbox" name="is_draft" value="1"> Зберегти як чернетку
            </label>
            <button type="submit">Опублікувати</button>
        </form>
    </main>
</body>
</html>
