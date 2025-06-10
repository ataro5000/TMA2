<?php

require_once '../php/init_session.php';
include '../php/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    echo "Session not set. Redirecting to login.";
    header("Location: login.php");
    exit;
}

// Validate and sanitize GET parameters
if (!isset($_GET['unit_id']) || !isset($_GET['title']) || !isset($_GET['course_id'])) {
    die("Missing required parameters: unit_id, title, or course_id.");
}

$unit_id = filter_var($_GET['unit_id'], FILTER_VALIDATE_INT);
$title = filter_var($_GET['title']);
$course_id = filter_var($_GET['course_id'], FILTER_VALIDATE_INT);

$is_owner = false;
if ($_SESSION['role'] === 'teacher') {
    $stmt = $pdo->prepare("SELECT 1 FROM teacher_courses WHERE teacher_id = ? AND course_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $is_owner = (bool)$stmt->fetchColumn();
}

if (!$unit_id || !$title || !$course_id) {
    die("Invalid parameters: unit_id, title, or course_id.");
}

try {
    // Fetch all content with the same title, unit_id, and course_id then orders them based on content_order
    $stmt = $pdo->prepare("

        SELECT 
            co.content_id,
            co.title, 
            co.content_info,
            co.content_type,
            co.content_order,
            u.unit_id,
            u.course_id,
            u.unit_name,
            up.completed_at
        FROM content co
        JOIN units u ON co.unit_id = u.unit_id
        LEFT JOIN user_progress up ON co.content_id = up.content_id AND up.user_id = ?
        WHERE co.unit_id = ? AND u.course_id = ? AND co.title = ?
        ORDER BY co.content_order ASC, co.content_id ASC

    ");
    $stmt->execute([$_SESSION['user_id'], $unit_id, $course_id, $title]);
    $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($contents)) {
        die("No content found for the specified title, unit_id, and course_id.");
    }

    // Fetch all titles for the unit to determine navigation
    $stmt = $pdo->prepare("

        SELECT DISTINCT c.title
        FROM content c
        JOIN units u ON c.unit_id = u.unit_id
        WHERE c.unit_id = ? AND u.course_id = ?
        ORDER BY c.title ASC

    ");
    $stmt->execute([$unit_id, $course_id]);
    $titles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Find the index of the current title
    $current_index = array_search($title, $titles);
    $prev_title = $current_index > 0 ? $titles[$current_index - 1] : null;
    $next_title = $current_index < count($titles) - 1 ? $titles[$current_index + 1] : null;

    // Fetch unit name for display
    $unit_name = $contents[0]['unit_name'];
    $is_completed = !empty($contents[0]['completed_at']);
} catch (PDOException $e) {
    die("An error occurred while loading the content: " . $e->getMessage());
}
// Handle lesson editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit'])) {
    $edit_content_id = intval($_POST['edit_content_id']);
    $edit_content_info = $_POST['edit_content_info'];
    $stmt = $pdo->prepare("UPDATE content SET content_info = ? WHERE content_id = ?");
    $stmt->execute([$edit_content_info, $edit_content_id]);
    // Optionally: reload the page to show the updated content
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
//handle quiz editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_quiz_edit'])) {
    $edit_content_id = intval($_POST['edit_content_id']);
    $edit_quiz_question = $_POST['edit_quiz_question'];
    $answer_ids = $_POST['answer_ids'];
    $answer_texts = $_POST['answer_texts'];
    $correct_index = intval($_POST['correct_answer']);

    // Update question
    $stmt = $pdo->prepare("UPDATE content SET content_info = ? WHERE content_id = ?");
    $stmt->execute([$edit_quiz_question, $edit_content_id]);

    // Update answers
    foreach ($answer_ids as $i => $answer_id) {
        $is_correct = ($i == $correct_index) ? 1 : 0;
        if ($answer_id) {
            $stmt = $pdo->prepare("UPDATE quiz_answers SET answer_text = ?, is_correct = ? WHERE answer_id = ?");
            $stmt->execute([$answer_texts[$i], $is_correct, $answer_id]);
        }
    }
    // Optionally: reload the page to show the updated quiz
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
//teacher controls for moving content deleting content and editing content
function render_teacher_controls($content_item, $is_owner, $unit_id, $title, $course_id)
{
    if ($_SESSION['role'] === 'teacher' && $is_owner) {
        ob_start();
?>
        <div class="content-controls">
            <?php if ($content_item['content_type'] === 'lesson'): ?>
                <button class="edit-btn" onclick="showEditForm(<?= $content_item['content_id'] ?>)">Edit</button>
                <form method="POST" class="edit-form" id="edit-form-<?= $content_item['content_id'] ?>" style="display:none;">
                    <textarea name="edit_content_info" rows="5" cols="60"><?= htmlspecialchars($content_item['content_info']) ?></textarea><br>
                    <input type="hidden" name="edit_content_id" value="<?= $content_item['content_id'] ?>">
                    <input type="hidden" name="unit_id" value="<?= $unit_id ?>">
                    <input type="hidden" name="title" value="<?= htmlspecialchars($title) ?>">
                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
                    <button type="submit" name="save_edit">Save</button>
                    <button type="button" onclick="hideEditForm(<?= $content_item['content_id'] ?>)">Cancel</button>
                </form>
            <?php endif; ?>
            <button class="delete-btn"
                onclick="if (confirm('Warning: Deleting this will remove the entire course and all enrollments if it is the last content. Are you sure?')) {
            window.location.href = '../php/delete_content.php?content_id=<?= $content_item['content_id'] ?>&unit_id=<?= $unit_id ?>&title=<?= urlencode($title) ?>&course_id=<?= $course_id ?>';
        }">
                Delete
            </button>
            <?php if ($content_item['content_type'] !== 'quiz'): ?>
                <button class="move-content-btn" data-content-id="<?= $content_item['content_id'] ?>" data-direction="up">↑</button>
                <button class="move-content-btn" data-content-id="<?= $content_item['content_id'] ?>" data-direction="down">↓</button>
            <?php endif; ?>
        </div>
<?php
        return ob_get_clean();
    }
    return '';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="../../shared/includes.css">
    <link rel="stylesheet" href="../stylesheet/styles.css">
    <script src="../scripts/view_content.js" defer></script>
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
        <!-- Display Unit and Title only once at the top -->
        <h1><?= htmlspecialchars($unit_name) ?></h1>
        <!-- Display all chapter content only if there is content-->
        <?php
        $hasLesson = false;
        $hasQuiz = false;
        $hasMedia = false;

        foreach ($contents as $content_item) {
            if ($content_item['content_type'] === 'lesson') $hasLesson = true;
            if ($content_item['content_type'] === 'quiz') $hasQuiz = true;
            if (in_array($content_item['content_type'], ['picture', 'audio', 'video'])) $hasMedia = true;
        }
        ?>

        <!-- Lesson Section -->
        <?php if ($hasLesson): ?>
            <section class="content-container">
                <h2>Chapter: <?= htmlspecialchars($title) ?></h2>
                <?php foreach ($contents as $content_item): ?>
                    <?php if ($content_item['content_type'] === 'lesson'): ?>
                        <div class="content-item" id="content-item-<?= $content_item['content_id'] ?>">
                            <div class="content-body" id="content-body-<?= $content_item['content_id'] ?>">
                                <?= nl2br(htmlspecialchars($content_item['content_info'])) ?>
                            </div>
                            <?= render_teacher_controls($content_item, $is_owner, $unit_id, $title, $course_id) ?>
                        </div>
                    <?php elseif (in_array($content_item['content_type'], ['picture', 'audio', 'video'])): ?>
                        <div class="content-item">
                          
                            <?php if ($content_item['content_type'] === 'picture'): ?>
                                <img src="<?= htmlspecialchars($content_item['content_info']) ?>" alt="Embedded Picture" style="max-width: 100%; height: auto;">
                            <?php elseif ($content_item['content_type'] === 'audio'): ?>
                                <audio controls>
                                    <source src="<?= htmlspecialchars($content_item['content_info']) ?>" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                            <?php elseif ($content_item['content_type'] === 'video'): ?>
                                <?php if (preg_match('/^(http:\/\/|https:\/\/)/', $content_item['content_info'])): ?>
                                    <!-- Render as a link -->
                                    <a href="<?= htmlspecialchars($content_item['content_info']) ?>" target="_blank" rel="noopener">
                                        <?= htmlspecialchars($content_item['content_info']) ?>
                                    </a>
                                <?php else: ?>
                                    <!-- Embed the video -->
                                    <video controls style="max-width: 100%; height: auto;">
                                        <source src="<?= htmlspecialchars($content_item['content_info']) ?>" type="video/mp4">
                                        Your browser does not support the video element.
                                    </video>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?= render_teacher_controls($content_item, $is_owner, $unit_id, $title, $course_id) ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <!-- Quiz Section -->
        <?php if ($hasQuiz): ?>
            <section class="content-container">
                <h2>Chapter: <?= htmlspecialchars($title) ?> Questions</h2>
                <?php foreach ($contents as $content_item): ?>

                    <?php if ($content_item['content_type'] === 'quiz'): ?>
                        <!-- Quiz Content -->
                        <div class="quiz-item">
                            <p><strong>Question:</strong> <?= htmlspecialchars($content_item['content_info']) ?></p><br>
                            <form class="quiz-form" data-content-id="<?= $content_item['content_id'] ?>">
                                <ul>
                                    <?php
                                    // Fetch the quiz answers for this question
                                    $stmt = $pdo->prepare("

                                        SELECT 
                                            qa.answer_id,
                                            qa.answer_text,
                                            qa.is_correct
                                        FROM quiz_answers qa
                                        WHERE qa.content_id = ?

                                ");
                                    $stmt->execute([$content_item['content_id']]);
                                    $quiz_answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    // Shuffle the answers
                                    shuffle($quiz_answers);

                                    // Render the shuffled answers
                                    foreach ($quiz_answers as $answer): ?>
                                        <li>
                                            <label>
                                                <input type="radio" name="quiz_answer_<?= $content_item['content_id'] ?>" value="<?= $answer['answer_id'] ?>" required>
                                                <?= htmlspecialchars($answer['answer_text']) ?>
                                            </label>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <p class="quiz-feedback" style="display: none;"></p>
                            </form>
                            <?php if ($_SESSION['role'] === 'teacher' && $is_owner): ?>
                                <div class="content-controls">
                                    <button type="button" onclick="showEditQuizForm(<?= $content_item['content_id'] ?>)">Edit</button>
                                    <a href="../php/delete_content.php?content_id=<?= $content_item['content_id'] ?>&unit_id=<?= $unit_id ?>&title=<?= urlencode($title) ?>&course_id=<?= $course_id ?>"
                                        onclick="return confirm('Warning: Deleting this will remove the entire course and all enrollments if it is the last content. Are you sure?');">
                                        Delete </a>
                                </div>
                                <form method="POST" class="edit-quiz-form" id="edit-quiz-form-<?= $content_item['content_id'] ?>" style="display:none;">
                                    <label>Question:<br>
                                        <textarea name="edit_quiz_question" rows="2" cols="60"><?= htmlspecialchars($content_item['content_info']) ?></textarea>
                                    </label>
                                    <br>
                                    <?php
                                    // Fetch answers for this quiz
                                    $stmt = $pdo->prepare("SELECT answer_id, answer_text, is_correct FROM quiz_answers WHERE content_id = ? ORDER BY answer_id ASC");
                                    $stmt->execute([$content_item['content_id']]);
                                    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    for ($i = 0; $i < 4; $i++):
                                        $answer = $answers[$i] ?? ['answer_id' => '', 'answer_text' => '', 'is_correct' => 0];
                                    ?>
                                        <div>
                                            <input type="hidden" name="answer_ids[]" value="<?= htmlspecialchars($answer['answer_id']) ?>">
                                            <input type="text" name="answer_texts[]" value="<?= htmlspecialchars($answer['answer_text']) ?>" placeholder="Answer <?= $i + 1 ?>" required>
                                            <label>
                                                <input type="radio" name="correct_answer" value="<?= $i ?>" <?= $answer['is_correct'] ? 'checked' : '' ?>> Correct
                                            </label>
                                        </div>
                                    <?php endfor; ?>
                                    <input type="hidden" name="edit_content_id" value="<?= $content_item['content_id'] ?>">
                                    <button type="submit" name="save_quiz_edit">Save</button>
                                    <button type="button" onclick="hideEditQuizForm(<?= $content_item['content_id'] ?>)">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </div>

                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <!-- Mark Complete Button -->
        <div class="mark-complete-container">
            <?php
            $allCompleted = true;
            foreach ($contents as $content_item) {
                if (empty($content_item['completed_at'])) {
                    $allCompleted = false;
                    break;
                }
            }
            ?>

            <?php if ($allCompleted): ?>
                <button class="mark-complete-btn completed" disabled>Completed</button>
            <?php else: ?>
                <button class="mark-complete-btn" id="markCompleteBtn"
                    data-content-ids='<?= json_encode(array_column($contents, "content_id")) ?>'>Complete?</button>
            <?php endif; ?>
        </div>
        <!-- Content Navigation -->
        <div class="content-navigation">
            <?php if ($prev_title): ?>
                <a href="view_content.php?unit_id=<?= urlencode($unit_id) ?>&title=<?= urlencode($prev_title) ?>&course_id=<?= urlencode($course_id) ?>">←</a>
            <?php else: ?>
                <a class="disabled">←</a>
            <?php endif; ?>

            <a href="course.php?id=<?= $course_id ?>">Back</a>

            <?php if ($next_title): ?>
                <a href="view_content.php?unit_id=<?= urlencode($unit_id) ?>&title=<?= urlencode($next_title) ?>&course_id=<?= urlencode($course_id) ?>">→</a>
            <?php else: ?>
                <a class="disabled">→</a>
            <?php endif; ?>
        </div>
    </main>
    <a href="#top" class="back-to-top">⬆ Back to Top</a>
    <footer>
        <p>&copy; 2025 Learning Management System. All rights reserved.</p>
    </footer>
</body>

</html>