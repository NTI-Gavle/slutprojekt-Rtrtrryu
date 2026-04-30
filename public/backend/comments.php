<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../database/user_queries.php';
header('Content-Type: application/json');

$postId = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit = max(1, min(20, (int)($_GET['limit'] ?? 5)));

if (!$postId) { echo json_encode(['ok' => false, 'error' => 'Invalid post_id']); exit; }

$comments = fetchCommentsForPost($dbconn, (int) $postId, $limit, $offset);
$currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$isAdmin = $currentUserId > 0 ? userHasAdminAccess($dbconn, $currentUserId) : false;

foreach ($comments as &$comment) {
    $comment['can_delete'] = $isAdmin || ($currentUserId > 0 && (int) ($comment['author_id'] ?? 0) === $currentUserId);
}
unset($comment);

$count = $dbconn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
$count->execute([(int)$postId]);
$total = (int)$count->fetchColumn();

echo json_encode([
  'ok' => true,
  'comments' => $comments,
  'hasMore' => ($offset + count($comments)) < $total
]);

