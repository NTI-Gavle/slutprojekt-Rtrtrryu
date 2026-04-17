<?php
require_once __DIR__ . '/db.php';

function normalizeIdentifier(string $value): string
{
    $value = strtolower($value);
    return preg_replace('/[^a-z0-9]/', '', $value) ?? '';
}

function resolveTableName(PDO $dbconn, array $candidates): ?string
{
    $tables = $dbconn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (!$tables) {
        return null;
    }

    $normalizedTableMap = [];
    foreach ($tables as $tableName) {
        $normalizedTableMap[normalizeIdentifier((string) $tableName)] = (string) $tableName;
    }

    foreach ($candidates as $candidate) {
        $key = normalizeIdentifier($candidate);
        if (isset($normalizedTableMap[$key])) {
            return $normalizedTableMap[$key];
        }
    }

    return null;
}

function getTableColumns(PDO $dbconn, string $tableName): array
{
    $stmt = $dbconn->query("SHOW COLUMNS FROM `{$tableName}`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $columns = [];
    foreach ($rows as $row) {
        if (isset($row['Field'])) {
            $columns[] = $row['Field'];
        }
    }

    return $columns;
}

function findColumn(array $columns, array $candidates): ?string
{
    $lookup = [];
    foreach ($columns as $column) {
        $lookup[normalizeIdentifier($column)] = $column;
    }

    foreach ($candidates as $candidate) {
        $key = normalizeIdentifier($candidate);
        if (isset($lookup[$key])) {
            return $lookup[$key];
        }
    }

    return null;
}

function firstNonEmptyValue(array $row, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (!array_key_exists($candidate, $row)) {
            continue;
        }

        $value = $row[$candidate];
        if ($value !== null && trim((string) $value) !== '') {
            return (string) $value;
        }
    }

    return null;
}

function resolveUserTable(PDO $dbconn): ?string
{
    return resolveTableName($dbconn, ['anvandare', 'anvndare', 'users', 'user']);
}

function getUserTableMeta(PDO $dbconn): ?array
{
    $userTable = resolveUserTable($dbconn);
    if ($userTable === null) {
        return null;
    }

    $columns = getTableColumns($dbconn, $userTable);
    $idColumn = findColumn($columns, ['id', 'user_id']);
    $nameColumn = findColumn($columns, ['namn', 'username', 'name']);
    $avatarColumn = findColumn($columns, ['avatar_path', 'pfp', 'pfp_path', 'profile_picture', 'profile_image', 'image_path']);

    if ($idColumn === null || $nameColumn === null) {
        return null;
    }

    return [
        'table' => $userTable,
        'id_column' => $idColumn,
        'name_column' => $nameColumn,
        'avatar_column' => $avatarColumn,
    ];
}

