<?php
session_start();
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$post_id = intval($_GET['post_id'] ?? 0);

function render_comments($parent_id, PDO $pdo, $post_id, $user_id) {
    $parent_condition = is_null($parent_id) ? "IS NULL" : "= :parent_id";

    $sql = "SELECT c.*, u.username, u.avatar 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = :post_id AND c.parent_id $parent_condition 
            ORDER BY c.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':post_id', $post_id, PDO::PARAM_INT);
    if (!is_null($parent_id)) {
        $stmt->bindValue(':parent_id', $parent_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($comments as $comment):
        $comment_id = $comment['id'];
        ?>
        <div class="comment-box" style="margin-left: <?= is_null($parent_id) ? '0' : '40px' ?>;">
            <div class="d-flex align-items-center mb-2">
                <img src="uploads/avatars/<?= htmlspecialchars($comment['avatar']) ?>" alt="Аватар" width="40" height="40" class="rounded-circle me-2 border border-2 border-pink">
                <strong><?= htmlspecialchars($comment['username']) ?></strong>
                <small class="text-muted ms-3" style="font-size: 0.85rem;">
                    <?= htmlspecialchars($comment['updated_at'] ?? $comment['created_at']) ?>
                    <?php if (!empty($comment['updated_at'])): ?>
                        (редаговано)
                    <?php endif; ?>
                </small>
            </div>
            <p style="white-space: pre-line;"><?= htmlspecialchars($comment['content']) ?></p>
            <?php if (!empty($comment['image_path'])): ?>
                <img src="uploads/comments/<?= htmlspecialchars($comment['image_path']) ?>" alt="Зображення коментаря" class="comment-image-preview">
            <?php endif; ?>
            <div class="comment-actions">
                <?php if ($user_id): ?>
                    <span class="comment-reply-btn" onclick="openReplyForm(<?= $comment_id ?>)">↩️ Відповісти</span>
                    <?php if ($comment['user_id'] == $user_id): ?>
                        <span class="comment-reply-btn" onclick='openEditForm(<?= $comment_id ?>, <?= json_encode($comment['content'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>✏️ Редагувати</span>
                        <span class="comment-reply-btn" onclick="deleteComment(<?= $comment_id ?>)">🗑️ Видалити</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="form-container" id="form-container-<?= $comment_id ?>"></div>
        </div>
        <?php
        render_comments($comment_id, $pdo, $post_id, $user_id);
    endforeach;
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Коментарі до поста</title>
    <style>
        .reply-form { margin-top: 10px; }
        .reply-form textarea { width: 100%; height: 60px; border-radius: 8px; padding: 8px; border: 1px solid #ff69b4; }
        .comment-reply-btn {
            color: #ff69b4;
            cursor: pointer;
            margin-right: 10px;
            user-select: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .comment-reply-btn:hover {
            color: #d5007d;
        }
        .comment-image-preview {
            max-width: 200px;
            max-height: 150px;
            margin-top: 10px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(255, 105, 180, 0.3);
        }
        .comment-box {
            border-bottom: 1px solid #f9c2e5;
            padding-bottom: 10px;
            margin-bottom: 10px;
            background: #fff0f6;
            border-radius: 12px;
            padding: 12px;
        }
        button {
            margin-top: 5px;
            margin-right: 5px;
            background-color: #000;
            color: #fff;
            border-radius: 12px;
            border: none;
            padding: 6px 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #333;
        }
    </style>
</head>
<body>

<h2>Коментарі</h2>

<div id="comments-container">
    <?php render_comments(null, $pdo, $post_id, $user_id); ?>
</div>

<script>
const postId = <?= json_encode($post_id) ?>;
const isLoggedIn = <?= $user_id ? 'true' : 'false' ?>;

function closeAllForms() {
    document.querySelectorAll('.form-container').forEach(container => container.innerHTML = '');
}

function openReplyForm(commentId) {
    if (!isLoggedIn) {
        alert("Потрібно увійти, щоб відповісти.");
        return;
    }

    closeAllForms();

    const container = document.getElementById('form-container-' + commentId);
    if (!container) return;

    container.innerHTML = `
        <form class="reply-form" enctype="multipart/form-data" onsubmit="submitReply(event, ${commentId})">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="post_id" value="${postId}">
            <input type="hidden" name="parent_id" value="${commentId}">
            <textarea name="content" required placeholder="Ваша відповідь..." style="width:100%; height:60px; border-radius:8px; padding:8px; border:1px solid #ff69b4;"></textarea><br>
            <input type="file" name="image" accept="image/*"><br>
            <button type="submit">Відправити</button>
            <button type="button" onclick="closeAllForms()">Скасувати</button>
        </form>
    `;

    container.querySelector('textarea[name="content"]').focus();
    container.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function openEditForm(commentId, currentContent) {
    if (!isLoggedIn) return alert("Потрібно увійти, щоб редагувати.");

    closeAllForms();
    const container = document.getElementById('form-container-' + commentId);
    if (!container) return;

    const safeContent = currentContent.replace(/</g, "&lt;").replace(/>/g, "&gt;");

    container.innerHTML = `
        <form class="reply-form" onsubmit="submitEdit(event, ${commentId})">
            <input type="hidden" name="action" value="edit">
            <textarea name="content" required>${safeContent}</textarea><br>
            <button type="submit">Зберегти</button>
            <button type="button" onclick="closeAllForms()">Скасувати</button>
        </form>
    `;
}

function addCommentReply(PDO $pdo, int $postId, int $parentId, int $userId, string $content): bool {
    // Валідація (можна розширити)
    if (empty(trim($content))) {
        return false;
    }

    $sql = "INSERT INTO comments (post_id, parent_id, user_id, content) 
            VALUES (:post_id, :parent_id, :user_id, :content)";

    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':post_id' => $postId,
        ':parent_id' => $parentId,
        ':user_id' => $userId,
        ':content' => $content
    ]);
}


function submitEdit(event, commentId) {
    event.preventDefault();
    const form = event.target;
    const content = form.querySelector('textarea[name="content"]').value.trim();

    if (content === '') {
        alert('Коментар не може бути порожнім.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'edit');
    formData.append('comment_id', commentId);
    formData.append('content', content);

    fetch('comment_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(result => {
        if (result === 'OK') {
            location.reload();
        } else {
            alert('Помилка: ' + result);
        }
    })
    .catch(() => alert('Помилка при відправці.'));
}

function deleteComment(commentId) {
    if (!confirm('Ви впевнені, що хочете видалити цей коментар та всі його відповіді?')) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('comment_id', commentId);

    fetch('comment_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(result => {
        if (result === 'OK') {
            location.reload();
        } else {
            alert('Помилка: ' + result);
        }
    })
    .catch(() => alert('Помилка при видаленні.'));
}
</script>

</body>
</html>
