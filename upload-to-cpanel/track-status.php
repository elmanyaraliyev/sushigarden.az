<?php
// Sifariş izləmə səhifəsi üçün yüngül JSON polling endpoint-i.
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
$token = (string)($_GET['t'] ?? '');

if ($id <= 0 || $token === '') {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$order = sg_get_order($id);
if (!$order || empty($order['track_token']) || !hash_equals($order['track_token'], $token)) {
    http_response_code(404);
    echo json_encode(['ok' => false]);
    exit;
}

$items = array_map(function ($it) {
    return [
        'product_id' => $it['product_id'] !== null ? (int)$it['product_id'] : null,
        'name' => $it['name'],
        'price' => (float)$it['price'],
        'qty' => (int)$it['qty'],
    ];
}, $order['items']);

echo json_encode([
    'ok' => true,
    'status' => $order['status'],
    'status_label' => sg_order_status_label($order['status']),
    'items' => $items,
    'total' => (float)$order['total'],
    'created_at' => $order['created_at'],
    'reviewed' => (bool)sg_get_review($id),
], JSON_UNESCAPED_UNICODE);
