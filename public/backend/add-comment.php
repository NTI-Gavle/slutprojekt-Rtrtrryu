<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../database/user_queries.php';
header('Content-Type: application/json');

try {
  if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'error'=>'Login required']); exit; }

  $currentUserId = (int) $_SESSION['user_id'];
  $isAdmin = userHasAdminAccess($dbconn, $currentUserId);

  $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
  $body = trim($_POST['body'] ?? '');
  if (!$postId || $body === '') { echo json_encode(['ok'=>false,'error'=>'Invalid input']); exit; }

  $stmt = $dbconn->prepare("INSERT INTO comments (post_id, author_id, body, created_at) VALUES (?, ?, ?, NOW())");
  $stmt->execute([$postId, $currentUserId, $body]);

  $id = (int)$dbconn->lastInsertId();
  $comment = fetchCommentById($dbconn, $id);
  if ($comment === null) {
    echo json_encode(['ok'=>false,'error'=>'Could not load new comment']);
    exit;
  }

  $comment['can_delete'] = $isAdmin || ((int) ($comment['author_id'] ?? 0) === $currentUserId);

  echo json_encode(['ok'=>true,'comment'=>$comment]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}

