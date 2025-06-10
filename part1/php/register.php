<?php
require_once 'init_session.php';
include 'db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token first
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirm-password']);

        if (!empty($username) && !empty($password) && !empty($confirmPassword)) {
            if ($password === $confirmPassword) {
                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert the user into the database
                $query = "INSERT INTO users (username, password) VALUES (:username, :password)";
                $stmt = $pdo->prepare($query);

                try {
                    $stmt->execute([
                        'username' => $username,
                        'password' => $hashedPassword
                    ]);
                    header("Location: ../html/login.php");
                    exit;
                } catch (PDOException $e) {
                    if ($e->errorInfo[1] === 1062) { // Duplicate entry error
                        echo "<p style='color: red;'>Username already exists. Please choose another.</p>";
                    } else {
                        echo "<p style='color: red;'>An error occurred. Please try again later.</p>";
                    }
                }
            } else {
                echo "<p style='color: red;'>Passwords do not match. Please try again.</p>";
            }
        } else {
            echo "<p style='color: red;'>Please fill in all fields.</p>";
        }
    }
}