function getUserProfileData(PDO $dbconn, int $userId): ?array
{
    $userTable = resolveUserTable($dbconn);
    if ($userTable === null) {
        return null;
    }

    $columns = getTableColumns($dbconn, $userTable);
    $idColumn = findColumn($columns, ['id', 'user_id']);
    if ($idColumn === null) {
        return null;
    }

    $stmt = $dbconn->prepare("SELECT * FROM `{$userTable}` WHERE `{$idColumn}` = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    $username = firstNonEmptyValue($row, ['namn', 'username', 'name']) ?? 'Unknown user';
    $description = firstNonEmptyValue($row, ['profile_description', 'description', 'bio', 'beskrivning']) ?? '';
    $avatarPath = firstNonEmptyValue($row, ['avatar_path', 'pfp_path', 'profile_picture', 'profile_image', 'image_path', 'Pfp']);
    $backgroundPath = firstNonEmptyValue($row, ['background_path', 'banner_path', 'cover_path', 'header_image_path']);

    return [
        'user_id' => (int) ($row[$idColumn] ?? $userId),
        'username' => $username,
        'description' => $description,
        'avatar_path' => $avatarPath,
        'background_path' => $backgroundPath,
    ];
}

function getUserLikedPosts(PDO $dbconn, int $userId, int $limit = 6): array
{
    $limit = max(1, min(30, $limit));

    $likesTable = resolveTableName($dbconn, ['likes', 'user_likes']);
    $postsTable = resolveTableName($dbconn, ['posts', 'post']);
    $userTable = resolveUserTable($dbconn);

    if ($likesTable === null || $postsTable === null || $userTable === null) {
        return [];
    }

    $likesColumns = getTableColumns($dbconn, $likesTable);
    $postColumns = getTableColumns($dbconn, $postsTable);
    $userColumns = getTableColumns($dbconn, $userTable);

    $likesUserIdColumn = findColumn($likesColumns, ['user_id', 'creator_id']);
    $likesPostIdColumn = findColumn($likesColumns, ['post_id', 'liked_post_id']);
    $likesCreatedAtColumn = findColumn($likesColumns, ['created_at', 'liked_at']);

    $postIdColumn = findColumn($postColumns, ['id', 'post_id']);
    $postTitleColumn = findColumn($postColumns, ['title', 'rubrik']);
    $postBodyColumn = findColumn($postColumns, ['body', 'content', 'text']);
    $postCreatedAtColumn = findColumn($postColumns, ['created_at', 'created']);
    $postCreatorIdColumn = findColumn($postColumns, ['creator_id', 'user_id', 'author_id']);

    $authorIdColumn = findColumn($userColumns, ['id', 'user_id']);
    $authorNameColumn = findColumn($userColumns, ['namn', 'username', 'name']);

    if (
        $likesUserIdColumn === null ||
        $likesPostIdColumn === null ||
        $postIdColumn === null ||
        $postTitleColumn === null ||
        $postBodyColumn === null
    ) {
        return [];
    }

    $selectAuthor = "'' AS author_name";
    $joinAuthor = '';

    if ($postCreatorIdColumn !== null && $authorIdColumn !== null && $authorNameColumn !== null) {
        $selectAuthor = "author.`{$authorNameColumn}` AS author_name";
        $joinAuthor = "LEFT JOIN `{$userTable}` AS author ON author.`{$authorIdColumn}` = post.`{$postCreatorIdColumn}`";
    }

    if ($likesCreatedAtColumn !== null) {
        $orderBy = "like_map.`{$likesCreatedAtColumn}` DESC";
    } elseif ($postCreatedAtColumn !== null) {
        $orderBy = "post.`{$postCreatedAtColumn}` DESC";
    } else {
        $orderBy = "post.`{$postIdColumn}` DESC";
    }

    $sql = "
        SELECT
            post.`{$postIdColumn}` AS post_id,
            post.`{$postTitleColumn}` AS title,
            post.`{$postBodyColumn}` AS body,
            {$selectAuthor}
        FROM `{$likesTable}` AS like_map
        INNER JOIN `{$postsTable}` AS post ON post.`{$postIdColumn}` = like_map.`{$likesPostIdColumn}`
        {$joinAuthor}
        WHERE like_map.`{$likesUserIdColumn}` = ?
        ORDER BY {$orderBy}
        LIMIT {$limit}
    ";

    $stmt = $dbconn->prepare($sql);
    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function tryAddColumn(PDO $dbconn, string $tableName, string $columnName, string $definition): void
{
    $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$definition}";

    try {
        $dbconn->exec($sql);
    } catch (PDOException $e) {
        $message = (string) $e->getMessage();
        if (strpos($message, 'Duplicate column name') === false) {
            throw $e;
        }
    }
}

function ensureTextColumnUtf8mb4(PDO $dbconn, string $tableName, string $columnName): void
{
    $sql = "ALTER TABLE `{$tableName}` MODIFY `{$columnName}` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL";
    $dbconn->exec($sql);
}

function updateUserProfileData(PDO $dbconn, int $userId, string $description, string $avatarPath, string $backgroundPath): bool
{
    $userTable = resolveUserTable($dbconn);
    if ($userTable === null) {
        return false;
    }

    $columns = getTableColumns($dbconn, $userTable);
    $idColumn = findColumn($columns, ['id', 'user_id']);
    if ($idColumn === null) {
        return false;
    }

    $descriptionColumn = findColumn($columns, ['profile_description', 'description', 'bio', 'beskrivning']);
    $avatarColumn = findColumn($columns, ['avatar_path', 'pfp_path', 'profile_picture', 'profile_image', 'image_path', 'Pfp']);
    $backgroundColumn = findColumn($columns, ['background_path', 'banner_path', 'cover_path', 'header_image_path']);

    if ($descriptionColumn === null) {
        tryAddColumn($dbconn, $userTable, 'profile_description', 'TEXT NULL');
        $descriptionColumn = 'profile_description';
    }

    if ($avatarColumn === null) {
        tryAddColumn($dbconn, $userTable, 'avatar_path', 'VARCHAR(255) NULL');
        $avatarColumn = 'avatar_path';
    }

    if ($backgroundColumn === null) {
        tryAddColumn($dbconn, $userTable, 'background_path', 'VARCHAR(255) NULL');
        $backgroundColumn = 'background_path';
    }

    ensureTextColumnUtf8mb4($dbconn, $userTable, $descriptionColumn);

    $sql = "UPDATE `{$userTable}` SET `{$descriptionColumn}` = ?, `{$avatarColumn}` = ?, `{$backgroundColumn}` = ? WHERE `{$idColumn}` = ?";
    $stmt = $dbconn->prepare($sql);

    $avatarValue = trim($avatarPath);
    if ($avatarValue === '') {
        $avatarValue = null;
    }

    $backgroundValue = trim($backgroundPath);
    if ($backgroundValue === '') {
        $backgroundValue = null;
    }

    return $stmt->execute([$description, $avatarValue, $backgroundValue, $userId]);
}

function getUserRoleValue(PDO $dbconn, int $userId): int
{
    $meta = getUserTableMeta($dbconn);
    if ($meta === null) {
        return 0;
    }

    $columns = getTableColumns($dbconn, $meta['table']);
    $roleColumn = findColumn($columns, ['roll', 'role', 'is_admin', 'admin', 'behorighet']);
    if ($roleColumn === null) {
        return 0;
    }

    $stmt = $dbconn->prepare("SELECT `{$roleColumn}` FROM `{$meta['table']}` WHERE `{$meta['id_column']}` = ? LIMIT 1");
    $stmt->execute([$userId]);
    $value = $stmt->fetchColumn();

    return (int) ($value ?? 0);
}

function getUserAge(PDO $dbconn, int $userId): int
{
    $meta = getUserTableMeta($dbconn);
    if ($meta === null) {
        return 0;
    }

    $columns = getTableColumns($dbconn, $meta['table']);
    $ageColumn = findColumn($columns, ['alder', 'age']);
    if ($ageColumn === null) {
        return 0;
    }

    $stmt = $dbconn->prepare("SELECT `{$ageColumn}` FROM `{$meta['table']}` WHERE `{$meta['id_column']}` = ? LIMIT 1");
    $stmt->execute([$userId]);
    $value = $stmt->fetchColumn();

    return (int) ($value ?? 0);
}

function userHasAdminAccess(PDO $dbconn, int $userId): bool
{
    return getUserRoleValue($dbconn, $userId) === 1;
}

function getPostTableMeta(PDO $dbconn): ?array
{
    $postsTable = resolveTableName($dbconn, ['posts', 'post']);
    if ($postsTable === null) {
        return null;
    }

    $postColumns = getTableColumns($dbconn, $postsTable);
    $postIdColumn = findColumn($postColumns, ['id', 'post_id']);
    $postCreatorColumn = findColumn($postColumns, ['creator_id', 'user_id', 'author_id']);

    if ($postIdColumn === null || $postCreatorColumn === null) {
        return null;
    }

    return [
        'table' => $postsTable,
        'id_column' => $postIdColumn,
        'creator_column' => $postCreatorColumn,
    ];
}

function getPostCreatorId(PDO $dbconn, int $postId): ?int
{
    $meta = getPostTableMeta($dbconn);
    if ($meta === null) {
        return null;
    }

    $stmt = $dbconn->prepare("SELECT `{$meta['creator_column']}` FROM `{$meta['table']}` WHERE `{$meta['id_column']}` = ? LIMIT 1");
    $stmt->execute([$postId]);
    $creatorId = $stmt->fetchColumn();

    if ($creatorId === false || $creatorId === null) {
        return null;
    }

    return (int) $creatorId;
}

function canUserDeletePost(PDO $dbconn, int $userId, int $postId): bool
{
    if (userHasAdminAccess($dbconn, $userId)) {
        return true;
    }

    $creatorId = getPostCreatorId($dbconn, $postId);
    return $creatorId !== null && $creatorId === $userId;
}

function deletePostAsAdmin(PDO $dbconn, int $postId): bool
{
    $postsTable = resolveTableName($dbconn, ['posts', 'post']);
    if ($postsTable === null) {
        return false;
    }

    $postColumns = getTableColumns($dbconn, $postsTable);
    $postIdColumn = findColumn($postColumns, ['id', 'post_id']);
    $postImageColumn = findColumn($postColumns, ['image_path', 'image', 'bild']);

    if ($postIdColumn === null) {
        return false;
    }

    $imagePath = null;
    if ($postImageColumn !== null) {
        $getImageStmt = $dbconn->prepare("SELECT `{$postImageColumn}` FROM `{$postsTable}` WHERE `{$postIdColumn}` = ? LIMIT 1");
        $getImageStmt->execute([$postId]);
        $imagePath = $getImageStmt->fetchColumn();
    }

    $likesTable = resolveTableName($dbconn, ['likes', 'user_likes']);
    $commentsTable = resolveTableName($dbconn, ['comments', 'comment']);

    try {
        $dbconn->beginTransaction();

        if ($likesTable !== null) {
            $likeColumns = getTableColumns($dbconn, $likesTable);
            $likePostIdColumn = findColumn($likeColumns, ['post_id', 'liked_post_id']);
            if ($likePostIdColumn !== null) {
                $deleteLikes = $dbconn->prepare("DELETE FROM `{$likesTable}` WHERE `{$likePostIdColumn}` = ?");
                $deleteLikes->execute([$postId]);
            }
        }

        if ($commentsTable !== null) {
            $commentColumns = getTableColumns($dbconn, $commentsTable);
            $commentPostIdColumn = findColumn($commentColumns, ['post_id']);
            if ($commentPostIdColumn !== null) {
                $deleteComments = $dbconn->prepare("DELETE FROM `{$commentsTable}` WHERE `{$commentPostIdColumn}` = ?");
                $deleteComments->execute([$postId]);
            }
        }

        $deletePost = $dbconn->prepare("DELETE FROM `{$postsTable}` WHERE `{$postIdColumn}` = ?");
        $deletePost->execute([$postId]);
        $deleted = $deletePost->rowCount() > 0;

        $dbconn->commit();
    } catch (Throwable $e) {
        if ($dbconn->inTransaction()) {
            $dbconn->rollBack();
        }
        return false;
    }

    if ($deleted && is_string($imagePath) && strpos($imagePath, 'uploads/') === 0) {
        $absoluteImagePath = __DIR__ . '/../public/' . $imagePath;
        if (is_file($absoluteImagePath)) {
            @unlink($absoluteImagePath);
        }
    }

    return $deleted;
}

function deletePostForUser(PDO $dbconn, int $userId, int $postId): bool
{
    if (!canUserDeletePost($dbconn, $userId, $postId)) {
        return false;
    }

    return deletePostAsAdmin($dbconn, $postId);
}

function fetchCommentsForPost(PDO $dbconn, int $postId, int $limit, int $offset = 0): array
{
    $meta = getUserTableMeta($dbconn);
    if ($meta === null) {
        return [];
    }

    $avatarSelect = $meta['avatar_column'] !== null
        ? "u.`{$meta['avatar_column']}` AS avatar_path"
        : "NULL AS avatar_path";

    $sql = "
        SELECT c.id, c.post_id, c.author_id, c.body, c.created_at, u.`{$meta['name_column']}` AS username, {$avatarSelect}
        FROM comments c
        JOIN `{$meta['table']}` u ON u.`{$meta['id_column']}` = c.author_id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $dbconn->prepare($sql);
    $stmt->bindValue(1, $postId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetchCommentById(PDO $dbconn, int $commentId): ?array
{
    $meta = getUserTableMeta($dbconn);
    if ($meta === null) {
        return null;
    }

    $avatarSelect = $meta['avatar_column'] !== null
        ? "u.`{$meta['avatar_column']}` AS avatar_path"
        : "NULL AS avatar_path";

    $sql = "
        SELECT c.id, c.post_id, c.author_id, c.body, c.created_at, u.`{$meta['name_column']}` AS username, {$avatarSelect}
        FROM comments c
        JOIN `{$meta['table']}` u ON u.`{$meta['id_column']}` = c.author_id
        WHERE c.id = ?
        LIMIT 1
    ";

    $stmt = $dbconn->prepare($sql);
    $stmt->execute([$commentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getCommentTableMeta(PDO $dbconn): ?array
{
    $commentsTable = resolveTableName($dbconn, ['comments', 'comment']);
    if ($commentsTable === null) {
        return null;
    }

    $columns = getTableColumns($dbconn, $commentsTable);
    $idColumn = findColumn($columns, ['id', 'comment_id']);
    $authorColumn = findColumn($columns, ['author_id', 'user_id', 'creator_id']);

    if ($idColumn === null || $authorColumn === null) {
        return null;
    }

    return [
        'table' => $commentsTable,
        'id_column' => $idColumn,
        'author_column' => $authorColumn,
    ];
}

function canUserDeleteComment(PDO $dbconn, int $userId, int $commentId): bool
{
    if (userHasAdminAccess($dbconn, $userId)) {
        return true;
    }

    $meta = getCommentTableMeta($dbconn);
    if ($meta === null) {
        return false;
    }

    $stmt = $dbconn->prepare("SELECT `{$meta['author_column']}` FROM `{$meta['table']}` WHERE `{$meta['id_column']}` = ? LIMIT 1");
    $stmt->execute([$commentId]);
    $authorId = $stmt->fetchColumn();

    return $authorId !== false && $authorId !== null && (int) $authorId === $userId;
}

function deleteCommentForUser(PDO $dbconn, int $userId, int $commentId): bool
{
    if (!canUserDeleteComment($dbconn, $userId, $commentId)) {
        return false;
    }

    $meta = getCommentTableMeta($dbconn);
    if ($meta === null) {
        return false;
    }

    $stmt = $dbconn->prepare("DELETE FROM `{$meta['table']}` WHERE `{$meta['id_column']}` = ?");
    $stmt->execute([$commentId]);
    return $stmt->rowCount() > 0;
}
