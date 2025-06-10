<?php
//Handle Authentication
require_once 'init_session.php';
include 'db.php';
// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../part1/html/login.php");
    exit;
}
// Fetch top 10 popular bookmarks
$query = "SELECT title, url, count FROM popular_bookmarks ORDER BY count DESC LIMIT 10";
$stmt = $pdo->query($query);
$popularBookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user-specific bookmarks
$userId = $_SESSION['user_id'];
$query = "SELECT id, title, url FROM bookmarks WHERE user_id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->execute(['user_id' => $userId]);
$userBookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add bookmark form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add-bookmark'])) {
    $title = trim($_POST['title']);
    $url = trim($_POST['url']);
    $userId = $_SESSION['user_id']; // Get the logged-in user's ID

    if (!empty($title) && filter_var($url, FILTER_VALIDATE_URL)) {
        // Check if the bookmark already exists for the user
        $query = "SELECT COUNT(*) FROM bookmarks WHERE user_id = :user_id AND url = :url";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $userId, 'url' => $url]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $_SESSION['url_error'] = "⚠️ This URL is already bookmarked!";
        } else {
            // Add the bookmark to the user's bookmarks
            $query = "INSERT INTO bookmarks (user_id, title, url) VALUES (:user_id, :title, :url)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'user_id' => $userId,
                'title' => $title,
                'url' => $url
            ]);

            // Update the popular_bookmarks table
            $query = "INSERT INTO popular_bookmarks (url, title, count) 
                      VALUES (:url, :title, 1)
                      ON DUPLICATE KEY UPDATE count = count + 1";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'url' => $url,
                'title' => $title,
            ]);
        }

        // Redirect to refresh the page and show the updated list
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p style='color: red;'>Invalid input. Please provide a valid title and URL.</p>";
    }
}

// Handle edit bookmark form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit-bookmark'])) {
    $id = intval($_POST['id']);
    $title = trim($_POST['title']);
    $url = trim($_POST['url']);

    if (!empty($title) && filter_var($url, FILTER_VALIDATE_URL)) {
        $query = "UPDATE bookmarks SET title = :title, url = :url WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'title' => $title,
            'url' => $url,
            'id' => $id,
            'user_id' => $userId
        ]);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<p style='color: red;'>Invalid input. Please provide a valid title and URL.</p>";
    }
}

// Handle delete bookmark form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-bookmark'])) {
    $id = intval($_POST['id']);
    $userId = $_SESSION['user_id'];

    if (!$id) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    try {
        $query = "DELETE FROM bookmarks WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        error_log("SQL Error: " . $e->getMessage());
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

?>