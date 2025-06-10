<?php
// Displays course structure, units, and progress bars for titles.
require_once '../php/init_session.php';
include '../php/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    echo "Session not set. Redirecting to login.";
    header("Location: login.php");
    exit;
}

// Get the course ID from the URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die("Invalid course ID. Please go back and try again.");
}

$course_id = intval($_GET['id']);

try {
    // Get course name
    $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $course_name = $stmt->fetchColumn();

    if (!$course_name) {
        die("Course not found.");
    }

    // Get all units for the course
    $stmt = $pdo->prepare("SELECT unit_id, unit_name, course_id FROM units WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $course = [
        'course_name' => $course_name,
        'units' => []
    ];

    foreach ($units as $unit) {
        // Initialize unit data
        $unit_id = $unit['unit_id'];
        $course['units'][$unit_id] = [
            'unit_name' => $unit['unit_name'],
            'titles' => [],
            'total_content' => 0,
            'completed_content' => 0
        ];

        // Get all distinct titles in this unit
        $stmt = $pdo->prepare("
            SELECT DISTINCT title 
            FROM content 
            WHERE unit_id = ?
            ORDER BY title ASC
        ");
        $stmt->execute([$unit_id]);
        $titles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($titles as $title) {
            // Get total content items for this title
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM content 
                WHERE unit_id = ? AND title = ?
            ");
            $stmt->execute([$unit_id, $title]);
            $total_content = $stmt->fetchColumn();

            // Get completed content items for this title
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM user_progress up
                JOIN content c ON up.content_id = c.content_id
                WHERE up.user_id = ? AND c.unit_id = ? AND c.title = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $unit_id, $title]);
            $completed_content = $stmt->fetchColumn();

            // Add to unit totals
            $course['units'][$unit_id]['total_content'] += $total_content;
            $course['units'][$unit_id]['completed_content'] += $completed_content;

            // Add title info
            $course['units'][$unit_id]['titles'][] = [
                'title' => $title,
                'total_content' => $total_content,
                'completed_content' => $completed_content,
                'is_complete' => ($total_content > 0 && $completed_content == $total_content)
            ];
        }

        // Calculate unit completion
        $course['units'][$unit_id]['is_complete'] = (
            $course['units'][$unit_id]['total_content'] > 0 &&
            $course['units'][$unit_id]['completed_content'] == $course['units'][$unit_id]['total_content']
        );
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course</title>
    <link rel="stylesheet" href="../../shared/includes.css">
    <link rel="stylesheet" href="../stylesheet/styles.css">
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
                    <li><a href="../index.php">Dashboard</a></li>
                    <?php if ($_SESSION['role'] === 'teacher'): ?>
                        <li><a href="add_content.php">Add Content</a></li>
                    <?php endif; ?>
                    <li><a href="take_course.php">Take Course</a></li>
                    <li><a href="../php/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
        <p class="logged-in-user">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></p>

    </header>

    <main>
        <h1><?= htmlspecialchars($course['course_name']) ?></h1>

        <?php foreach ($course['units'] as $unit_id => $unit): ?>
            <div class="unit-card <?= $unit['is_complete'] ? 'completed-unit' : '' ?>">
                <h2><?= htmlspecialchars($unit['unit_name']) ?></h2>

                <!-- Unit Progress Bar -->
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?=
                                                                    $unit['total_content'] > 0
                                                                        ? round(($unit['completed_content'] / $unit['total_content']) * 100)
                                                                        : 0 ?>%;"></div>
                    </div>
                    <p><?= $unit['completed_content'] ?> of <?= $unit['total_content'] ?> content items completed</p>
                </div>

                <section>
                    <?php foreach ($unit['titles'] as $title): ?>
                        <div class="course-card <?= $title['is_complete'] ? 'completed-chapter' : '' ?>">
                            <a href="view_content.php?unit_id=<?= urlencode($unit_id) ?>&title=<?= urlencode($title['title']) ?>&course_id=<?= urlencode($course_id) ?>">
                                <h3>Chapter: <?= htmlspecialchars($title['title']) ?></h3>
                                <div class="chapter-progress">
                                    <?php if ($title['is_complete']): ?>
                                        <p class="completed-icon">Lessons completed</p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </section>
            </div>
        <?php endforeach; ?>
        <a href="#top" class="back-to-top">⬆ Back to Top</a>
    </main>
    <footer>
        <p>&copy; 2025 Learning Management System. All rights reserved.</p>
    </footer>
</body>

</html>