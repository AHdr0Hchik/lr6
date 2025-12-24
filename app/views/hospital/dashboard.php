<?php
$db   = db_connect();
$user = auth_user();
$hospital_id = $user['hospital_id'];

$stmt = $db->prepare("SELECT COUNT(*) FROM patients WHERE hospital_id = ?");
$stmt->execute([$hospital_id]);
$patients_count = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE hospital_id = ? AND role = 'doctor'");
$stmt->execute([$hospital_id]);
$doctors_count = (int)$stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM devices d
    JOIN patients p ON d.patient_id = p.id
    WHERE p.hospital_id = ?
");
$stmt->execute([$hospital_id]);
$devices_count = (int)$stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM alerts a
    JOIN patients p ON a.patient_id = p.id
    WHERE p.hospital_id = ?
");
$stmt->execute([$hospital_id]);
$alerts_count = (int)$stmt->fetchColumn();
?>

<h1>Панель больницы</h1>

<div class="row mb-4">
  <div class="col-md-3 mb-3">
    <div class="card text-bg-success">
      <div class="card-body">
        <h5 class="card-title">Пациенты</h5>
        <p class="card-text fs-3"><?= $patients_count ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card text-bg-primary">
      <div class="card-body">
        <h5 class="card-title">Врачи</h5>
        <p class="card-text fs-3"><?= $doctors_count ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card text-bg-info">
      <div class="card-body">
        <h5 class="card-title">Устройства</h5>
        <p class="card-text fs-3"><?= $devices_count ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3 mb-3">
    <div class="card text-bg-danger">
      <div class="card-body">
        <h5 class="card-title">Алерты</h5>
        <p class="card-text fs-3"><?= $alerts_count ?></p>
      </div>
    </div>
  </div>
</div>

<div class="list-group">
  <a href="<?= BASE_URL ?>?r=hospital/doctors" class="list-group-item list-group-item-action">
    Управление врачами и доверенными пользователями
  </a>
  <a href="<?= BASE_URL ?>?r=hospital/patients" class="list-group-item list-group-item-action">
    Управление пациентами
  </a>
  <a href="<?= BASE_URL ?>?r=hospital/devices" class="list-group-item list-group-item-action">
    Управление устройствами
  </a>
</div>