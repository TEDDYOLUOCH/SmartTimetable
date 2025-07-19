<?php
require_once '../includes/auth.php';
require_login();
if (current_user()['role'] !== 'lecturer') {
    header('Location: ../views/dashboard.php');
    exit();
}
include '../templates/header.php';
?>
<div class="container" style="max-width:600px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">Cancel Class Session</h2>
            <p class="lead text-center">(Feature coming soon: Here you will be able to cancel a scheduled class and notify students.)</p>
            <!-- Future: Add form for selecting and cancelling a class session -->
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?> 