<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

// Helper function to sanitize input
define('REQUIRED_FIELDS', ['name', 'email', 'password']);

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check if POST and required fields are set
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $missing = array_diff(REQUIRED_FIELDS, array_keys($_POST));
    if (!empty($missing)) {
        echo json_encode(['success' => false, 'message' => 'Missing fields: ' . implode(', ', $missing)]);
        exit;
    }

    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = 'student'; // Force role to student
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : null;

    // Remove role validation
    // $valid_roles = ['student', 'lecturer', 'admin'];
    // if (!in_array($role, $valid_roles)) {
    //     echo json_encode(['success' => false, 'message' => 'Invalid role.']);
    //     exit;
    // }

    // Check if email already exists
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered.']);
        exit;
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare('INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('sssss', $name, $email, $hashed_password, $role, $phone);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registration successful.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed.']);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
} 