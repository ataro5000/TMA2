<?php
//Multi-step form for adding courses, units, and content
// Session-based authentication check, generates content automatically for lessons, Uses (ON DUPLICATE KEY UPDATE) to handle existing course/unit names, a few edge cases made this necessary.
require_once '../php/init_session.php';
include '../php/db.php';


//redirect
if (!isset($_SESSION['user_id'])) {
    echo "Session not set. Redirecting to login.";
    header("Location: login.php");
    exit;
}


if ($_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit;
}

// Fetch existing courses
$stmt = $pdo->prepare("
    SELECT c.course_id, c.course_name 
    FROM courses c
    JOIN teacher_courses tc ON c.course_id = tc.course_id
    WHERE tc.teacher_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = isset($_POST['step']) ? intval($_POST['step']) : 1; // Default to step 1
    $courseId = isset($_POST['course_id']) ? intval($_POST['course_id']) : null;
    $newCourseName = isset($_POST['new_course_name']) ? trim($_POST['new_course_name']) : '';
    $unitId = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : null;
    $newUnitName = isset($_POST['new_unit_name']) ? trim($_POST['new_unit_name']) : '';
    $contentType = isset($_POST['content_type']) ? trim($_POST['content_type']) : '';
    $contentTitle = isset($_POST['title']) ? trim($_POST['title']) : '';
    $contentInfo = isset($_POST['content_info']) ? trim($_POST['content_info']) : '';

    try {
        if ($step === 1) {
            // Step 1: Add or select a course
            if (!empty($newCourseName)) {
                // Check if the course name already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE course_name = ?");
                $stmt->execute([$newCourseName]);
                $courseExists = $stmt->fetchColumn() > 0;
        
                if ($courseExists) {
                    throw new Exception("The course name already exists. Please select it from the dropdown or use a different name.");
                }
        
                $pdo->beginTransaction();
                try {
                    // Insert course
                    $stmt = $pdo->prepare("
                        INSERT INTO courses (course_name) 
                        VALUES (:course_name) 
                        ON DUPLICATE KEY UPDATE course_id=LAST_INSERT_ID(course_id)
                    ");
                    $stmt->execute(['course_name' => $newCourseName]);
                    $courseId = $pdo->lastInsertId();
        
                    // Automatically assign the teacher to the course
                    $stmt = $pdo->prepare("
                        INSERT INTO teacher_courses (teacher_id, course_id)
                        VALUES (:teacher_id, :course_id)
                        ON DUPLICATE KEY UPDATE teacher_id = teacher_id
                    ");
                    $stmt->execute([
                        'teacher_id' => $_SESSION['user_id'],
                        'course_id' => $courseId
                    ]);
        
                    // Automatically enroll the teacher as a student in the course
                    $stmt = $pdo->prepare("
                        INSERT INTO student_enrollments (student_id, course_id)
                        VALUES (:student_id, :course_id)
                        ON DUPLICATE KEY UPDATE student_id = student_id
                    ");
                    $stmt->execute([
                        'student_id' => $_SESSION['user_id'],
                        'course_id' => $courseId
                    ]);
        
                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
            $step = 2; // Proceed to step 2
            $success = "Course selected successfully!";
        
        } elseif ($step === 2) {
            // Step 2: Add or select a unit
            if (!empty($newUnitName)) {
                $stmt = $pdo->prepare("INSERT INTO units (unit_name, course_id) VALUES (:unit_name, :course_id) ON DUPLICATE KEY UPDATE unit_id=LAST_INSERT_ID(unit_id)");
                $stmt->execute(['unit_name' => $newUnitName, 'course_id' => $courseId]);
                $unitId = $pdo->lastInsertId();
            }
            $step = 3; // Proceed to step 3
            $success = "Unit selected successfully!";
        } elseif ($step === 3) {
            // Step 3: Add content
            $existingTitle = isset($_POST['existing_title']) ? trim($_POST['existing_title']) : '';
            $newTitle = isset($_POST['new_title']) ? trim($_POST['new_title']) : '';
            $contentInfo = isset($_POST['content_info']) ? trim($_POST['content_info']) : '';
            $contentType = isset($_POST['content_type']) ? trim($_POST['content_type']) : '';

            if (empty($existingTitle) && empty($newTitle)) {
                throw new Exception("You must select an existing title or add a new one.");
            }

            if (!empty($existingTitle) && !empty($newTitle)) {
                throw new Exception("You can only select an existing title or add a new one, not both.");
            }

            $contentTitle = !empty($existingTitle) ? $existingTitle : $newTitle;

            // Check if the title already exists for the unit
            $stmt = $pdo->prepare("SELECT MAX(content_order) FROM content WHERE unit_id = ? AND title = ?");
            $stmt->execute([$unitId, $contentTitle]);
            $maxOrder = $stmt->fetchColumn();
            $newOrder = ($maxOrder !== null) ? $maxOrder + 1 : 1;

            // Check if the title exists for this unit
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM content WHERE unit_id = ? AND title = ?");
            $stmt->execute([$unitId, $contentTitle]);
            $titleExists = $stmt->fetchColumn() > 0;

            if ($titleExists && empty($existingTitle)) {
                throw new Exception("The title already exists. Please select it from the dropdown.");
            }


            if ($contentType === 'lesson') {
                // Handle lesson content
                $contentInfo = isset($_POST['content_info']) ? trim($_POST['content_info']) : '';
                if (empty($contentInfo)) {
                    throw new Exception("Lesson content cannot be empty.");
                }

                $stmt = $pdo->prepare("INSERT INTO content (unit_id, content_type, title, content_info, content_order) VALUES (:unit_id, :content_type, :title, :content_info, :content_order)");
                $stmt->execute([
                    'unit_id' => $unitId,
                    'content_type' => $contentType,
                    'title' => $contentTitle,
                    'content_info' => $contentInfo,
                    'content_order' => $newOrder
                ]);

                $success = "Lesson added successfully!";
            } elseif ($contentType === 'quiz') {
                // Handle quiz content
                $quizQuestion = isset($_POST['quiz_question']) ? trim($_POST['quiz_question']) : '';
                $quizAnswers = isset($_POST['quiz_answers']) ? $_POST['quiz_answers'] : [];

                if (empty($quizQuestion) || count($quizAnswers) !== 4) {
                    throw new Exception("Quiz must have a question and exactly 4 answers.");
                }

                // Insert quiz question as content
                $stmt = $pdo->prepare("INSERT INTO content (unit_id, content_type, title, content_info) VALUES (:unit_id, :content_type, :title, :content_info)");
                $stmt->execute([
                    'unit_id' => $unitId,
                    'content_type' => $contentType,
                    'title' => $contentTitle,
                    'content_info' => $quizQuestion
                ]);
                $contentId = $pdo->lastInsertId();

                // Insert quiz answers
                foreach ($quizAnswers as $index => $answer) {
                    $isCorrect = ($index === 0) ? 1 : 0; // First answer is correct
                    $stmt = $pdo->prepare("INSERT INTO quiz_answers (content_id, answer_text, is_correct) VALUES (:content_id, :answer_text, :is_correct)");
                    $stmt->execute([
                        'content_id' => $contentId,
                        'answer_text' => $answer,
                        'is_correct' => $isCorrect
                    ]);
                }

                $success = "Quiz added successfully!";
            } elseif ($contentType === 'video' || $contentType === 'picture' || $contentType === 'audio') {
                // Handle media upload or URL
                $mediaUrl = isset($_POST['media_url']) ? trim($_POST['media_url']) : '';

                if (!empty($mediaUrl)) {
                    // Use the provided URL
                    $mediaPath = $mediaUrl;
                } else {
                    // Handle file upload
                    $uploadDir = '../uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $mediaFile = $_FILES['media_file'] ?? null;
                    if (!$mediaFile || $mediaFile['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("File upload failed and no URL provided.");
                    }

                    // Validate file type
                    $allowedMimeTypes = [
                        'video' => ['video/mp4', 'video/webm', 'video/ogg'],
                        'picture' => ['image/jpeg', 'image/png', 'image/gif'],
                        'audio' => ['audio/mpeg', 'audio/ogg', 'audio/wav']
                    ];

                    $detectedMimeType = mime_content_type($mediaFile['tmp_name']);
                    if (!in_array($detectedMimeType, $allowedMimeTypes[$contentType])) {
                        throw new Exception("Invalid file type for $contentType.");
                    }

                    // Save file
                    $filename = uniqid('media_') . '_' . basename($mediaFile['name']);
                    $destination = $uploadDir . $filename;
                    if (!move_uploaded_file($mediaFile['tmp_name'], $destination)) {
                        throw new Exception("Failed to save file.");
                    }
                    $mediaPath = $destination;
                }

                // Insert into database
                $stmt = $pdo->prepare("INSERT INTO content (unit_id, content_type, title, content_info, content_order) VALUES (:unit_id, :content_type, :title, :content_info, :content_order)");
                $stmt->execute([
                    'unit_id' => $unitId,
                    'content_type' => $contentType,
                    'title' => $contentTitle,
                    'content_info' => $mediaPath,
                    'content_order' => $newOrder
                ]);

                $success = ucfirst($contentType) . " added successfully!";
            } else {
                throw new Exception("Invalid content type.");
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Content</title>
    <link rel="stylesheet" href="../../shared/includes.css">
    <link rel="stylesheet" href="../stylesheet/styles.css">
    <script src="../scripts/add_content.js" defer></script>
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
                    <li><a href="take_course.php">Take Course</a></li>
                    <li><a href="../php/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
        <p class="logged-in-user">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></p>
    </header>

    <main>

        <h1>Add New Content</h1>
        <section class="add-content">
            <?php if (isset($error)): ?>
                <p style="color: red;"><?= htmlspecialchars($error) ?></p>
            <?php elseif (isset($success)): ?>
                <p style="color: green;"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <!-- Step 1: Select or Add Course -->
            <?php if (!isset($step) || $step === 1): ?>
                <form id="course-form" method="POST" action="">
                    <input type="hidden" name="step" value="1">
                    <label for="course_id">Course:</label>
                    <select id="course_id" name="course_id" onchange="toggleCourseFields()">
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= htmlspecialchars($course['course_id']) ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                        <?php endforeach; ?>
                    </select><br>
                    <label for="new_course_name">Or Add New Course:</label>
                    <input type="text" id="new_course_name" name="new_course_name" oninput="toggleCourseFields()">
                    <button type="submit">Next</button>
                </form>
            <?php endif; ?>

            <!-- Step 2: Select or Add Unit -->
            <?php if (isset($step) && $step === 2): ?>
                <form id="unit-form" method="POST" action="">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="course_id" value="<?= htmlspecialchars($courseId) ?>">
                    <label for="unit_id">Unit:</label>
                    <select id="unit_id" name="unit_id" onchange="toggleUnitFields()">
                        <option value="">Select Unit</option>
                        <?php
                        $units = $pdo->prepare("SELECT unit_id, unit_name FROM units WHERE course_id = ?");
                        $units->execute([$courseId]);
                        foreach ($units->fetchAll(PDO::FETCH_ASSOC) as $unit): ?>
                            <option value="<?= htmlspecialchars($unit['unit_id']) ?>"><?= htmlspecialchars($unit['unit_name']) ?></option>
                        <?php endforeach; ?>
                    </select><br>
                    <label for="new_unit_name">Or Add New Unit:</label>
                    <input type="text" id="new_unit_name" name="new_unit_name" oninput="toggleUnitFields()">
                    <button type="submit">Next</button>
                </form>
            <?php endif; ?>

            <!-- Step 3: Add Content -->
            <?php if (isset($step) && $step === 3): ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="unit_id" value="<?= htmlspecialchars($unitId) ?>">

                    <label for="content_type">Content Type That Is Being Added:</label>
                    <select id="content_type" name="content_type" required onchange="toggleContentFields()">
                        <option value="">Select Type</option>
                        <option value="lesson">Written Lesson</option>
                        <option value="quiz">Multiple Choice Quiz</option>
                        <option value="video">Video Lesson</option>
                        <option value="picture">Picture Aids</option>
                        <option value="audio">Audio File</option>
                    </select><br>

                    <label for="existing_title">Select Existing Chapter:</label>
                    <select id="existing_title" name="existing_title" onchange="toggleTitleFields()">
                        <option value="">Select Title</option>
                        <?php
                        $titles = $pdo->prepare("SELECT DISTINCT title FROM content WHERE unit_id = ?");
                        $titles->execute([$unitId]);
                        foreach ($titles->fetchAll(PDO::FETCH_ASSOC) as $title): ?>
                            <option value="<?= htmlspecialchars($title['title']) ?>"><?= htmlspecialchars($title['title']) ?></option>
                        <?php endforeach; ?>
                    </select><br>

                    <label for="new_title">Or Add New Chapter:</label>
                    <input type="text" id="new_title" name="new_title" oninput="toggleTitleFields()"><br>

                    <!-- Written Lesson Fields -->
                    <div id="lessonFields" style="display: none;">
                        <label for="content_info">Content for Chapter:</label><br>
                        <textarea id="content_info" name="content_info"></textarea><br>
                    </div>

                    <!-- Multiple Choice Quiz Fields -->
                    <div id="quizFields" style="display: none;">
                        <label for="quiz_question">Question:</label><br>
                        <input type="text" id="quiz_question" name="quiz_question"><br>

                        <label for="quiz_answer_1">Correct Answer:</label><br>
                        <input type="text" id="quiz_answer_1" name="quiz_answers[]"><br>

                        <label for="quiz_answer_2">Answer 2:</label><br>
                        <input type="text" id="quiz_answer_2" name="quiz_answers[]"><br>

                        <label for="quiz_answer_3">Answer 3:</label><br>
                        <input type="text" id="quiz_answer_3" name="quiz_answers[]"><br>

                        <label for="quiz_answer_4">Answer 4:</label><br>
                        <input type="text" id="quiz_answer_4" name="quiz_answers[]"><br>
                    </div>

                    <!-- Media Upload Field (Video, Picture, Audio) -->
                    <div id="mediaFields" style="display: none;">
                        <label for="media_file">Upload File:</label>
                        <input type="file" id="media_file" name="media_file"><br>
                        <label for="media_url">Or Enter Media URL:</label>
                        <input type="url" id="media_url" name="media_url" placeholder="https://..."><br>
                    </div>

                    <button type="submit">Add Content</button>
                </form>
            <?php endif; ?>
        </section>
        <a href="#top" class="back-to-top">⬆ Back to Top</a>
    </main>
    <footer>
        <p>&copy; 2025 Learning Management System. All rights reserved.</p>
    </footer>
</body>

</html>