<?php
// configures secure session parameters and CSRF token generatjion
// sets secure, httponly, and samesite cookie attributes, regenerates session IDs to prevent fixation.
// Only configure session if not already started
// Enable strict error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Only configure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Use a unique session name
    session_name('TMA2_SESSION');
    
    // Configure cookie parameters - CRITICAL UPDATE
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',       // Accessible across entire domain
        'domain' => $_SERVER['HTTP_HOST'], // Current domain
        'secure' => true,    // Must be true for HTTPS
        'httponly' => true,
        'samesite' => 'Lax'  // Balanced security
    ]);
    
    session_start();
    
    // Debug output
    error_log("Session started with ID: " . session_id());
} else {
    error_log("Session already active: " . session_id());
}

// Regenerate ID to prevent session fixation
if (empty($_SESSION['init'])) {
    session_regenerate_id(true);
    $_SESSION['init'] = true;
    error_log("Session regenerated: " . session_id());
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("CSRF token generated");
}