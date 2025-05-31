<?php
require 'includes/db.php'; // Має бути підключення до БД у $pdo
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Введіть email і пароль';
    } else {
        // Шукаємо користувача в базі за email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['username'];
                header('Location: profile.php');
                exit;
            } else {
                $error = "Неправильний пароль";
            }
        } else {
            $error = "Користувача з таким email не знайдено";
        }
    }
}
?>
