<?php
//Validates user credentials, checks CSRF token, and intitiates a session.
//Uses password_verify() for secure pasword validation, returns JSON responses for AJAX handling, Generates CSRF token if missing.
require_once 'init_session.php';
include 'db.php';

// Ensure token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }

        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validate input
        if (empty($username) || empty($password)) {
            throw new Exception('Please fill in all fields');
        }

        // Database query
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validate credentials
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('Invalid username or password');
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];


        // Send success response
        $response = ['success' => true];
    } catch (Exception $e) {
        // Send error response
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Invalid request method'
    ];
}

// Clear any accidental output
ob_end_clean();

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;