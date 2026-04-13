<?php
require_once __DIR__ . '/../database/db.php';
header('Content-Type: application/json');

$postId = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit = 5;

if (!$postId) { echo json_encode(['ok'=>false,'error'=>'Invalid post_id']); exit; }

$stmt = $dbconn->prepare("
  SELECT c.id, c.body, c.created_at, u.namn AS username, u.Pfp
  FROM comments c
  JOIN `användare` u ON u.id = c.author_id
  WHERE c.post_id = ?
  ORDER BY c.created_at DESC
  LIMIT ? OFFSET ?
");
$stmt->bindValue(1, (int)$postId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = $dbconn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
$count->execute([(int)$postId]);
$total = (int)$count->fetchColumn();

echo json_encode([
  'ok' => true,
  'comments' => $comments,
  'hasMore' => ($offset + count($comments)) < $total
]);
