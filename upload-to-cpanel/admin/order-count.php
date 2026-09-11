<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();

header('Content-Type: application/json; charset=utf-8');

$pdo = sg_db();
$latestId = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM orders')->fetchColumn();
$pendingIds = array_map('intval', $pdo->query("SELECT id FROM orders WHERE status = 'pending' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN));

echo json_encode(['latest_id' => $latestId, 'pending' => count($pendingIds), 'pending_ids' => $pendingIds]);
