<?php
session_start();
require_once 'includes/db.php'; // повертає $pdo

if (!isset($_SESSION['user_id'])) {
    die("Ви повинні увійти.");
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $post_id = intval($_POST['post_id']);
    $content = trim($_POST['content'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    if ($content === '') {
        die("Коментар не може бути порожнім.");
    }

    // Перевірка існування parent_id
    if ($parent_id !== null) {
        $check = $pdo->prepare("SELECT id FROM comments WHERE id = :parent_id");
        $check->execute([':parent_id' => $parent_id]);
        if ($check->rowCount() === 0) {
            die("Батьківський коментар не знайдено.");
        }
    }

    // Обробка зображення
    $image_path = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/comments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $tmp_name = $_FILES['image']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            die("Недопустимий формат зображення.");
        }
        $filename = uniqid() . '.' . $ext;
        move_uploaded_file($tmp_name, $upload_dir . $filename);
        $image_path = $filename;
    }

    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, parent_id, image_path, created_at) 
                           VALUES (:post_id, :user_id, :content, :parent_id, :image_path, NOW())");
    $stmt->execute([
        ':post_id' => $post_id,
        ':user_id' => $user_id,
        ':content' => $content,
        ':parent_id' => $parent_id,
        ':image_path' => $image_path
    ]);

    echo "OK";
    exit();

} elseif ($action === 'edit') {
    $comment_id = intval($_POST['comment_id']);
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
        die("Коментар не може бути порожнім.");
    }

    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = :comment_id");
    $stmt->execute([':comment_id' => $comment_id]);
    $comment = $stmt->fetch();

    if (!$comment) die("Коментар не знайдено.");
    if ($comment['user_id'] != $user_id) die("Ви не можете редагувати цей коментар.");

    $update = $pdo->prepare("UPDATE comments SET content = :content, updated_at = NOW() WHERE id = :comment_id");
    $update->execute([
        ':content' => $content,
        ':comment_id' => $comment_id
    ]);

    echo "OK";
    exit();

} elseif ($action === 'delete') {
    $comment_id = intval($_POST['comment_id']);

    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = :comment_id");
    $stmt->execute([':comment_id' => $comment_id]);
    $comment = $stmt->fetch();

    if (!$comment) die("Коментар не знайдено.");
    if ($comment['user_id'] != $user_id) die("Ви не можете видалити цей коментар.");

    function delete_comment_recursive($pdo, $comment_id) {
        // Отримати дочірні коментарі
        $stmt = $pdo->prepare("SELECT id FROM comments WHERE parent_id = :parent_id");
        $stmt->execute([':parent_id' => $comment_id]);
        $children = $stmt->fetchAll();

        // Рекурсивне видалення
        foreach ($children as $child) {
            delete_comment_recursive($pdo, $child['id']);
        }

        // Видалити сам коментар
        $pdo->prepare("DELETE FROM comments WHERE id = :comment_id")
            ->execute([':comment_id' => $comment_id]);
    }

    delete_comment_recursive($pdo, $comment_id);

    echo "OK";
    exit();
}

die("Невідома дія.");
