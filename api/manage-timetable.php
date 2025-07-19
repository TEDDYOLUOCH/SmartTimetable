<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';
require_once '../config.php';
header('Content-Type: application/json');

if (!is_logged_in() || !in_array(current_user()['role'], ['admin', 'lecturer'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Helper to sanitize
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Helper to send notifications (email + SMS)
function notify_students($conn, $timetable_id, $type, $message) {
    // Get all students
    $students = $conn->query("SELECT name, email, phone FROM users WHERE role='student'");
    // Get class info
    $sql = "SELECT t.date, t.start_time, t.end_time, c.name AS course, u.name AS lecturer, r.name AS room
            FROM timetables t
            LEFT JOIN courses c ON t.course_id = c.id
            LEFT JOIN users u ON t.lecturer_id = u.id
            LEFT JOIN rooms r ON t.room_id = r.id
            WHERE t.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $timetable_id);
    $stmt->execute();
    $class = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$class) return;
    $subject = ($type === 'cancel') ? 'Class Cancelled' : 'Class Updated';
    $body = "<strong>Class:</strong> {$class['course']}<br>" .
            "<strong>Lecturer:</strong> {$class['lecturer']}<br>" .
            "<strong>Room:</strong> {$class['room']}<br>" .
            "<strong>Date:</strong> {$class['date']}<br>" .
            "<strong>Time:</strong> {$class['start_time']} - {$class['end_time']}<br><br>" .
            "$message";
    // Email/SMS each student
    while ($student = $students->fetch_assoc()) {
        // Email
        $to = $student['email'];
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
        @mail($to, $subject, $body, $headers);
        // SMS (Twilio)
        if (defined('TWILIO_SID') && !empty($student['phone'])) {
            $sid = TWILIO_SID;
            $token = TWILIO_TOKEN;
            $from = TWILIO_FROM;
            $sms_body = "[Smart Timetable] {$class['course']} on {$class['date']} at {$class['start_time']} - {$message}";
            $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
            $data = http_build_query([
                'From' => $from,
                'To' => $student['phone'],
                'Body' => $sms_body
            ]);
            $options = [
                'http' => [
                    'header'  => "Authorization: Basic " . base64_encode("$sid:$token") . "\r\nContent-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => $data,
                ],
            ];
            @file_get_contents($url, false, stream_context_create($options));
        }
    }
}

// Fetch all timetable entries (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT t.id, c.name AS course, u.name AS lecturer, r.name AS room, t.date, t.start_time, t.end_time, t.status
            FROM timetables t
            LEFT JOIN courses c ON t.course_id = c.id
            LEFT JOIN users u ON t.lecturer_id = u.id
            LEFT JOIN rooms r ON t.room_id = r.id
            ORDER BY t.date, t.start_time";
    $result = $conn->query($sql);
    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }
    echo json_encode(['success' => true, 'entries' => $entries]);
    exit;
}

// Add new timetable entry (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['course_id', 'lecturer_id', 'room_id', 'date', 'start_time', 'end_time', 'status'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'Missing field: ' . $field]);
            exit;
        }
    }
    $course_id = intval($_POST['course_id']);
    $lecturer_id = intval($_POST['lecturer_id']);
    $room_id = intval($_POST['room_id']);
    $date = sanitize($_POST['date']);
    $start_time = sanitize($_POST['start_time']);
    $end_time = sanitize($_POST['end_time']);
    $status = sanitize($_POST['status']);
    $stmt = $conn->prepare('INSERT INTO timetables (course_id, lecturer_id, room_id, date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iiissss', $course_id, $lecturer_id, $room_id, $date, $start_time, $end_time, $status);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Timetable entry added.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add entry.']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Edit timetable entry (PUT)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents('php://input'), $_PUT);
    $required = ['id', 'course_id', 'lecturer_id', 'room_id', 'date', 'start_time', 'end_time', 'status'];
    foreach ($required as $field) {
        if (empty($_PUT[$field])) {
            echo json_encode(['success' => false, 'message' => 'Missing field: ' . $field]);
            exit;
        }
    }
    $id = intval($_PUT['id']);
    $course_id = intval($_PUT['course_id']);
    $lecturer_id = intval($_PUT['lecturer_id']);
    $room_id = intval($_PUT['room_id']);
    $date = sanitize($_PUT['date']);
    $start_time = sanitize($_PUT['start_time']);
    $end_time = sanitize($_PUT['end_time']);
    $status = sanitize($_PUT['status']);
    $stmt = $conn->prepare('UPDATE timetables SET course_id=?, lecturer_id=?, room_id=?, date=?, start_time=?, end_time=?, status=? WHERE id=?');
    $stmt->bind_param('iiissssi', $course_id, $lecturer_id, $room_id, $date, $start_time, $end_time, $status, $id);
    if ($stmt->execute()) {
        // Notify students if class is cancelled or changed
        if ($status === 'cancelled') {
            notify_students($conn, $id, 'cancel', 'This class has been cancelled.');
        } else {
            notify_students($conn, $id, 'update', 'This class has been updated.');
        }
        echo json_encode(['success' => true, 'message' => 'Timetable entry updated.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update entry.']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Delete timetable entry (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $_DELETE);
    if (empty($_DELETE['id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing timetable id.']);
        exit;
    }
    $id = intval($_DELETE['id']);
    // Notify students before deleting
    notify_students($conn, $id, 'cancel', 'This class has been cancelled.');
    $stmt = $conn->prepare('DELETE FROM timetables WHERE id=?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Timetable entry deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete entry.']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// For unsupported methods
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']); 