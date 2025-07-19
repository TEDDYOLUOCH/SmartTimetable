<?php
require_once '../includes/db_connect.php';
require_once '../config.php';

// Use PHPMailer if available
$use_phpmailer = file_exists('../vendor/autoload.php');
if ($use_phpmailer) {
    require_once '../vendor/autoload.php';
}

// Get today's date
$today = date('Y-m-d');

// Fetch all students
$students = $conn->query("SELECT id, name, email FROM users WHERE role='student'");

while ($student = $students->fetch_assoc()) {
    $student_id = $student['id'];
    $student_name = $student['name'];
    $student_email = $student['email'];

    // Fetch today's classes for this student (only enrolled courses)
    $sql = "SELECT t.start_time, t.end_time, c.name AS course, u.name AS lecturer, r.name AS room
            FROM timetables t
            LEFT JOIN courses c ON t.course_id = c.id
            LEFT JOIN users u ON t.lecturer_id = u.id
            LEFT JOIN rooms r ON t.room_id = r.id
            INNER JOIN enrollments e ON e.course_id = t.course_id
            WHERE t.date = ? AND t.status = 'scheduled' AND e.student_id = ? ORDER BY t.start_time";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $today, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $body = "Dear $student_name,<br><br>Here is your class schedule for today:<br><ul>";
        while ($row = $result->fetch_assoc()) {
            $body .= "<li><strong>{$row['course']}</strong> with {$row['lecturer']} in {$row['room']} from {$row['start_time']} to {$row['end_time']}</li>";
        }
        $body .= "</ul><br>Best regards,<br>Smart Timetable Notifier";

        $subject = "Today's Class Schedule Reminder";

        // Send email
        if ($use_phpmailer) {
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = 'tls';
            $mail->Port = SMTP_PORT;
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($student_email, $student_name);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            @$mail->send();
        } else {
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
            @mail($student_email, $subject, $body, $headers);
        }
    }
    $stmt->close();
}
$conn->close(); 