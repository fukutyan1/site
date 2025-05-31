<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$senderId = $_SESSION['user_id'];
$receiverId = intval($_POST['to'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($receiverId <= 0 || empty($message)) {
    die("Неправильні дані.");
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$senderId, $receiverId, $message]);

    // ❌ Раніше було перенаправлення на user_profile.php
    // ✅ Тепер перенаправляємо назад до чату
    header("Location: chat.php?to=" . $receiverId);
    exit;

} catch (PDOException $e) {
    die("Помилка бази даних: " . htmlspecialchars($e->getMessage()));
}
