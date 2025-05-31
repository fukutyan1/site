<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = intval($_POST['message_id']);
    $user_id = $_SESSION['user_id'];

    // Перевірка, чи це повідомлення справді належить цьому користувачу
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ? AND sender_id = ?");
    $stmt->execute([$message_id, $user_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($message) {
        // Видалити повідомлення
        $deleteStmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $deleteStmt->execute([$message_id]);

        // Повернути назад у чат
        header("Location: chat.php?to=" . $message['receiver_id']);
        exit;
    } else {
        // Повідомлення не знайдене або не ваше
        header("Location: index.php");
        exit;
    }
} else {
    // Невірний запит
    header("Location: index.php");
    exit;
}
