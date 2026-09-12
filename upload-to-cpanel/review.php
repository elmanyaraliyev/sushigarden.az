<?php
// Müştəri sifariş üçün rəy (1-5 ulduz + qeyd) buraxır. Sifarişin öz track_token-i
// ilə doğrulanır — ayrıca giriş tələb olunmur, link kifayətdir.
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Yanlış sorğu üsulu.']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) $body = $_POST;

$id = (int)($body['id'] ?? 0);
$token = (string)($body['t'] ?? '');
$rating = (int)($body['rating'] ?? 0);
$comment = (string)($body['comment'] ?? '');

$order = ($id > 0 && $token !== '') ? sg_get_order($id) : null;
if (!$order || empty($order['track_token']) || !hash_equals($order['track_token'], $token)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Sifariş tapılmadı.']);
    exit;
}
if ($order['status'] !== 'completed') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Yalnız tamamlanmış sifarişə rəy bildirə bilərsiniz.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Zəhmət olmasa 1-5 arası ulduz seçin.']);
    exit;
}
if (sg_get_review($id)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Bu sifariş üçün artıq rəy bildirilib.']);
    exit;
}

$saved = sg_save_review($id, $order['customer_id'], $rating, $comment);
echo json_encode(['ok' => $saved]);
