<?php
$post = [
    'id' => 482913,
    'title' => 'Parody Post View',
    'rating' => 'safe',
    'score' => 128,
    'favorites' => 46,
    'status' => 'active',
    'source' => 'https://example.com/mock-source',
    'uploaded_by' => 'mock_admin',
    'posted_at' => '2026-03-27 10:12',
    'dimensions' => '1600x900',
    'file_size' => '1.8 MB',
    'file_type' => 'JPG',
    'tags' => [
        'copyright' => ['parody_series', 'retro_booru'],
        'character' => ['sample_mascot', 'comment_sprite'],
        'artist' => ['demo_artist'],
        'meta' => ['highres', 'mockup', 'ui_parody', 'tag_sidebar', 'comment_thread'],
        'general' => ['blue_theme', 'desktop_layout', 'post_page', 'php_demo', 'vintage_web'],
    ],
    'poster' => 'Poster',
    'title' => 'Title',
    'content' => 'Content- Text first then Picture after if it has one',
    'image' => 'https://picsum.photos/900/420',
    'liked' => false,
    'comments' => [
        [
            'author' => 'frontendfan',
            'time' => '2026-03-27 10:18',
            'body' => 'This captures the old-school post page feel really well. The tag rail and metadata blocks read instantly.',
        ],
        [
            'author' => 'stacktrace',
            'time' => '2026-03-27 10:23',
            'body' => 'Nice parody direction. If you want it even closer, you could swap in your own thumbnail strip and pagination rows later.',
        ],
        [
            'author' => 'lemmy',
            'time' => '2026-03-27 10:31',
            'body' => 'Perfect as a starting point. I can wire this up to a database once the visuals are in place.',
        ],
        ['author' => 'Alex', 'text' => 'First comment on the post.'],
        ['author' => 'Mika', 'text' => 'This layout feels very clean.'],
        ['author' => 'Sam', 'text' => 'I like the hand-drawn wireframe look.'],
        ['author' => 'Nora', 'text' => 'The title and content spacing looks good.'],
        ['author' => 'June', 'text' => 'This should be the fifth visible comment.'],
        ['author' => 'Elliot', 'text' => 'This comment appears on the next square tab.'],
        ['author' => 'Robin', 'text' => 'Loading 5 more comments works nicely here.'],
        ['author' => 'Chris', 'text' => 'PHP pagination makes this easy to keep simple.'],
        ['author' => 'Taylor', 'text' => 'The extra square only shows when needed.'],
        ['author' => 'Kim', 'text' => 'This is the tenth comment in the list.'],
        ['author' => 'Jules', 'text' => 'A third square appears once comments go past 10.'],
    ],
];

