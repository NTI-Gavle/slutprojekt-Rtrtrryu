<?php
declare(strict_types=1);

$dbConfig = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'RULER_89',
    'username' => 'root',
    'password' => 'nooo',
];

function firstValue(array $row, array $keys, mixed $default = ''): mixed
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
            return $row[$key];
        }
    }

    return $default;
}

function buildDsn(array $config): string
{
    return sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['dbname']
    );
}

$postId = isset($_GET['id']) ? max(1, (int) $_GET['id']) : null;
$comments = [];
$errorMessage = null;
$postRow = null;

try {
    $pdo = new PDO(
        buildDsn($dbConfig),
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    if ($postId !== null) {
        $statement = $pdo->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $postId]);
    } else {
        $statement = $pdo->query('SELECT * FROM posts ORDER BY id DESC LIMIT 1');
    }

    $postRow = $statement->fetch() ?: null;

    if ($postRow && isset($postRow['id'])) {
        $possibleCommentTables = ['comments', 'post_comments'];

        foreach ($possibleCommentTables as $tableName) {
            try {
                $commentStatement = $pdo->prepare(
                    "SELECT * FROM {$tableName} WHERE post_id = :post_id ORDER BY id ASC"
                );
                $commentStatement->execute(['post_id' => (int) $postRow['id']]);
                $comments = $commentStatement->fetchAll();
                break;
            } catch (PDOException) {
                $comments = [];
            }
        }
    }
} catch (PDOException $exception) {
    $errorMessage = $exception->getMessage();
}

if (!$postRow) {
    $postRow = [
        'id' => 0,
        'title' => 'No post found',
        'body' => 'Check your database connection details or pass ?id=POST_ID in the URL.',
        'media' => '',
        'image_url' => '',
        'creator_id' => null,
        'created_at' => null,
    ];
}

$post = [
    'id' => (int) firstValue($postRow, ['id'], 0),
    'poster' => (string) firstValue($postRow, ['creator_name', 'creator_username', 'username'], 'Poster'),
    'creator_id' => firstValue($postRow, ['creator_id', 'creatorid', 'user_id'], null),
    'title' => (string) firstValue($postRow, ['title'], 'Untitled post'),
    'content' => (string) firstValue($postRow, ['body', 'content', 'description'], ''),
    'image' => (string) firstValue($postRow, ['image_url', 'image', 'media'], ''),
    'created_at' => (string) firstValue($postRow, ['created_at', 'created_on', 'created'], ''),
    'adultcheck' => (int) firstValue($postRow, ['adultcheck', 'adult_check'], 0),
];

if ($post['poster'] === 'Poster' && $post['creator_id'] !== null) {
    $post['poster'] = 'User ' . $post['creator_id'];
}

$normalizedComments = [];

foreach ($comments as $commentRow) {
    $normalizedComments[] = [
        'author' => (string) firstValue($commentRow, ['author', 'username', 'creator_name'], 'User'),
        'text' => (string) firstValue($commentRow, ['body', 'comment', 'content'], ''),
    ];
}

$commentsPerPage = 5;
$totalComments = count($normalizedComments);
$totalPages = max(1, (int) ceil($totalComments / $commentsPerPage));
$page = isset($_GET['comments_page']) ? (int) $_GET['comments_page'] : 1;
$page = max(1, min($totalPages, $page));
$offset = ($page - 1) * $commentsPerPage;
$visibleComments = array_slice($normalizedComments, $offset, $commentsPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?></title>
    <link rel="stylesheet" href="Test.css">
</head>
    <main class="page">
        <article class="post-card">
            <div class="poster-badge"><?= htmlspecialchars($post['poster']) ?></div>

            <header class="post-title">
                <h1><?= htmlspecialchars($post['title']) ?></h1>
            </header>

            <section class="post-body">
                <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>

                <?php if ($post['image'] !== ''): ?>
                    <figure class="post-image">
                        <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post content image">
                    </figure>
                <?php endif; ?>
            </section>

            <section class="post-footer">
                <button class="like-button" type="button" aria-label="Like post">
                    <span class="heart-shape"></span>
                </button>

                <div class="comments-shell">
                    <div class="comments-panel">
                        <h2>Comments</h2>

                        <?php if ($errorMessage !== null): ?>
                            <div class="comment-item">
                                <strong>Database connection failed</strong>
                                <p><?= htmlspecialchars($errorMessage) ?></p>
                            </div>
                        <?php elseif ($totalComments === 0): ?>
                            <div class="comment-item">
                                <strong>No comments yet</strong>
                                <p>No related comments table was found, or this post does not have comments yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="comment-list">
                                <?php foreach ($visibleComments as $comment): ?>
                                    <article class="comment-item">
                                        <strong><?= htmlspecialchars($comment['author']) ?></strong>
                                        <p><?= htmlspecialchars($comment['text']) ?></p>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($totalComments > $commentsPerPage): ?>
                        <nav class="comment-tabs" aria-label="Comment pages">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a
                                    class="comment-tab<?= $i === $page ? ' is-active' : '' ?>"
                                    href="?id=<?= $post['id'] ?>&comments_page=<?= $i ?>"
                                    aria-label="Load comments <?= (($i - 1) * $commentsPerPage) + 1 ?> to <?= min($i * $commentsPerPage, $totalComments) ?>"
                                ></a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                </div>

                <div class="scroll-rail" aria-hidden="true">
                    <span class="scroll-thumb"></span>
                </div>
            </section>
        </article>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
