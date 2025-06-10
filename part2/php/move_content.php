<?php
require_once 'init_session.php';
include 'db.php';

$content_id = intval($_POST['content_id']);
$direction = $_POST['direction']; // 'up' or 'down'

// Get current content info
$stmt = $pdo->prepare("SELECT unit_id, title, content_order FROM content WHERE content_id = ?");
$stmt->execute([$content_id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    echo json_encode(['success' => false, 'message' => 'Content not found']);
    exit;
}

$unit_id = $current['unit_id'];
$title = $current['title'];
$order = $current['content_order'];

// Find the neighbor content to swap with
$cmp = $direction === 'up' ? '<' : '>';
$order_by = $direction === 'up' ? 'DESC' : 'ASC';

$stmt = $pdo->prepare("
    SELECT content_id, content_order FROM content
    WHERE unit_id = ? AND title = ? AND content_order $cmp ?
    ORDER BY content_order $order_by
    LIMIT 1
");
$stmt->execute([$unit_id, $title, $order]);
$neighbor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$neighbor) {
    echo json_encode(['success' => false, 'message' => 'No neighbor to swap with']);
    exit;
}

// Swap the orders
$pdo->beginTransaction();
$stmt = $pdo->prepare("UPDATE content SET content_order = ? WHERE content_id = ?");
$stmt->execute([$neighbor['content_order'], $content_id]);
$stmt->execute([$order, $neighbor['content_id']]);
$pdo->commit();

echo json_encode(['success' => true]);