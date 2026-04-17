<?php
session_start();
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/user_queries.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'Login required']);
    exit;
}

$commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
if (!$commentId) {
    echo json_encode(['ok' => false, 'error' => 'Invalid comment id']);
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
$deleted = deleteCommentForUser($dbconn, $currentUserId, (int) $commentId);

if (!$deleted) {
    echo json_encode(['ok' => false, 'error' => 'You can only delete your own comments']);
    exit;
}

echo json_encode(['ok' => true]);
