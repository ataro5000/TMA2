<?php
require_once 'init_session.php';
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id']) || !isset($_POST['content_ids'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $contentIds = json_decode($_POST['content_ids']);

    try {
        $pdo->beginTransaction();

        // Mark all content items as complete
        $placeholders = implode(',', array_fill(0, count($contentIds), '?'));
        $query = "INSERT IGNORE INTO user_progress (user_id, content_id, completed_at) 
                  VALUES " . implode(',', array_fill(0, count($contentIds), "(?, ?, NOW())"));
        
        $params = [];
        foreach ($contentIds as $contentId) {
            $params[] = $userId;
            $params[] = $contentId;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Get unit information for response
        $query = "SELECT unit_id FROM content WHERE content_id = ? LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$contentIds[0]]);
        $unit = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'All content items marked as complete',
            'unit_id' => $unit['unit_id'] ?? null
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error processing request: ' . $e->getMessage()
        ]);
    }
}