<?php
session_start();
require_once 'includes/db.php';

if (!isset($_GET['id'])) {
    die("Пост не знайдено.");
}

$post_id = intval($_GET['id']);

// Отримуємо пост і дані користувача
$stmt = $pdo->prepare("SELECT p.*, u.username, u.avatar FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id = :post_id");
$stmt->execute(['post_id' => $post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Пост не знайдено.");
}

// Отримуємо зображення поста
$stmt_images = $pdo->prepare("SELECT image_path FROM post_images WHERE post_id = :post_id");
$stmt_images->execute(['post_id' => $post_id]);
$post_images = $stmt_images->fetchAll(PDO::FETCH_COLUMN);

// Лайки
$likes_stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
$likes_stmt->execute([$post_id]);
$likes_count = $likes_stmt->fetchColumn();

$user_liked = false;
if (isset($_SESSION['user_id'])) {
    $check_like = $pdo->prepare("SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?");
    $check_like->execute([$post_id, $_SESSION['user_id']]);
    $user_liked = $check_like->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #ffe0f0, #ffc8e0);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-post {
            background: #fff0f6;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(255, 105, 180, 0.2);
        }
        .btn-black {
            background-color: #000;
            color: #fff;
            border-radius: 12px;
            transition: background-color 0.3s;
        }
        .btn-black:hover {
            background-color: #333;
            color: #fff;
        }
        img.post-image {
            max-height: 400px;
            object-fit: cover;
            width: 100%;
            border-radius: 12px;
            margin-bottom: 15px;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        .comment-image-preview {
            max-width: 120px;
            max-height: 120px;
            margin-top: 10px;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 0 10px rgba(255, 105, 180, 0.3);
        }
        .comment-reply-btn {
            font-size: 0.9rem;
            color: #ff69b4;
            cursor: pointer;
            margin-left: 10px;
        }
        .comment-reply-btn:hover {
            text-decoration: underline;
        }
        .comment-box {
            border-left: 2px solid #ff69b4;
            padding-left: 10px;
            margin-bottom: 15px;
        }
        .comment-actions span {
            margin-right: 15px;
            cursor: pointer;
            user-select: none;
            color: #ff1493;
        }
        .comment-actions span:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container py-5" style="max-width: 900px;">

    <a href="index.php" class="btn btn-outline-secondary mb-4">← Назад на головну</a>
    <div class="card card-post p-4 mb-5">
        <div class="d-flex align-items-center mb-3">
            <img src="uploads/avatars/<?= htmlspecialchars($post['avatar']) ?>" width="50" height="50" class="rounded-circle me-3 border border-2 border-pink" alt="Аватар користувача">
            <strong><?= htmlspecialchars($post['username']) ?></strong>
        </div>
        <h2 class="mb-3"><?= htmlspecialchars($post['title']) ?></h2>
        <p class="mb-4" style="white-space: pre-line;"><?= htmlspecialchars($post['content']) ?></p>

        <?php if (!empty($post_images)): ?>
            <?php foreach ($post_images as $img): ?>
                <img src="uploads/posts/<?= htmlspecialchars($img) ?>" class="post-image" alt="Зображення поста">
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="mb-3">
                <button id="like-btn" class="btn <?= $user_liked ? 'btn-danger' : 'btn-outline-danger' ?>">
                    ❤️ <span id="like-count"><?= $likes_count ?></span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
            <form id="delete-post-form" method="POST" class="mt-3">
                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                <button type="button" class="btn btn-danger" onclick="confirmDeletePost()">Видалити пост</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <h4>📝 Залишити коментар</h4>
        <?php if (isset($_SESSION['user_id'])): ?>
            <form id="comment-form" enctype="multipart/form-data" class="mb-4">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                <input type="hidden" name="parent_id" id="parent_id" value="">
                <textarea name="content" class="form-control" placeholder="Ваш коментар..." required></textarea>
                <input type="file" name="image" accept="image/*" class="form-control mt-2">
                <button type="submit" class="btn btn-black mt-3">Надіслати</button>
            </form>
        <?php else: ?>
            <p class="text-muted">Щоб залишити коментар, <a href="login.php" class="text-decoration-none text-pink">увійдіть</a>.</p>
        <?php endif; ?>
    </div>

    <div id="comments-container">
        <!-- Коментарі будуть завантажені тут AJAX -->
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function () {

    // AJAX лайк
    $('#like-btn').click(function () {
        $.post('like_post.php', { post_id: <?= $post_id ?> }, function (data) {
            if (data.success) {
                $('#like-count').text(data.likes);
                $('#like-btn').toggleClass('btn-outline-danger btn-danger');
            } else {
                Swal.fire('Помилка', data.message, 'error');
            }
        }, 'json');
    });

    // Завантажити коментарі
    function loadComments() {
        $.get('comments_render.php', { post_id: <?= $post_id ?> }, function (data) {
            $('#comments-container').html(data);
        });
    }

    loadComments();

    // Додати коментар
    $('#comment-form').submit(function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        $.ajax({
            url: 'comment_actions.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#comment-form')[0].reset();
                    $('#parent_id').val('');
                    loadComments();
                } else {
                    Swal.fire('Помилка', res.message, 'error');
                }
            }
        });
    });

    // Видалення поста
    window.confirmDeletePost = function () {
        Swal.fire({
            title: 'Ви впевнені?',
            text: 'Цей пост буде видалено назавжди!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Так, видалити',
            cancelButtonText: 'Скасувати'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('delete_post.php', { post_id: <?= $post_id ?> }, function (res) {
                    if (res.success) {
                        Swal.fire('Готово', 'Пост видалено!', 'success').then(() => {
                            window.location.href = 'index.php';
                        });
                    } else {
                        Swal.fire('Помилка', res.message, 'error');
                    }
                }, 'json');
            }
        });
    };

    // Відповідь на коментар
    window.setReply = function (commentId) {
        $('#parent_id').val(commentId);
        $('html, body').animate({
            scrollTop: $('#comment-form').offset().top
        }, 600);
    };
});
</script>
</body>
</html>
