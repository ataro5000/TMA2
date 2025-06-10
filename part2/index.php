<?php
require_once 'php/init_session.php';
include 'php/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: html/login.php");
    exit;
}

try {
    // Get all enrolled courses
    $stmt = $pdo->prepare("
        SELECT c.course_id, c.course_name
        FROM student_enrollments se
        JOIN courses c ON se.course_id = c.course_id
        WHERE se.student_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $enrolledCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $courses = [];
    foreach ($enrolledCourses as $course) {
        // Get all units for the course
        $stmt = $pdo->prepare("
            SELECT unit_id, unit_name
            FROM units
            WHERE course_id = ?
        ");
        $stmt->execute([$course['course_id']]);
        $units = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalUnits = count($units);
        $completedUnits = 0;

        foreach ($units as $unit) {
            // Get count of all content items in this unit
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total_content
                FROM content
                WHERE unit_id = ?
            ");
            $stmt->execute([$unit['unit_id']]);
            $totalContent = $stmt->fetchColumn();

            // Get count of completed content in this unit
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as completed_content
                FROM user_progress up
                JOIN content c ON up.content_id = c.content_id
                WHERE up.user_id = ? AND c.unit_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $unit['unit_id']]);
            $completedContent = $stmt->fetchColumn();

            // Mark unit as completed if all content is done (and unit has content)
            if ($totalContent > 0 && $completedContent == $totalContent) {
                $completedUnits++;
            }
        }

        $courses[] = [
            'course_id' => $course['course_id'],
            'course_name' => $course['course_name'],
            'total_units' => $totalUnits,
            'completed_units' => $completedUnits
        ];
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
    <title>Learning Management System</title>
    <link rel="stylesheet" href="../shared/includes.css">
    <link rel="stylesheet" href="stylesheet/styles.css">
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
                    <li><a href="../tma2.html">Home</a></li>
                    <?php if ($_SESSION['role'] === 'teacher'): ?>
                        <li><a href="html/add_content.php">Add Content</a></li>
                    <?php endif; ?>
                    <li><a href="html/take_course.php">Take Course</a></li>
                    <li><a href="html/documentation.html">Documentation</a></li>
                    <li><a href="php/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
        <p class="logged-in-user">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></p>
    </header>

    <main>
        <h1>My Courses</h1>

        <?php if (empty($courses)): ?>
            <section>
                <p>You are not enrolled in any courses yet.</p>
            </section>
        <?php else: ?>
            <div class="course-list">
                <?php foreach ($courses as $course): ?>
                    <section>
                        <div class="course-card" onclick="window.location='html/course.php?id=<?= $course['course_id'] ?>';">
                            <h2><?= htmlspecialchars($course['course_name']) ?></h2>
                            <div class="progress-container">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?=
                                                                                $course['total_units'] > 0
                                                                                    ? round(($course['completed_units'] / $course['total_units']) * 100)
                                                                                    : 0 ?>%;"></div>
                                </div>
                                <p>Progress: <?= $course['completed_units'] ?>/<?= $course['total_units'] ?> Units Completed</p>
                            </div>
                            <p>Click to View Course</p>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a href="#top" class="back-to-top">⬆ Back to Top</a>
    </main>
    <footer>
        <p>&copy; 2025 Learning Management System. All rights reserved.</p>
    </footer>
</body>

</html>