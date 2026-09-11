<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();

header('Content-Type: application/json; charset=utf-8');

$pdo = sg_db();
$latestId = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM orders')->fetchColumn();
$pending = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

echo json_encode(['latest_id' => $latestId, 'pending' => $pending]);
