<?php
require_once '../includes/auth.php';
require_login();
include '../templates/header.php';
?>
<div class="container" style="max-width:900px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4 text-center">Timetable</h2>
            <p class="lead text-center">(Feature coming soon: Here you will be able to view the full timetable for all courses.)</p>
            <!-- Future: Add timetable table or calendar view here -->
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?> 