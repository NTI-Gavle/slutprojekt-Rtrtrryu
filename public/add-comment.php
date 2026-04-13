<?php
session_start();
require_once __DIR__ . '/../database/db.php';
header('Content-Type: application/json');

try {
  if (!isset($_SESSION['user_id'])) { echo json_encode(['ok'=>false,'error'=>'Login required']); exit; }

  $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
  $body = trim($_POST['body'] ?? '');
  if (!$postId || $body === '') { echo json_encode(['ok'=>false,'error'=>'Invalid input']); exit; }

  $stmt = $dbconn->prepare("INSERT INTO comments (post_id, author_id, body, created_at) VALUES (?, ?, ?, NOW())");
  $stmt->execute([$postId, (int)$_SESSION['user_id'], $body]);

  $id = (int)$dbconn->lastInsertId();
  $q = $dbconn->prepare("SELECT c.id, c.body, c.created_at, u.namn AS username, u.Pfp FROM comments c JOIN `användare` u ON u.id=c.author_id WHERE c.id=? LIMIT 1");
  $q->execute([$id]);

  echo json_encode(['ok'=>true,'comment'=>$q->fetch(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
