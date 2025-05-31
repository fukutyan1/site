<?php 
session_start();
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Портфоліо-сайт для спілкування</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .user-card img {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }

        .user-scroll-container {
            display: flex;
            overflow-x: auto;
            gap: 1rem;
            padding-bottom: 1rem;
            scroll-snap-type: x mandatory;
        }

        .user-card-wrapper {
            flex: 0 0 auto;
            scroll-snap-align: start;
            width: 250px;
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body class="pink-background">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 bubble-navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">Портфоліо</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php
            $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            $avatar = $user['avatar'] ?: 'default.png';
            ?>
            <div class="ms-auto">
                <a href="profile.php" class="d-inline-block">
                    <img src="uploads/avatars/<?= htmlspecialchars($avatar) ?>" alt="Аватар" class="rounded-circle border border-white" style="width:40px; height:40px;">
                </a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<div class="container mb-4">
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Пошук користувачів -->
        <div class="input-group mb-3" style="max-width: 400px;">
            <input type="text" class="form-control" id="user-search" placeholder="🔍 Введіть ім'я...">
            <button class="btn btn-dark" id="btn-search" type="button">Пошук</button>
        </div>
        <div id="search-results" class="list-group" style="display:none;"></div>
    <?php endif; ?>
</div>

<div class="container bubble-box">
    <?php if (!isset($_SESSION['user_id'])): ?>
        <h1 class="mb-4 text-pink text-center">Ласкаво просимо на сайт-портфоліо!</h1>
        <p class="lead mb-4 text-center">
            Тут ви можете створювати пости, залишати коментарі, переглядати профілі інших учасників.
            Щоб розпочати — увійдіть або зареєструйтесь!
        </p>
        <div class="text-center">
            <a href="login.php" class="btn btn-lg btn-dark me-3">Увійти</a>
            <a href="register.php" class="btn btn-lg btn-dark">Реєстрація</a>
        </div>
    <?php else: ?>
        <!-- Інші користувачі -->
        <h2 class="mb-3">Інші профілі</h2>
        <div class="user-scroll-container">
            <?php
            $currentUserId = intval($_SESSION['user_id']);
            $stmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE id != ? ORDER BY RAND() LIMIT 10");
            $stmt->execute([$currentUserId]);
            $users = $stmt->fetchAll();

            if ($users):
                foreach ($users as $user):
                    $avatar = $user['avatar'] ?: 'default.png';
            ?>
            <div class="user-card-wrapper bg-light rounded shadow-sm p-2 user-card">
                <a href="user_profile.php?id=<?= $user['id'] ?>" class="me-2 d-block text-center">
                    <img src="uploads/avatars/<?= htmlspecialchars($avatar) ?>" alt="Аватар" class="rounded-circle mb-2" style="width:60px; height:60px;">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($user['username']) ?></div>
                </a>
                <a href="user_profile.php?id=<?= $user['id'] ?>#chat" class="btn btn-sm btn-outline-primary w-100">Написати</a>
            </div>
            <?php endforeach; else: ?>
                <p class="text-muted">Поки що немає інших профілів.</p>
            <?php endif; ?>
        </div>

        <!-- Пости -->
        <h2 class="mt-4 mb-3">Останні пости</h2>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
        <?php
        $stmt = $pdo->query("
        SELECT posts.*, 
           (SELECT image_path FROM post_images WHERE post_id = posts.id LIMIT 1) AS image_path
        FROM posts 
        ORDER BY posts.created_at DESC
        LIMIT 20
        ");
        $posts = $stmt->fetchAll();

        foreach ($posts as $post):
        ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <?php if ($post['image_path']): ?>
                        <img src="uploads/posts/<?= htmlspecialchars($post['image_path']) ?>" class="card-img-top" alt="Зображення поста">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                        <p class="card-text"><?= nl2br(htmlspecialchars(mb_strimwidth($post['content'], 0, 100, '...'))) ?></p>
                        <a href="post_view.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-dark">Переглянути</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($_SESSION['user_id'])): ?>
<script>
    const searchInput = document.getElementById("user-search");
    const resultsDiv = document.getElementById("search-results");
    const searchButton = document.getElementById("btn-search");

    function performSearch() {
        const query = searchInput.value.trim();
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            resultsDiv.innerHTML = '';
            return;
        }

        fetch("search_users.php?q=" + encodeURIComponent(query))
            .then(res => res.text())
            .then(html => {
                resultsDiv.innerHTML = html;
                resultsDiv.style.display = 'block';
            });
    }

    searchInput.addEventListener("input", performSearch);
    searchButton.addEventListener("click", performSearch);
</script>
<?php endif; ?>
</body>
</html>
