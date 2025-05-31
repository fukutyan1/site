<?php
session_start();
require_once 'includes/db.php';

$currentUserId = $_SESSION['user_id'] ?? null;
$query = $_GET['q'] ?? '';

if (!$currentUserId || strlen($query) < 2) {
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE username LIKE ? AND id != ? LIMIT 10");
$stmt->execute(['%' . $query . '%', $currentUserId]);
$users = $stmt->fetchAll();

foreach ($users as $user):
    $avatar = $user['avatar'] ?: 'default.png';
?>
    <a href="user_profile.php?id=<?= $user['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center">
        <img src="uploads/avatars/<?= htmlspecialchars($avatar) ?>" class="rounded-circle me-2" width="40" height="40" alt="Аватар">
        <?= htmlspecialchars($user['username']) ?>
    </a>
<?php endforeach; ?>
