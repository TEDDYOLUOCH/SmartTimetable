<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!is_logged_in() || !in_array(current_user()['role'], ['admin', 'lecturer'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
if (!is_uploaded_file($file)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file upload.']);
    exit;
}

$handle = fopen($file, 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Failed to open file.']);
    exit;
}

$header = fgetcsv($handle);
$col_student = array_search('student_email', $header);
$col_course = array_search('course_code', $header);
if ($col_student === false || $col_course === false) {
    echo json_encode(['success' => false, 'message' => 'CSV must have columns: student_email, course_code.']);
    exit;
}

$results = [];
$success = 0;
$fail = 0;
while (($row = fgetcsv($handle)) !== false) {
    $email = trim($row[$col_student]);
    $code = trim($row[$col_course]);
    // Find student
    $stmt = $conn->prepare('SELECT id FROM users WHERE email=? AND role="student"');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($student_id);
    $stmt->fetch();
    $stmt->close();
    if (!$student_id) {
        $results[] = "<tr><td>$email</td><td>$code</td><td style='color:red;'>Student not found</td></tr>";
        $fail++;
        continue;
    }
    // Find course
    $stmt = $conn->prepare('SELECT id FROM courses WHERE code=?');
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $stmt->bind_result($course_id);
    $stmt->fetch();
    $stmt->close();
    if (!$course_id) {
        $results[] = "<tr><td>$email</td><td>$code</td><td style='color:red;'>Course not found</td></tr>";
        $fail++;
        continue;
    }
    // Check for duplicate
    $stmt = $conn->prepare('SELECT id FROM enrollments WHERE student_id=? AND course_id=?');
    $stmt->bind_param('ii', $student_id, $course_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $results[] = "<tr><td>$email</td><td>$code</td><td style='color:orange;'>Already enrolled</td></tr>";
        $stmt->close();
        continue;
    }
    $stmt->close();
    // Enroll
    $stmt = $conn->prepare('INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $student_id, $course_id);
    if ($stmt->execute()) {
        $results[] = "<tr><td>$email</td><td>$code</td><td style='color:green;'>Enrolled</td></tr>";
        $success++;
    } else {
        $results[] = "<tr><td>$email</td><td>$code</td><td style='color:red;'>Failed</td></tr>";
        $fail++;
    }
    $stmt->close();
}
fclose($handle);

$summary_html = "<div><strong>Import Summary:</strong> $success enrolled, $fail failed.<br><table class='table table-bordered table-sm'><thead><tr><th>Student Email</th><th>Course Code</th><th>Status</th></tr></thead><tbody>" . implode('', $results) . "</tbody></table></div>";
echo json_encode(['success' => true, 'summary_html' => $summary_html]); 