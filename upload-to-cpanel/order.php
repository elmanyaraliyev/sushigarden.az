<?php
// Sushi Garden — sifariş qəbulu (səbətdən AJAX ilə çağırılır).
require_once __DIR__ . '/includes/customer_auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'errors' => ['Yanlış sorğu üsulu.']]);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    $body = $_POST; // fallback — adi form-post kimi göndərilsə
}

if (!sg_csrf_check($body['csrf'] ?? '')) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'errors' => ['Səhifə köhnəlib, zəhmət olmasa səhifəni yeniləyib yenidən cəhd edin.']]);
    exit;
}

$items = is_array($body['items'] ?? null) ? $body['items'] : [];
$customer = sg_current_customer();

$result = sg_create_order([
    'name' => $body['name'] ?? '',
    'phone' => $body['phone'] ?? '',
    'service_type' => $body['service_type'] ?? '',
    'address' => $body['address'] ?? '',
    'tip' => $body['tip'] ?? 0,
    'notes' => $body['notes'] ?? '',
    'party_size' => $body['party_size'] ?? '',
    'requested_time' => $body['requested_time'] ?? 'asap',
    'customer_id' => $customer ? $customer['id'] : null,
], $items);

if (!$result['ok']) {
    http_response_code(422);
}
echo json_encode($result, JSON_UNESCAPED_UNICODE);
