<?php
require_once '../includes/auth.php';
require_login();
$user = current_user();
include '../templates/header.php';
require_once '../includes/db_connect.php';

// Only show notification history for students
if ($user['role'] === 'student') {
    $stmt = $conn->prepare("SELECT message, type, sent_at FROM notifications WHERE user_id = ? ORDER BY sent_at DESC");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<div class="container" style="max-width:700px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">Notification History</h2>
            <?php if ($user['role'] === 'student'): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm bg-white">
                        <thead class="thead-light">
                            <tr>
                                <th>Date/Time</th>
                                <th>Type</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows === 0): ?>
                                <tr><td colspan="3" class="text-center text-muted">No notifications found.</td></tr>
                            <?php else: while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['sent_at']); ?></td>
                                    <td><span class="badge badge-<?php echo $row['type']==='reminder'?'info':($row['type']==='update'?'warning':'danger'); ?> text-uppercase"><?php echo htmlspecialchars($row['type']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['message']); ?></td>
                                </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="lead text-center">(Feature coming soon: Here you will see your notification history and important alerts.)</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?> 