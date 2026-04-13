<?php
session_start();
include('../../database/db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Login required']);
    exit;
}

$postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$body = trim($_POST['body'] ?? '');

if (!$postId || $body === '') {
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

// If your created column has no default, keep NOW() column in INSERT.
// Replace `created_at` with your real created column name.
$stmt = $dbconn->prepare("
    INSERT INTO comments (post_id, author_id, body, `created_at`)
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([$postId, (int)$_SESSION['user_id'], $body]);

$id = (int)$dbconn->lastInsertId();

$fetch = $dbconn->prepare("
    SELECT c.id, c.body, c.`created_at` AS created, u.namn AS username, u.Pfp
    FROM comments c
    JOIN `användare` u ON u.id = c.author_id
    WHERE c.id = ?
    LIMIT 1
");
$fetch->execute([$id]);
$comment = $fetch->fetch(PDO::FETCH_ASSOC);

echo json_encode(['ok' => true, 'comment' => $comment]);