function renderTagGroup(string $label, array $tags): void
{
    echo '<section class="tag-group">';
    echo '<h3>' . htmlspecialchars($label) . '</h3>';
    echo '<ul>';

    foreach ($tags as $tag) {
        echo '<li><a href="?tags=' . rawurlencode($tag) . '">' . htmlspecialchars($tag) . '</a></li>';
    }

    echo '</ul>';
    echo '</section>';
}
$commentsPerPage = 5;
$totalComments = count($post['comments']);
$totalPages = (int) ceil($totalComments / $commentsPerPage);
$page = isset($_GET['comments_page']) ? (int) $_GET['comments_page'] : 1;
$page = max(1, min($totalPages > 0 ? $totalPages : 1, $page));
$offset = ($page - 1) * $commentsPerPage;
$visibleComments = array_slice($post['comments'], $offset, $commentsPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - Post #<?= (int) $post['id'] ?></title>
    <title><?= htmlspecialchars($post['title']) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="site-shell">
        <header class="topbar">
            <div class="brand">
                <span class="brand-mark">RB</span>
                <div>
                    <strong>RetroBooru</strong>
                    <span>parody archive interface</span>
                </div>
            </div>
    <main class="page">
        <article class="post-card">
            <div class="poster-badge"><?= htmlspecialchars($post['poster']) ?></div>

            <header class="post-title">
                <h1><?= htmlspecialchars($post['title']) ?></h1>
            </header>

            <nav class="topnav" aria-label="Primary">
                <a href="#">Posts</a>
                <a href="#">Comments</a>
                <a href="#">Tags</a>
                <a href="#">Artists</a>
                <a href="#">Wiki</a>
                <a href="#">Forum</a>
            </nav>
            <section class="post-body">
                <p><?= htmlspecialchars($post['content']) ?></p>

            <form class="searchbar" action="#" method="get">
                <input type="text" name="q" value="post_view parody" aria-label="Search tags">
                <button type="submit">Search</button>
            </form>
        </header>
                <?php if (!empty($post['image'])): ?>
                    <figure class="post-image">
                        <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post content image">
                    </figure>
                <?php endif; ?>
            </section>

        <main class="content-grid">
            <section class="post-column">
                <div class="notice-bar">
                    <span>Post #<?= (int) $post['id'] ?></span>
                    <span>Rating: <?= htmlspecialchars($post['rating']) ?></span>
                    <span>Status: <?= htmlspecialchars($post['status']) ?></span>
                </div>
            <section class="post-footer">
                <button class="like-button" type="button" aria-label="Like post">
                    <span class="heart-shape"></span>
                </button>

                <div class="post-actions">
                    <a href="#">Previous</a>
                    <a href="#">Next</a>
                    <a href="#">View larger</a>
                    <a href="#">Flag</a>
                    <a href="#">Favorite</a>
                </div>
                <div class="comments-shell">
                    <div class="comments-panel">
                        <h2>Comments</h2>

                <article class="post-card">
                    <div class="image-stage" aria-label="Sample post preview">
                        <div class="mock-image">
                            <div class="mock-chip">sample artwork</div>
                            <h1>Post Page Mockup</h1>
                            <p>Structured to feel like a classic booru comment-and-tag view.</p>
                        <div class="comment-list">
                            <?php foreach ($visibleComments as $index => $comment): ?>
                                <article class="comment-item">
                                    <strong><?= htmlspecialchars($comment['author']) ?></strong>
                                    <p><?= htmlspecialchars($comment['text']) ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <dl class="meta-grid">
                        <div>
                            <dt>Posted</dt>
                            <dd><?= htmlspecialchars($post['posted_at']) ?></dd>
                        </div>
                        <div>
                            <dt>Uploader</dt>
                            <dd><a href="#"><?= htmlspecialchars($post['uploaded_by']) ?></a></dd>
                        </div>
                        <div>
                            <dt>Score</dt>
                            <dd><?= (int) $post['score'] ?></dd>
                        </div>
                        <div>
                            <dt>Favorites</dt>
                            <dd><?= (int) $post['favorites'] ?></dd>
                        </div>
                        <div>
                            <dt>Size</dt>
                            <dd><?= htmlspecialchars($post['dimensions']) ?></dd>
                        </div>
                        <div>
                            <dt>Format</dt>
                            <dd><?= htmlspecialchars($post['file_type']) ?> / <?= htmlspecialchars($post['file_size']) ?></dd>
                        </div>
                        <div class="meta-wide">
                            <dt>Source</dt>
                            <dd><a href="<?= htmlspecialchars($post['source']) ?>"><?= htmlspecialchars($post['source']) ?></a></dd>
                        </div>
                    </dl>
                </article>

                <section class="comment-panel">
                    <header>
                        <h2>Comments</h2>
                        <span><?= count($post['comments']) ?> total</span>
                    </header>

                    <?php foreach ($post['comments'] as $index => $comment): ?>
                        <article class="comment">
                            <div class="comment-head">
                                <strong>#<?= $index + 1 ?> <?= htmlspecialchars($comment['author']) ?></strong>
                                <time datetime="<?= htmlspecialchars($comment['time']) ?>"><?= htmlspecialchars($comment['time']) ?></time>
                            </div>
                            <p><?= htmlspecialchars($comment['body']) ?></p>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($totalComments > $commentsPerPage): ?>
                        <nav class="comment-tabs" aria-label="Comment pages">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a
                                    class="comment-tab<?= $i === $page ? ' is-active' : '' ?>"
                                    href="?comments_page=<?= $i ?>"
                                    aria-label="Load comments <?= (($i - 1) * $commentsPerPage) + 1 ?> to <?= min($i * $commentsPerPage, $totalComments) ?>"
                                ></a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                </div>

                    <form class="comment-form" action="#" method="post">
                        <label for="comment">Add comment</label>
                        <textarea id="comment" name="comment" rows="4" placeholder="Write a comment..."></textarea>
                        <div class="comment-form-actions">
                            <button type="submit">Post</button>
                            <span>HTML disabled. Be nice.</span>
                        </div>
                    </form>
                </section>
                <div class="scroll-rail" aria-hidden="true">
                    <span class="scroll-thumb"></span>
                </div>
            </section>

            <aside class="sidebar">
                <section class="sidebar-panel">
                    <h2>Tags</h2>
                    <?php foreach ($post['tags'] as $group => $tags): ?>
                        <?php renderTagGroup($group, $tags); ?>
                    <?php endforeach; ?>
                </section>

                <section class="sidebar-panel stats-panel">
                    <h2>Stats</h2>
                    <ul>
                        <li><span>ID</span><strong>#<?= (int) $post['id'] ?></strong></li>
                        <li><span>Score</span><strong><?= (int) $post['score'] ?></strong></li>
                        <li><span>Favorites</span><strong><?= (int) $post['favorites'] ?></strong></li>
                        <li><span>Comments</span><strong><?= count($post['comments']) ?></strong></li>
                    </ul>
                </section>

                <section class="sidebar-panel">
                    <h2>Pool / Relations</h2>
                    <p>This area is ready for parent-child links, collections, and related posts.</p>
                </section>
            </aside>
        </main>
    </div>
        </article>
    </main>
</body>
</html>