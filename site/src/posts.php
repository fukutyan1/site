<?php
require_once 'includes/db.php';

$sql = "SELECT posts.*, users.username, users.avatar FROM posts 
        JOIN users ON posts.user_id = users.id 
        ORDER BY posts.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Пости</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <h2>Усі пости</h2>

    <?php while ($post = $result->fetch_assoc()): ?>
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center">
                <img src="uploads/avatars/<?= $post['avatar'] ?>" class="rounded-circle me-2" width="40">
                <strong><?= htmlspecialchars($post['username']) ?></strong>
                <span class="ms-auto text-muted"><?= $post['created_at'] ?></span>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                <p class="card-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                <a href="post_view.php?id=<?= $post['id'] ?>" class="btn btn-outline-primary">Переглянути</a>
            </div>
        </div>
    <?php endwhile; ?>
</div>
</body>
</html>
