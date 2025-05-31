<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8" />
    <title><?php echo $pageTitle ?? "Сторінка"; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/style.css" rel="stylesheet" />
</head>
<body class="container py-4">
<nav class="mb-4">
    <a href="index.php" class="btn btn-secondary">Головна</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="profile.php" class="btn btn-primary">Профіль</a>
        <a href="logout.php" class="btn btn-danger">Вийти</a>
    <?php else: ?>
        <a href="auth_login.php" class="btn btn-success">Вхід</a>
        <a href="auth_register.php" class="btn btn-outline-primary">Реєстрація</a>
    <?php endif; ?>
</nav>
