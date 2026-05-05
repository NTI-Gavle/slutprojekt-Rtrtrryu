<?php
$pageTitle = "Manage Users";
$extraStyles = [];
require_once __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../database/user_queries.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int) $_SESSION['user_id'];
if (!userHasAdminAccess($dbconn, $currentUserId)) {
    http_response_code(403);
    echo '<div class="container py-4"><div class="alert alert-danger">Access denied.</div></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $incomingToken = (string) ($_POST['csrf_token'] ?? '');
    $targetUserId = filter_input(INPUT_POST, 'delete_user_id', FILTER_VALIDATE_INT);

    if (!hash_equals((string) $_SESSION['csrf_token'], $incomingToken)) {
        $message = 'Security token mismatch. Refresh and try again.';
        $messageType = 'danger';
    } elseif (!$targetUserId) {
        $message = 'Invalid user id.';
        $messageType = 'danger';
    } elseif ((int) $targetUserId === $currentUserId) {
        $message = 'You cannot delete your own admin account.';
        $messageType = 'warning';
    } else {
        $deleted = deleteUserAndAllData($dbconn, (int) $targetUserId, $currentUserId);
        if ($deleted) {
            $message = 'User and all related data were deleted.';
            $messageType = 'success';
        } else {
            $message = 'Could not delete this user.';
            $messageType = 'danger';
        }
    }
}

$users = listUsersForAdmin($dbconn);
?>


<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h4 mb-0">Admin - Manage Users</h2>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>

  <div class="alert alert-warning py-2">
    Deleting a user removes their posts, comments, likes, profile images, and account.
  </div>

  <?php if ($message !== null): ?>
    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> py-2">
      <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0 admin-users-card">
    <div class="table-responsive admin-users-table-responsive">
      <table class="table table-hover align-middle mb-0 admin-users-table">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Age</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <?php
              $uid = (int) ($u['user_id'] ?? 0);
              $isSelf = $uid === $currentUserId;
              $role = (int) ($u['role_value'] ?? 0);
            ?>
            <tr>
              <td data-label="ID"><?php echo $uid; ?></td>
              <td data-label="Username"><?php echo htmlspecialchars((string) ($u['username'] ?? 'unknown')); ?></td>
              <td data-label="Role">
                <?php if ($role === 1): ?>
                  <span class="badge bg-primary">Admin</span>
                <?php else: ?>
                  <span class="badge bg-secondary">User</span>
                <?php endif; ?>
              </td>
              <td data-label="Age"><?php echo htmlspecialchars((string) ($u['age_value'] ?? '-')); ?></td>
              <td data-label="Action" class="text-end">
                <?php if ($isSelf): ?>
                  <span class="text-muted small">Current account</span>
                <?php else: ?>
                    <form method="POST" action="admin-users.php" class="d-inline" data-confirm="Delete this user and all their data?">
                    <input type="hidden" name="delete_user_id" value="<?php echo $uid; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $_SESSION['csrf_token']); ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete user</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>


