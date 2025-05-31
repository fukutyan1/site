<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Дані користувача
$stmt = $pdo->prepare("SELECT username, email, avatar FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "Користувача не знайдено.";
    exit;
}

$username = $user['username'];
$email = $user['email'];
$avatar = $user['avatar'];

// Пости користувача
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.created_at, 
           (SELECT image_path FROM post_images WHERE post_id = p.id LIMIT 1) as image 
    FROM posts p 
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();

// Повідомлення
$stmt = $pdo->prepare("
    SELECT m.*, u.username as sender_name, u.avatar as sender_avatar 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.receiver_id = ? 
    ORDER BY m.created_at DESC
");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Мій профіль</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #ffe0f0, #ffc8e0);
            font-family: 'Segoe UI', sans-serif;
        }

        .profile-card {
            background: #fff0f6;
            border-radius: 20px;
            padding: 30px;
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

        .avatar-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ff69b4;
        }

        .post-card {
            box-shadow: 0 0 10px rgba(255, 105, 180, 0.2);
            border-radius: 12px;
            overflow: hidden;
        }

        .post-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .message-box {
            background: #fff5fa;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 10px;
            box-shadow: 0 0 5px rgba(255, 0, 102, 0.1);
        }

        .message-box.unread {
            border: 2px solid #ff69b4;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 2px solid #ff69b4;
        }

        .back-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <a href="index.php" class="btn btn-outline-dark back-btn">⬅ Назад</a>

    <h2 class="text-center mb-4">👤 Мій профіль</h2>

    <div class="profile-card text-center mb-4">
        <img src="uploads/avatars/<?= htmlspecialchars($avatar) ?>" alt="Аватар" class="avatar-img mb-3">
        <h4><?= htmlspecialchars($username) ?></h4>
        <p class="mb-2"><?= htmlspecialchars($email) ?></p>
        <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap">
            <a href="edit_profile.php" class="btn btn-black">Редагувати профіль</a>
            <a href="add_post.php" class="btn btn-black">Додати пост</a>
            <a href="logout.php" class="btn btn-danger">Вийти</a>
        </div>
    </div>

    <h4 class="mb-3">📝 Мої пости</h4>
    <div class="row g-3 mb-5">
        <?php if (count($posts) === 0): ?>
            <p class="text-muted">У вас ще немає постів.</p>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="col-md-4">
                    <div class="card post-card">
                        <?php if ($post['image']): ?>
                            <img src="uploads/posts/<?= htmlspecialchars($post['image']) ?>" alt="Зображення поста">
                        <?php else: ?>
                            <img src="assets/img/no-image.png" alt="Немає зображення">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                            <p class="card-text"><small><?= $post['created_at'] ?></small></p>
                            <a href="post_view.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-primary">Переглянути</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <h4 class="mb-3">📨 Повідомлення</h4>
    <?php if (empty($messages)): ?>
        <p class="text-muted">У вас ще немає вхідних повідомлень.</p>
    <?php else: ?>
        <?php foreach ($messages as $msg): ?>
            <div class="message-box <?= $msg['is_read'] == 0 ? 'unread' : '' ?>">
                <div class="d-flex align-items-center mb-2">
                    <img src="uploads/avatars/<?= htmlspecialchars($msg['sender_avatar']) ?>" alt="Аватар" class="message-avatar">
                    <strong><?= htmlspecialchars($msg['sender_name']) ?></strong>
                    <span class="ms-auto text-muted small"><?= $msg['created_at'] ?></span>
                </div>
                <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                <form action="send_message.php" method="POST" class="mt-2">
                    <input type="hidden" name="to" value="<?= $msg['sender_id'] ?>">
                    <textarea name="message" class="form-control mb-2" placeholder="Відповісти..." required></textarea>
                    <button class="btn btn-sm btn-outline-primary">Відправити</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
