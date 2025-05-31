<?php
session_start();
require_once 'includes/db.php';

$errors = [];
$success = false; // 🔁 Стан для JavaScript-повідомлення

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirm_password = $_POST["confirm_password"] ?? '';
    $phone = trim($_POST["phone"] ?? '');
    $avatar_name = '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "Всі поля обов'язкові.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Невірний формат email.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Паролі не співпадають.";
    }

    if ($_FILES["avatar"]["error"] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $allowed)) {
            $avatar_name = uniqid("avatar_") . "." . $ext;
            if (!is_dir('uploads/avatars')) {
                mkdir('uploads/avatars', 0755, true);
            }
            move_uploaded_file($_FILES["avatar"]["tmp_name"], "uploads/avatars/" . $avatar_name);
        } else {
            $errors[] = "Недопустимий формат зображення.";
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, phone, avatar) VALUES (?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$username, $email, $hash, $phone, $avatar_name]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $success = true;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "Користувач з таким email вже існує.";
            } else {
                $errors[] = "Помилка при збереженні: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Реєстрація</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #ffd6e0, #ffb3c6);
            font-family: 'Segoe UI', sans-serif;
        }
        .bubble-box {
            background: #fff;
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 10px 20px rgba(255, 105, 180, 0.2);
        }
        .text-pink {
            color: #d63384;
        }
        .btn-dark {
            background-color: #d63384;
            border: none;
        }
        .btn-dark:hover {
            background-color: #b52b6f;
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="bubble-box" style="width: 100%; max-width: 500px;">
        <h2 class="text-center mb-4 text-pink">Реєстрація</h2>

        <?php if (!empty($errors)): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Помилка',
                    html: '<?= implode("<br>", array_map('htmlspecialchars', $errors)) ?>'
                });
            </script>
        <?php endif; ?>

        <form action="register.php" method="post" enctype="multipart/form-data" novalidate>
            <div class="mb-3">
                <label for="username">Ім’я користувача</label>
                <input type="text" id="username" name="username" class="form-control" required value="<?= htmlspecialchars($username ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="email">Електронна пошта</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="phone">Телефон</label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($phone ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password">Підтвердіть пароль</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="avatar">Аватар</label>
                <input type="file" id="avatar" name="avatar" class="form-control" accept="image/*">
            </div>
            <button type="submit" class="btn btn-dark w-100">Зареєструватись</button>
        </form>
    </div>
</div>

<?php if ($success): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Успішна реєстрація!',
        showConfirmButton: false,
        timer: 1500
    }).then(() => {
        window.location.href = 'index.php';
    });
</script>
<?php endif; ?>
</body>
</html>
