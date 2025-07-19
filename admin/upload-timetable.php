<?php
require_once '../includes/auth.php';
require_login();
if (!in_array(current_user()['role'], ['admin', 'lecturer'])) {
    header('Location: ../views/dashboard.php');
    exit();
}
include '../templates/header.php';
?>
<div class="container" style="max-width:600px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">Upload Timetable</h2>
            <p class="lead text-center">(Feature coming soon: Here you will be able to upload a timetable file, such as a CSV, to bulk add class sessions.)</p>
            <!-- Future: Add form for uploading timetable CSV -->
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?> 