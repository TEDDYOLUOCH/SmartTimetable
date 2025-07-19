<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id, name, email, password, role, phone FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Remove password before storing in session/response
            unset($user['password']);
            $_SESSION['user'] = $user;
            // Record login event
            $login_stmt = $conn->prepare('INSERT INTO user_logins (user_id) VALUES (?)');
            $login_stmt->bind_param('i', $user['id']);
            $login_stmt->execute();
            $login_stmt->close();
            echo json_encode(['success' => true, 'message' => 'Login successful.', 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
} 