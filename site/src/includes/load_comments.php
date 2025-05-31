<?php
// $post_id передається із main файлу
$user_id = $_SESSION['user_id'] ?? 0;

// Функція для рекурсивного виведення коментарів з відповідями
function render_comments($parent_id, $conn, $user_id) {
    $sql = $parent_id === null
        ? "SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = $GLOBALS[post_id] AND c.parent_id IS NULL ORDER BY c.created_at DESC"
        : "SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id WHERE c.parent_id = $parent_id ORDER BY c.created_at ASC";

    $result = $conn->query($sql);
    if (!$result) return;

    while ($comment = $result->fetch_assoc()) {
        $comment_id = $comment['id'];

        // Кількість лайків коментаря
        $comment_likes_count = $conn->query("SELECT COUNT(*) AS cnt FROM comment_likes WHERE comment_id = $comment_id")->fetch_assoc()['cnt'];

        // Чи лайкнув користувач цей коментар
        $user_liked_comment = 0;
        if ($user_id) {
            $res = $conn->query("SELECT id FROM comment_likes WHERE comment_id = $comment_id AND user_id = $user_id");
            $user_liked_comment = $res->num_rows > 0 ? 1 : 0;
        }

        ?>
        <div class="comment-box" style="margin-left: <?= $parent_id === null ? '0' : '40px' ?>;">
            <div class="d-flex align-items-center mb-2">
                <img src="uploads/avatars/<?= htmlspecialchars($comment['avatar']) ?>" alt="Аватар" width="40" height="40" class="rounded-circle me-2 border border-2 border-pink">
                <strong><?= htmlspecialchars($comment['username']) ?></strong>
                <small class="text-muted ms-3"><?= htmlspecialchars($comment['created_at']) ?></small>
            </div>
            <p style="white-space: pre-line;"><?= htmlspecialchars($comment['content']) ?></p>
            <?php if (!empty($comment['image'])): ?>
                <img src="uploads/comments/<?= htmlspecialchars($comment['image']) ?>" alt="Зображення коментаря" class="comment-image-preview">
            <?php endif; ?>
            <div class="comment-actions">
                <?php if ($user_id): ?>
                    <span onclick="toggleCommentLike(<?= $comment_id ?>)" style="color: <?= $user_liked_comment ? 'red' : '#ff69b4' ?>">
                        ❤️ <span id="comment-likes-count-<?= $comment_id ?>"><?= $comment_likes_count ?></span>
                    </span>
                    <span class="comment-reply-btn" onclick="replyTo(<?= $comment_id ?>)">Відповісти</span>
                    <?php if ($comment['user_id'] == $user_id): ?>
                        <span class="comment-reply-btn" onclick="editComment(<?= $comment_id ?>, <?= json_encode($comment['content']) ?>)">Редагувати</span>
                        <span class="comment-reply-btn" onclick="deleteComment(<?= $comment_id ?>)">Видалити</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php

        // Виводимо відповіді рекурсивно
        render_comments($comment_id, $conn, $user_id);
    }
}

render_comments(null, $conn, $user_id);
?>
