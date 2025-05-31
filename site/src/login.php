<?php
session_start();
require_once 'includes/db.php';

$error = '';
$login_success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';

    if ($email && $password) {
        // Підготовка запиту
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Перевірка пароля
            if (password_verify($password, $user['password'])) {
                // Успішний вхід — зберігаємо id користувача в сесію
                $_SESSION["user_id"] = $user['id'];
                $login_success = true;
            } else {
                $error = "Невірний пароль.";
            }
        } else {
            $error = "Користувача з таким email не знайдено.";
        }
    } else {
        $error = "Будь ласка, заповніть всі поля.";
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Вхід</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #ffe6f0;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-box {
            background: #fff0f5;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 450px;
            margin: 50px auto;
        }
        h2 {
            color: #d63384;
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            color: #444;
        }
        .btn-dark {
            background-color: #000;
            color: #fff;
            border: none;
        }
        .btn-dark:hover {
            background-color: #333;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="login-box">
        <h2>Вхід до акаунту</h2>

        <?php if ($error): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Помилка',
                    text: '<?= htmlspecialchars($error, ENT_QUOTES) ?>'
                });
            </script>
        <?php endif; ?>

        <form action="login.php" method="post" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Електронна пошта</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($email ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Увійти</button>
        </form>
    </div>
</div>

<?php if ($login_success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Вхід успішний!',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            // Переадресація на головну сторінку після успішного входу
            window.location.href = 'index.php';
        });
    </script>
<?php endif; ?>

</body>
</html>
