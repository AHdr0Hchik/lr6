<?php
require_once __DIR__ . '/db.php';

function create_alert(PDO $db, int $patient_id, int $device_id, string $type, string $level, string $message): void {
    $stmt = $db->prepare("
      INSERT INTO alerts (patient_id, device_id, type, level, message)
      VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$patient_id, $device_id, $type, $level, $message]);
}

function process_glucose_alerts(PDO $db, int $patient_id, int $device_id, int $glucose): void {
    if ($glucose < 54) {
        create_alert($db, $patient_id, $device_id, 'severe_hypo', 'critical',
            "Тяжёлая гипогликемия: {$glucose} mg/dL");
    } elseif ($glucose < 70) {
        create_alert($db, $patient_id, $device_id, 'hypo', 'warning',
            "Гипогликемия: {$glucose} mg/dL");
    } elseif ($glucose > 250) {
        create_alert($db, $patient_id, $device_id, 'hyper', 'warning',
            "Гипергликемия: {$glucose} mg/dL");
    }
}