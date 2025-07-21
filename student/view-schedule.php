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
// Fetch all scheduled classes (not just enrolled)
$sql = "SELECT t.date, t.start_time, t.end_time, c.name AS course, c.unit AS unit, u.name AS lecturer, r.name AS room, t.status, t.mode
        FROM timetables t
        LEFT JOIN courses c ON t.course_id = c.id
        LEFT JOIN users u ON t.lecturer_id = u.id
        LEFT JOIN rooms r ON t.room_id = r.id
        WHERE t.status = 'scheduled'
        ORDER BY t.date, t.start_time";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all courses and units for filter dropdowns
$course_list = [];
$unit_list = [];
$course_result = $conn->query("SELECT name, unit FROM courses ORDER BY name");
while ($row = $course_result->fetch_assoc()) {
    if (!in_array($row['name'], $course_list)) $course_list[] = $row['name'];
    if (!in_array($row['unit'], $unit_list)) $unit_list[] = $row['unit'];
}
?>
<div class="container" style="max-width:900px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4">My Timetable</h2>
            <p class="lead">Below is your personalized class schedule based on your current course enrollments.</p>
            <div class="row mb-3">
                <div class="col-md-4 mb-2">
                    <label>Filter by Course:</label>
                    <select class="form-control" id="filter_course">
                        <option value="">All Courses</option>
                        <?php foreach($course_list as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label>Filter by Unit/Field:</label>
                    <select class="form-control" id="filter_unit">
                        <option value="">All Units</option>
                        <?php foreach($unit_list as $u): ?>
                            <option value="<?php echo htmlspecialchars($u); ?>"><?php echo htmlspecialchars($u); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-end">
                    <button class="btn btn-success mr-2" id="download_pdf">Download as PDF</button>
                    <button class="btn btn-primary" id="download_csv">Download as CSV</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm bg-white" id="timetableTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Course</th>
                            <th>Unit/Field</th>
                            <th>Lecturer</th>
                            <th>Room</th>
                            <th>Mode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr><td colspan="9" class="text-center text-muted">No scheduled classes found for your enrollments.</td></tr>
                        <?php else: while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                <td><?php echo htmlspecialchars($row['start_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['end_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['course']); ?></td>
                                <td><?php echo htmlspecialchars($row['unit']); ?></td>
                                <td><?php echo htmlspecialchars($row['lecturer']); ?></td>
                                <td><?php echo htmlspecialchars($row['room']); ?></td>
                                <td><?php echo htmlspecialchars($row['mode']); ?></td>
                                <td><span class="badge badge-<?php echo $row['status']==='scheduled'?'success':($row['status']==='cancelled'?'danger':'warning'); ?> text-uppercase"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Add jsPDF and html2canvas CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterCourse = document.getElementById('filter_course');
    const filterUnit = document.getElementById('filter_unit');
    const table = document.getElementById('timetableTable');
    filterCourse.addEventListener('change', filterTable);
    filterUnit.addEventListener('change', filterTable);
    function filterTable() {
        const courseVal = filterCourse.value;
        const unitVal = filterUnit.value;
        for (let row of table.tBodies[0].rows) {
            const courseCell = row.cells[3].textContent;
            const unitCell = row.cells[4].textContent;
            let show = true;
            if (courseVal && courseCell !== courseVal) show = false;
            if (unitVal && unitCell !== unitVal) show = false;
            row.style.display = show ? '' : 'none';
        }
    }
    document.getElementById('download_csv').addEventListener('click', function() {
        let csv = '';
        const rows = table.querySelectorAll('tr');
        for (let row of rows) {
            let cols = Array.from(row.querySelectorAll('th,td')).map(td => '"' + td.innerText.replace(/"/g, '""') + '"');
            if (row.style.display !== 'none') csv += cols.join(',') + '\n';
        }
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'timetable.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
    document.getElementById('download_pdf').addEventListener('click', function() {
        // Hide rows that are filtered out for the screenshot
        const rows = Array.from(table.tBodies[0].rows);
        const hiddenRows = rows.filter(row => row.style.display === 'none');
        hiddenRows.forEach(row => row.style.display = 'none');
        html2canvas(table, { scale: 2 }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new window.jspdf.jsPDF({ orientation: 'landscape' });
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const imgProps = pdf.getImageProperties(imgData);
            let pdfWidth = pageWidth;
            let pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
            if (pdfHeight > pageHeight) {
                pdfHeight = pageHeight;
                pdfWidth = (imgProps.width * pdfHeight) / imgProps.height;
            }
            pdf.addImage(imgData, 'PNG', 10, 10, pdfWidth - 20, pdfHeight - 20);
            pdf.save('timetable.pdf');
            // Restore hidden rows
            hiddenRows.forEach(row => row.style.display = 'none');
        });
    });
});
</script>
<?php include '../templates/footer.php'; ?> 