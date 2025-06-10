<?php
//This file renders the login form, handles client-side form submission and displays error messages.
//Key Features, CSRF token embedded in the form for security, Asynchronous POST request to ../php/login.php and redirects to ../index.php on success
include '../php/db.php';
require_once '../php/init_session.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../../shared/includes.css">
    <link rel="stylesheet" href="../stylesheet/styles.css">
    <script src="../scripts/scripts.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@400;500;700&display=swap" rel="stylesheet">

</head>

<body>
    <header>
        <div class="header-left">
            <div class="logo">
                <h1>Learning Management System</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="../../tma2.html">Home</a></li>
                    <li><a href="documentation.html">Documentation</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <h1>Login</h1>
        <section id="login">
            <form id="login-form">
                <input type="hidden" name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Login</button>
            </form>
            <div id="error-message" style="color: red; display: none;"></div>
        </section>
        <p>Don't have an account? <a href="register.php">Register here</a>.</p>
    </main>
    <footer>
        <p>&copy; 2025 Online Learning Management System. All rights reserved.</p>
    </footer>

</body>

</html>