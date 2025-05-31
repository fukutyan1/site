<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'NOT_LOGGED_IN']);
    exit;
}

if (!isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing post_id']);
    exit;
}

$post_id = intval($_POST['post_id']);
$user_id = $_SESSION['user_id'];

// Перевіряємо, чи вже лайкав
$stmt = $pdo->prepare("SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$post_id, $user_id]);
$already_liked = $stmt->fetchColumn();

if ($already_liked) {
    // Видаляємо лайк
    $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
} else {
    // Додаємо лайк
    $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
    $stmt->execute([$post_id, $user_id]);
}

// Повертаємо актуальну кількість лайків
$stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
$stmt->execute([$post_id]);
$likes = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'likes' => $likes
]);
