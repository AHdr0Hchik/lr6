<?php
require_once __DIR__ . '/../app/lib/db.php';
require_once __DIR__ . '/../app/lib/alerts.php';
require_once __DIR__ . '/../config/config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_json']);
    exit;
}

if (($input['api_key'] ?? '') !== API_SHARED_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$serial  = $input['serial'] ?? null;
$glucose = (int)($input['glucose'] ?? 0);
$trend   = isset($input['trend']) ? (float)$input['trend'] : null;
$bat     = isset($input['battery']) ? (int)$input['battery'] : null;
$ts      = $input['timestamp'] ?? null;

if (!$serial || !$ts || $glucose < 20 || $glucose > 600) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_data']);
    exit;
}

$db = db_connect();
$db->beginTransaction();

// Ищем устройство
$stmt = $db->prepare("SELECT id, patient_id FROM devices WHERE serial = ?");
$stmt->execute([$serial]);
$device = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$device) {
    $stmt = $db->prepare("INSERT INTO devices (serial, status) VALUES (?, 'active')");
    $stmt->execute([$serial]);
    $device_id = (int)$db->lastInsertId();
    $patient_id = null;
} else {
    $device_id = (int)$device['id'];
    $patient_id = $device['patient_id'] ? (int)$device['patient_id'] : null;
}

$stmt = $db->prepare("
  INSERT INTO sensor_readings (device_id, ts, glucose_mg_dl, trend, raw_payload)
  VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([
    $device_id,
    gmdate('Y-m-d H:i:s', strtotime($ts)),
    $glucose,
    $trend,
    json_encode($input, JSON_UNESCAPED_UNICODE)
]);

$stmt = $db->prepare("
  UPDATE devices
  SET `last_value` = ?, `last_timestamp` = ?, `battery_level` = ?
  WHERE `id` = ?
");
$stmt->execute([
    $glucose,
    gmdate('Y-m-d H:i:s', strtotime($ts)),
    $bat,
    $device_id
]);

if ($patient_id) {
    process_glucose_alerts($db, $patient_id, $device_id, $glucose);
}

$db->commit();

echo json_encode(['status' => 'ok']);