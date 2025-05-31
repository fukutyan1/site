<?php
session_start();
require_once 'includes/db.php';

if (!isset($_POST['post_id']) || !isset($_SESSION['user_id'])) {
    exit("Недостатньо прав");
}

$post_id = intval($_POST['post_id']);
$user_id = $_SESSION['user_id'];

// Перевірка власника поста
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = :post_id");
$stmt->execute([':post_id' => $post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    exit("Пост не знайдено");
}

if ($post['user_id'] != $user_id) {
    exit("У вас немає прав на видалення цього поста");
}

// Видалення зображень з файлової системи
$images_stmt = $pdo->prepare("SELECT image_path FROM post_images WHERE post_id = :post_id");
$images_stmt->execute([':post_id' => $post_id]);
$images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($images as $img) {
    $file_path = __DIR__ . "/uploads/posts/" . $img['image_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Видаляємо зображення поста з БД
$del_images_stmt = $pdo->prepare("DELETE FROM post_images WHERE post_id = :post_id");
$del_images_stmt->execute([':post_id' => $post_id]);

// Видаляємо коментарі до поста
$del_comments_stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = :post_id");
$del_comments_stmt->execute([':post_id' => $post_id]);

// Видаляємо сам пост
$del_post_stmt = $pdo->prepare("DELETE FROM posts WHERE id = :post_id");
$del_post_stmt->execute([':post_id' => $post_id]);

echo "OK";
?>
