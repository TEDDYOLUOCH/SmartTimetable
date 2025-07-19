<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!is_logged_in() || !in_array(current_user()['role'], ['admin', 'lecturer'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// GET: fetch enrollments for a student
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_GET['student_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing student_id.']);
        exit;
    }
    $student_id = intval($_GET['student_id']);
    $sql = "SELECT e.id, c.name AS course FROM enrollments e LEFT JOIN courses c ON e.course_id = c.id WHERE e.student_id = ? ORDER BY c.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $enrollments = [];
    while ($row = $result->fetch_assoc()) {
        $enrollments[] = $row;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'enrollments' => $enrollments]);
    exit;
}

// POST: add enrollments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['student_id']) || empty($_POST['course_ids'])) {
        echo json_encode(['success' => false, 'message' => 'Missing student or courses.']);
        exit;
    }
    $student_id = intval($_POST['student_id']);
    $course_ids = $_POST['course_ids'];
    $added = 0;
    foreach ($course_ids as $course_id) {
        $course_id = intval($course_id);
        // Prevent duplicate
        $stmt = $conn->prepare('SELECT id FROM enrollments WHERE student_id=? AND course_id=?');
        $stmt->bind_param('ii', $student_id, $course_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 0) {
            $stmt2 = $conn->prepare('INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)');
            $stmt2->bind_param('ii', $student_id, $course_id);
            if ($stmt2->execute()) $added++;
            $stmt2->close();
        }
        $stmt->close();
    }
    echo json_encode(['success' => true, 'message' => "$added enrollment(s) added."]);
    exit;
}

// DELETE: remove enrollment
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $_DELETE);
    if (empty($_DELETE['id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing enrollment id.']);
        exit;
    }
    $id = intval($_DELETE['id']);
    $stmt = $conn->prepare('DELETE FROM enrollments WHERE id=?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Enrollment removed.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove enrollment.']);
    }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']); 