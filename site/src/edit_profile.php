<?php
session_start();
require_once 'includes/db.php'; // Підключення PDO ($pdo)

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$success = '';
$error = '';
$showSuccessAlert = false;

// Отримання поточних даних користувача
try {
    $stmt = $pdo->prepare("SELECT username, email, avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Користувача не знайдено.");
    }

    $username = $user['username'];
    $email = $user['email'];
    $avatar = $user['avatar'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_username = trim($_POST['username']);
        $new_email = trim($_POST['email']);
        $new_password = $_POST['password'] ?? '';
        $avatar_file = $_FILES['avatar'] ?? null;

        $avatar_filename = $avatar;

        // Обробка аватара
        if ($avatar_file && $avatar_file['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $avatar_file['tmp_name'];
            $fileName = $avatar_file['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = uniqid() . '.' . $fileExtension;
                $uploadDir = __DIR__ . '/uploads/avatars/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $dest_path = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $avatar_filename = $newFileName;
                } else {
                    $error = 'Помилка при завантаженні аватара.';
                }
            } else {
                $error = 'Недопустимий тип файлу.';
            }
        }

        if (!$error) {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, avatar = ? WHERE id = ?");
                $params = [$new_username, $new_email, $hashed_password, $avatar_filename, $user_id];
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, avatar = ? WHERE id = ?");
                $params = [$new_username, $new_email, $avatar_filename, $user_id];
            }

            if ($stmt->execute($params)) {
                $success = 'Профіль оновлено!';
                $username = $new_username;
                $email = $new_email;
                $avatar = $avatar_filename;
                $showSuccessAlert = true;
            } else {
                $error = 'Помилка при оновленні профілю.';
            }
        }
    }
} catch (PDOException $e) {
    die("Помилка бази даних: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Редагування профілю</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(to right, #ffe0f0, #ffc8e0);
            font-family: 'Segoe UI', sans-serif;
        }

        .profile-form {
            background: #fff0f6;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 0 15px rgba(255, 192, 203, 0.5);
        }

        .btn-black {
            background-color: #000;
            color: #fff;
            border-radius: 12px;
        }

        .btn-black:hover {
            background-color: #333;
        }

        img.avatar-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ff69b4;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="text-center mb-4">Редагування профілю</h2>
    <div class="profile-form">
        <form method="POST" enctype="multipart/form-data" novalidate>
            <div class="mb-3">
                <label class="form-label">Ім’я користувача</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Новий пароль (не обов’язково)</label>
                <input type="password" name="password" class="form-control" autocomplete="new-password">
            </div>

            <div class="mb-3">
                <label class="form-label">Аватар</label><br>
                <?php if ($avatar): ?>
                    <img src="uploads/avatars/<?= htmlspecialchars($avatar) ?>" class="avatar-preview" alt="Аватар"><br>
                <?php endif; ?>
                <input type="file" name="avatar" class="form-control mt-2" accept=".jpg,.jpeg,.png,.gif">
            </div>

            <button type="submit" class="btn btn-black w-100">Зберегти</button>
        </form>
    </div>
</div>

<?php if ($showSuccessAlert): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Готово!',
        text: '<?= addslashes($success) ?>',
        confirmButtonColor: '#ff66b2',
        timer: 1500,
        timerProgressBar: true,
        willClose: () => {
            window.location.href = 'profile.php';
        }
    });
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Помилка!',
        text: '<?= addslashes($error) ?>',
        confirmButtonColor: '#ff66b2'
    });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
