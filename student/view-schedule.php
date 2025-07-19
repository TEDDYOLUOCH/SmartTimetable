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
// Fetch only classes for courses the student is enrolled in
$sql = "SELECT t.date, t.start_time, t.end_time, c.name AS course, u.name AS lecturer, r.name AS room, t.status
        FROM timetables t
        LEFT JOIN courses c ON t.course_id = c.id
        LEFT JOIN users u ON t.lecturer_id = u.id
        LEFT JOIN rooms r ON t.room_id = r.id
        INNER JOIN enrollments e ON e.course_id = t.course_id
        WHERE e.student_id = ? AND t.status = 'scheduled'
        ORDER BY t.date, t.start_time";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="container" style="max-width:900px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4">My Timetable</h2>
            <p class="lead">Below is your personalized class schedule based on your current course enrollments.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm bg-white">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Course</th>
                            <th>Lecturer</th>
                            <th>Room</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted">No scheduled classes found for your enrollments.</td></tr>
                        <?php else: while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                <td><?php echo htmlspecialchars($row['start_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['end_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['course']); ?></td>
                                <td><?php echo htmlspecialchars($row['lecturer']); ?></td>
                                <td><?php echo htmlspecialchars($row['room']); ?></td>
                                <td><span class="badge badge-<?php echo $row['status']==='scheduled'?'success':($row['status']==='cancelled'?'danger':'warning'); ?> text-uppercase"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../templates/footer.php'; ?> 