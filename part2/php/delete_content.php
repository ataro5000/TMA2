<?php
require_once 'init_session.php';
include 'db.php';

$content_id = intval($_GET['content_id']);
$unit_id = intval($_GET['unit_id']);
$title = $_GET['title'];
$course_id = intval($_GET['course_id']);

// Delete related user_progress entries
$stmt = $pdo->prepare("DELETE FROM user_progress WHERE content_id = ?");
$stmt->execute([$content_id]);

// Delete the content itself
$stmt = $pdo->prepare("DELETE FROM content WHERE content_id = ?");
$stmt->execute([$content_id]);

// Check if the unit is now empty
$stmt = $pdo->prepare("SELECT COUNT(*) FROM content WHERE unit_id = ?");
$stmt->execute([$unit_id]);
if ($stmt->fetchColumn() == 0) {
    // Delete the unit
    $stmt = $pdo->prepare("DELETE FROM units WHERE unit_id = ?");
    $stmt->execute([$unit_id]);
}

// Check if the course is now empty (no units)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM units WHERE course_id = ?");
$stmt->execute([$course_id]);
if ($stmt->fetchColumn() == 0) {
    // Delete enrollments and teacher links
    $stmt = $pdo->prepare("DELETE FROM student_enrollments WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $stmt = $pdo->prepare("DELETE FROM teacher_courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    // Delete the course
    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    header("Location: ../index.php");
    exit;
}

header("Location: ../html/course.php?id=$course_id");
exit;