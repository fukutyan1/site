<?php
session_start();
require_once 'includes/db.php'; // Підключення PDO ($pdo)

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $user_id = (int)$_SESSION['user_id'];

    if ($title === '' || $content === '') {
        $errors[] = 'Заповніть усі поля.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $title, $content]);
            $post_id = $pdo->lastInsertId();

            if (!empty($_FILES['images']['name'][0])) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $uploadDir = __DIR__ . '/uploads/posts/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $filesCount = count($_FILES['images']['name']);
                $maxFiles = 10;
                $filesToUpload = min($filesCount, $maxFiles);

                for ($i = 0; $i < $filesToUpload; $i++) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));

                        if (in_array($ext, $allowedExtensions)) {
                            $newFileName = uniqid('post_', true) . '.' . $ext;
                            $destPath = $uploadDir . $newFileName;

                            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $destPath)) {
                                $img_stmt = $pdo->prepare("INSERT INTO post_images (post_id, image_path) VALUES (?, ?)");
                                $img_stmt->execute([$post_id, $newFileName]);
                            }
                        }
                    }
                }
            }

            $pdo->commit();

            header('Location: profile.php');
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Помилка при збереженні поста: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8" />
    <title>Новий пост</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #ffe0f0, #ffc8e0);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 0 80px;
        }
        .post-form {
            background: #fff0f6;
            padding: 30px 35px;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(255, 105, 180, 0.3);
            max-width: 580px;
            margin: 0 auto;
        }
        .btn-primary {
            background-color: #ff66b2;
            border-color: #ff66b2;
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 30px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #e0559b;
            border-color: #e0559b;
        }
        label {
            font-weight: 600;
            color: #b30059;
        }
    </style>
</head>
<body>
<div class="post-form">
    <h2 class="text-center mb-4" style="color:#b30059;">Створити пост</h2>

    <?php if (!empty($errors)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Помилка',
                html: '<?= implode("<br>", array_map('htmlspecialchars', $errors)) ?>',
                confirmButtonColor: '#ff66b2'
            });
        </script>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
        <div class="mb-4">
            <label for="title" class="form-label">Заголовок</label>
            <input type="text" id="title" name="title" class="form-control" required
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
        </div>
        <div class="mb-4">
            <label for="content" class="form-label">Текст поста</label>
            <textarea id="content" name="content" class="form-control" rows="5" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        </div>
        <div class="mb-4">
            <label for="images" class="form-label">Зображення (до 10)</label>
            <input type="file" id="images" name="images[]" class="form-control" multiple accept="image/*" />
        </div>
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-primary px-5">Опублікувати</button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
