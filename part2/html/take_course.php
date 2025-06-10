<?php
require_once '../php/init_session.php';
include '../php/db.php';
//redirect
if (!isset($_SESSION['user_id'])) {
    echo "Session not set. Redirecting to login.";
    header("Location: login.php");
    exit;
}

// Handle course enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $courseId = intval($_POST['course_id']);
    $userId = $_SESSION['user_id'];

    try {
        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT 1 FROM student_enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$userId, $courseId]);
        
        if (!$stmt->fetch()) {
            // Enroll the student
            $stmt = $pdo->prepare("INSERT INTO student_enrollments (student_id, course_id) VALUES (?, ?)");
            $stmt->execute([$userId, $courseId]);
            $_SESSION['success'] = "Successfully enrolled in the course!";
        } else {
            $_SESSION['error'] = "You are already enrolled in this course.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    header("Location: take_course.php");
    exit;
}

// Get available courses (not already enrolled)
try {
    $stmt = $pdo->prepare("
        SELECT c.course_id, c.course_name,  
               COUNT(se.student_id) AS enrolled_students,
               u.username AS teacher_name
        FROM courses c
        LEFT JOIN teacher_courses tc ON c.course_id = tc.course_id
        LEFT JOIN users u ON tc.teacher_id = u.id
        LEFT JOIN student_enrollments se ON c.course_id = se.course_id
        WHERE c.course_id NOT IN (
            SELECT course_id FROM student_enrollments WHERE student_id = ?
        )
        GROUP BY c.course_id
        ORDER BY c.course_name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $availableCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Courses</title>
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
                <li><a href="../php/logout.php">Logout</a></li>
            </ul>
        </nav>
        </div>
        <p class="logged-in-user">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></p>
    </header>

    <main>
        <h1>Available Courses</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="course-grid">
            <?php if (empty($availableCourses)): ?>
                <p>No available courses at the moment.</p><br><br>
            <?php else: ?>
                <?php foreach ($availableCourses as $course): ?>
                    <section>
                    <div class="course-card">
                        <h3><?= htmlspecialchars($course['course_name']) ?></h3>
                        <div class="course-meta">
                            <span>Teacher: <?= htmlspecialchars($course['teacher_name'] ?? 'Unknown') ?></span>
                            <span>Students: <?= $course['enrolled_students'] ?></span>
                        </div>
                        <form method="POST" action="take_course.php">
                            <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                            <button type="submit" class="enroll-btn">Enroll</button>
                        </form>
                    </div>
                </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a href="#top" class="back-to-top">⬆ Back to Top</a>
    </main>
    <footer>
        <p>&copy; 2025 Learning Management System. All rights reserved.</p>

    </footer>
</body>
</html>