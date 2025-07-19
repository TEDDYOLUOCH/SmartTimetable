<?php
require_once '../includes/auth.php';
require_login();
$user = current_user();
include '../templates/header.php';
?>
<div class="container" style="max-width:700px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-3">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
            <h5 class="mb-4">Role: <span class="badge badge-info text-uppercase"><?php echo htmlspecialchars($user['role']); ?></span></h5>
            <p class="lead">This is your dashboard. Use the quick links below to access the main features of the Smart Timetable Notifier system.</p>
            <div class="row mt-4">
                <?php if ($user['role'] === 'student'): ?>
                    <div class="col-md-6 mb-3">
                        <a href="../student/view-schedule.php" class="btn btn-outline-primary btn-block btn-lg">View My Timetable</a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="../student/enroll.php" class="btn btn-outline-success btn-block btn-lg">Enroll in Courses</a>
                    </div>
                <?php endif; ?>
                <?php if (in_array($user['role'], ['admin', 'lecturer'])): ?>
                    <div class="col-md-6 mb-3">
                        <a href="../views/manage-timetable.php" class="btn btn-outline-primary btn-block btn-lg">Manage Timetable</a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="../admin/user-management.php" class="btn btn-outline-success btn-block btn-lg">User/Enrollment Management</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?> 