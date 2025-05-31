<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['to'])) {
    header("Location: login.php");
    exit;
}

$from = $_SESSION['user_id'];
$to = intval($_GET['to']);

// Отримання імені одержувача та аватара
$stmt = $pdo->prepare("SELECT username, avatar FROM users WHERE id = ?");
$stmt->execute([$to]);
$receiver = $stmt->fetch();

if (!$receiver) {
    die("Користувач не знайдений.");
}

// Позначити повідомлення як прочитані
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?")
    ->execute([$from, $to]);

// Отримання повідомлень з аватарами
$stmt = $pdo->prepare("
    SELECT m.*, u.avatar, u.username 
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->execute([$from, $to, $to, $from]);
$messages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Чат з <?= htmlspecialchars($receiver['username']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #fff0f5;
        }

        .chat-box {
            max-height: 500px;
            overflow-y: auto;
            background: #fefefe;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .message {
            display: flex;
            align-items: flex-end;
            margin-bottom: 15px;
        }

        .from-me {
            justify-content: flex-end;
        }

        .from-them {
            justify-content: flex-start;
        }

        .bubble {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 25px;
            word-wrap: break-word;
            font-size: 15px;
            position: relative;
        }

        .from-me .bubble {
            background-color: #d1e7dd;
            border-bottom-right-radius: 5px;
        }

        .from-them .bubble {
            background-color: #f8d7da;
            border-bottom-left-radius: 5px;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff69b4;
        }

        .from-me .avatar {
            margin-left: 10px;
        }

        .from-them .avatar {
            margin-right: 10px;
        }

        .message-meta {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            text-align: right;
        }

        .delete-btn {
            background: none;
            border: none;
            color: #dc3545;
            font-size: 14px;
            margin-left: 5px;
            cursor: pointer;
        }

        textarea {
            resize: none;
            border-radius: 15px;
        }

        form[action="delete_message.php"] {
            display: inline;
        }
    </style>
</head>
<body class="container py-4">
    <h4 class="mb-4">Чат з <?= htmlspecialchars($receiver['username']) ?></h4>

    <div class="chat-box d-flex flex-column">
        <?php foreach ($messages as $msg): ?>
            <?php
                $isMe = $msg['sender_id'] == $from;
                $avatarFile = 'uploads/avatars/' . ($msg['avatar'] ?? 'default.png');
                if (!file_exists($avatarFile) || !$msg['avatar']) {
                    $avatarFile = 'uploads/avatars/default.png';
                }
            ?>
            <div class="message <?= $isMe ? 'from-me' : 'from-them' ?>">
                <?php if (!$isMe): ?>
                    <img src="<?= $avatarFile ?>" class="avatar" alt="avatar">
                <?php endif; ?>

                <div>
                    <div class="bubble">
                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    </div>
                    <div class="message-meta">
                        <?= htmlspecialchars($msg['created_at']) ?>
                        <?php if ($isMe): ?>
                            <form method="post" action="delete_message.php">
                                <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                <button type="submit" class="delete-btn" onclick="return confirm('Видалити це повідомлення?')">🗑️</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isMe): ?>
                    <img src="<?= $avatarFile ?>" class="avatar" alt="avatar">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post" action="send_message.php">
        <input type="hidden" name="to" value="<?= $to ?>">
        <textarea name="message" class="form-control mb-2" rows="2" placeholder="Введіть повідомлення..." required></textarea>
        <button class="btn btn-primary w-100">Надіслати ✉️</button>
    </form>

    <div class="text-center mt-3">
        <a href="user_profile.php?id=<?= $to ?>" class="btn btn-outline-secondary">← Назад до профілю <?= htmlspecialchars($receiver['username']) ?></a>
    </div>
</body>
</html>
