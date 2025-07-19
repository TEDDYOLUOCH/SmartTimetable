<?php
require_once '../includes/auth.php';
require_login();
if (current_user()['role'] !== 'student') {
    header('Location: ../views/dashboard.php');
    exit();
}
include '../templates/header.php';
require_once '../includes/db_connect.php';

$user_id = current_user()['id'];
// Fetch all courses
$courses = $conn->query("SELECT id, name FROM courses ORDER BY name");
// Fetch student's current enrollments
$enrolled = [];
$res = $conn->query("SELECT course_id FROM enrollments WHERE student_id = $user_id");
while ($row = $res->fetch_assoc()) $enrolled[] = $row['course_id'];
?>
<div class="container" style="max-width:700px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4">Course Self-Enrollment</h2>
            <p class="lead">Select the courses you wish to enroll in. Your current enrollments are pre-checked.</p>
            <form id="selfEnrollForm">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm bg-white">
                        <thead class="thead-light">
                            <tr><th>Enroll</th><th>Course</th></tr>
                        </thead>
                        <tbody>
                            <?php $hasCourses = false; while($c = $courses->fetch_assoc()): $hasCourses = true; ?>
                            <tr>
                                <td class="text-center"><input type="checkbox" name="course_ids[]" value="<?php echo $c['id']; ?>" <?php if(in_array($c['id'], $enrolled)) echo 'checked'; ?>></td>
                                <td><?php echo htmlspecialchars($c['name']); ?></td>
                            </tr>
                            <?php endwhile; if (!$hasCourses): ?>
                            <tr><td colspan="2" class="text-center text-muted">No courses available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-primary">Update Enrollments</button>
                <span id="enrollMsg" style="margin-left:15px;"></span>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('selfEnrollForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append('student_id', <?php echo $user_id; ?>);
    const res = await fetch('../api/enrollments.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    const msg = document.getElementById('enrollMsg');
    if (data.success) {
        msg.innerHTML = '<span style="color:green;">' + data.message + '</span>';
    } else {
        msg.innerHTML = '<span style="color:red;">' + data.message + '</span>';
    }
};
</script>
<?php include '../templates/footer.php'; ?> 