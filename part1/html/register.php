<?php
require_once '../php/init_session.php';
include '../php/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../../shared/includes.css">
    <link rel="stylesheet" href="../stylesheet/styles.css">
    <script src="../javascript/scripts.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@400;500;700&display=swap" rel="stylesheet">
</head>

<body>
    <header>
        <div class="header-left">
            <div class="logo">
                <h1>Bookmark Manager</h1>
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
        <h1>Register</h1>
        <section id="register">
            <form method="POST" action="../php/register.php">
                <input type="hidden" name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <label for="confirm-password">Confirm Password:</label>
                <input type="password" id="confirm-password" name="confirm-password" required>
                <button type="submit">Register</button>
            </form>
        </section>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </main>

    <footer>
        <p>&copy; 2025 Bookmark Manager. All rights reserved.</p>
        <a href="#top" class="back-to-top">⬆ Back to Top</a>
    </footer>

</body>

</html>