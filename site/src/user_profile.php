<?php
session_start();
require_once 'includes/db.php';

if (!isset($_GET['id'])) {
    die("Користувача не знайдено.");
}

$user_id = intval($_GET['id']);

try {
    // 1. Отримати дані користувача
    $stmt = $pdo->prepare("SELECT username, email, avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("Користувача не знайдено.");
    }

    $username = $user['username'];
    $email = $user['email'];
    $avatar = $user['avatar'];

    // 2. Отримати пости користувача з головним зображенням
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.created_at, 
               (SELECT image_path FROM post_images WHERE post_id = p.id LIMIT 1) AS image 
        FROM posts p 
        WHERE p.user_id = ? 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Чат між поточним користувачем і цим користувачем (якщо не сам собі)
    $chatMessages = [];
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user_id) {
        $me = $_SESSION['user_id'];
        $them = $user_id;

        // Отримання повідомлень (виправлено параметри)
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$me, $them, $them, $me]);
        $chatMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Позначити повідомлення як прочитані
        $updateStmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
        $updateStmt->execute([$me, $them]);
    }

} catch (PDOException $e) {
    die("Помилка бази даних: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Профіль користувача <?= htmlspecialchars($username) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #e0f7ff, #d0eaff);
            font-family: 'Segoe UI', sans-serif;
        }

        .profile-card {
            background: #e6f3ff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.2);
        }

        .avatar-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #0d6efd;
        }

        .post-card {
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.2);
            border-radius: 12px;
            overflow: hidden;
        }

        .post-card img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }

        .chat-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 15px;
        }

        .message {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 8px;
            max-width: 75%;
        }

        .from-me {
            background-color: #d1e7dd;
            align-self: flex-end;
            margin-left: auto;
        }

        .from-them {
            background-color: #f8d7da;
            align-self: flex-start;
            margin-right: auto;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="text-center mb-4">👤 Профіль користувача</h2>

    <div class="text-center mb-4">
        <a href="index.php" class="btn btn-outline-secondary">← Назад на головну</a>
    </div>

    <div class="profile-card text-center mb-4">
        <img src="uploads/avatars/<?= htmlspecialchars($avatar) ?>" alt="Аватар" class="avatar-img mb-3">
        <h4><?= htmlspecialchars($username) ?></h4>
        <p class="mb-2 text-muted"><?= htmlspecialchars($email) ?></p>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user_id): ?>
            <a href="chat.php?to=<?= $user_id ?>" class="btn btn-outline-primary mt-3">💬 Перейти в чат</a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user_id): ?>
        <div class="mb-4">
            <h5>💬 Повідомлення між вами:</h5>
            <div class="chat-box d-flex flex-column">
                <?php foreach ($chatMessages as $msg): ?>
                    <div class="message <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'from-me' : 'from-them' ?>">
                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                        <div class="text-muted small text-end"><?= $msg['created_at'] ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($chatMessages)): ?>
                    <div class="text-muted">Ще немає повідомлень.</div>
                <?php endif; ?>
            </div>

            <form action="send_message.php" method="POST">
                <input type="hidden" name="to" value="<?= $user_id ?>">
                <textarea name="message" class="form-control mb-2" placeholder="Напишіть повідомлення..." required></textarea>
                <button class="btn btn-primary">Надіслати</button>
            </form>
        </div>
    <?php endif; ?>

    <h4 class="mb-3">📝 Пости користувача</h4>
    <div class="row g-3">
        <?php if (count($posts) > 0): ?>
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
                            <p class="card-text"><small><?= htmlspecialchars($post['created_at']) ?></small></p>
                            <a href="post_view.php?id=<?= (int)$post['id'] ?>" class="btn btn-sm btn-outline-primary">Переглянути</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">Користувач ще не створив жодного поста.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
