<?php
require_once 'init_session.php';
include 'db.php';

header('Content-Type: application/json');

// Decode the JSON request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['answer_id'])) {
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$answer_id = intval($data['answer_id']);

try {
    // Check if the selected answer is correct
    $stmt = $pdo->prepare("SELECT is_correct FROM quiz_answers WHERE answer_id = ?");
    $stmt->execute([$answer_id]);
    $is_correct = $stmt->fetchColumn();

    echo json_encode(['correct' => (bool)$is_correct]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}