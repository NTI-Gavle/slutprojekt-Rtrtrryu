<?php
// /public/api/comments.php
include('../../database/db.php');
header('Content-Type: application/json');

$postId = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
$offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT);

$offset = max(0, (int)$offset);
$limit = ($limit && $limit > 0 && $limit <= 20) ? (int)$limit : 5;

if (!$postId) {
    echo json_encode(['ok' => false, 'error' => 'Invalid post_id']);
    exit;
}

// NOTE: JOIN is on c.author_id
$stmt = $dbconn->prepare("
    SELECT c.id, c.body, c.created, u.namn AS username, u.Pfp
    FROM comments c
    JOIN `användare` u ON u.id = c.author_id
    WHERE c.post_id = ?
    ORDER BY c.created DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, (int)$postId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $dbconn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
$countStmt->execute([(int)$postId]);
$total = (int)$countStmt->fetchColumn();

$hasMore = ($offset + count($comments)) < $total;

echo json_encode([
    'ok' => true,
    'comments' => $comments,
    'hasMore' => $hasMore
]);
