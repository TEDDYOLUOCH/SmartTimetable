<?php
require_once '../includes/auth.php';
require_login();
if (!in_array(current_user()['role'], ['admin', 'lecturer'])) {
    header('Location: ../views/dashboard.php');
    exit();
}
include '../templates/header.php';
require_once '../includes/db_connect.php';

// Fetch all students and courses
$students = $conn->query("SELECT id, name, email FROM users WHERE role='student' ORDER BY name");
$courses = $conn->query("SELECT id, name FROM courses ORDER BY name");

// For select options as arrays
function fetch_options($result) {
    $arr = [];
    while ($row = $result->fetch_assoc()) $arr[] = $row;
    return $arr;
}
$student_opts = fetch_options($conn->query("SELECT id, name, email FROM users WHERE role='student' ORDER BY name"));
$course_opts = fetch_options($conn->query("SELECT id, name FROM courses ORDER BY name"));
?>
<div class="container" style="max-width:1000px;margin:40px auto;">
    <div class="card shadow mt-5">
        <div class="card-body">
            <h2 class="card-title mb-4">User & Enrollment Management</h2>
            <p class="lead">Enroll students in courses, view and manage current enrollments, or import enrollments in bulk.</p>
            <hr>
            <h5 class="mb-3">Enroll a Student in Courses</h5>
            <form id="enrollForm" class="mb-4">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label>Student:</label>
                        <select class="form-control" name="student_id" id="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach($student_opts as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name'] . ' (' . $s['email'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>Course(s):</label>
                        <select class="form-control" name="course_ids[]" id="course_ids" multiple required>
                            <?php foreach($course_opts as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Hold Ctrl (Windows) or Cmd (Mac) to select multiple courses.</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-2">Enroll</button>
                <span id="enrollMsg" style="margin-left:15px;"></span>
            </form>
            <hr>
            <h5 class="mb-3">Current Enrollments</h5>
            <div id="enrollmentsTable" class="mb-4"></div>
            <hr>
            <h5 class="mb-3">Bulk Enrollment Import</h5>
            <a href="bulk-enrollment-template.csv" class="btn btn-link mb-2" download>Download CSV Template</a>
            <form id="bulkImportForm" enctype="multipart/form-data" class="mb-4">
                <div class="form-group">
                    <label for="csv_file">Upload CSV (columns: student_email, course_code):</label>
                    <input type="file" class="form-control-file" id="csv_file" name="csv_file" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-secondary">Import Enrollments</button>
                <span id="bulkImportMsg" style="margin-left:15px;"></span>
            </form>
            <div id="bulkImportResult"></div>
        </div>
    </div>
</div>
<script>
function fetchEnrollments() {
    const studentId = document.getElementById('student_id').value;
    if (!studentId) {
        document.getElementById('enrollmentsTable').innerHTML = '<em>Select a student to view enrollments.</em>';
        return;
    }
    fetch('../api/enrollments.php?student_id=' + studentId)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let html = '<table class="table table-bordered table-sm"><thead><tr>' +
                    '<th>Course</th><th>Action</th></tr></thead><tbody>';
                data.enrollments.forEach(e => {
                    html += `<tr><td>${e.course}</td><td><button class='btn btn-sm btn-danger' onclick='removeEnrollment(${e.id})'>Remove</button></td></tr>`;
                });
                html += '</tbody></table>';
                document.getElementById('enrollmentsTable').innerHTML = html;
            } else {
                document.getElementById('enrollmentsTable').innerHTML = '<span style="color:red;">Failed to load enrollments.</span>';
            }
        });
}
document.getElementById('student_id').onchange = fetchEnrollments;
fetchEnrollments();

document.getElementById('enrollForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const res = await fetch('../api/enrollments.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    const msg = document.getElementById('enrollMsg');
    if (data.success) {
        msg.innerHTML = '<span style="color:green;">' + data.message + '</span>';
        fetchEnrollments();
    } else {
        msg.innerHTML = '<span style="color:red;">' + data.message + '</span>';
    }
};
function removeEnrollment(id) {
    if (!confirm('Remove this enrollment?')) return;
    const formData = new URLSearchParams();
    formData.append('id', id);
    fetch('../api/enrollments.php', {
        method: 'DELETE',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            fetchEnrollments();
        } else {
            alert(data.message);
        }
    });
}

document.getElementById('bulkImportForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const msg = document.getElementById('bulkImportMsg');
    msg.innerHTML = 'Importing...';
    const res = await fetch('../api/bulk-enroll.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    msg.innerHTML = '';
    if (data.success) {
        document.getElementById('bulkImportResult').innerHTML = data.summary_html;
    } else {
        document.getElementById('bulkImportResult').innerHTML = '<span style="color:red;">' + data.message + '</span>';
    }
};
</script>
<?php include '../templates/footer.php'; ?> 