<?php
require 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... [твій існуючий код реєстрації тут] ...
}

$pageTitle = "Реєстрація";
require 'includes/header.php';
?>

<h2>Реєстрація</h2>
<form action="" method="post" enctype="multipart/form-data" class="card p-4 shadow-sm" style="max-width: 400px;">
    <div class="mb-3">
        <label class="form-label">Ім'я користувача</label>
        <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Телефон</label>
        <input type="text" name="phone" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Пароль</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Аватар</label>
        <input type="file" name="avatar" accept="image/*" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">Зареєструватись</button>
</form>
