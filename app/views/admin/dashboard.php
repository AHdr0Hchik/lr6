<?php
$db = db_connect();

$hospitals_count = (int)$db->query("SELECT COUNT(*) FROM hospitals")->fetchColumn();
$users_count     = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$patients_count  = (int)$db->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$devices_count   = (int)$db->query("SELECT COUNT(*) FROM devices")->fetchColumn();
$alerts_count    = (int)$db->query("SELECT COUNT(*) FROM alerts")->fetchColumn();
?>
<h1>Панель администратора</h1>

<div class="row mb-4">
  <div class="col-md-2 mb-3">
    <div class="card text-bg-primary">
      <div class="card-body">
        <h5 class="card-title">Больницы</h5>
        <p class="card-text fs-3"><?= $hospitals_count ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-2 mb-3">
    <div class="card text-bg-secondary">
      <div class="card-body">
        <h5 class="card-title">Пользователи</h5>
        <p class="card-text fs-3"><?= $users_count ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-2 mb-3">
    <div class="card text-bg-success">
      <div class="card-body">
        <h5 class="card-title">Пациенты</h5>
        <p class="card-text fs-3"><?= $patients_count ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-2 mb-3">
    <div class="card text-bg-info">
      <div class="card-body">
        <h5 class="card-title">Устройства</h5>
        <p class="card-text fs-3"><?= $devices_count ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-2 mb-3">
    <div class="card text-bg-danger">
      <div class="card-body">
        <h5 class="card-title">Алерты</h5>
        <p class="card-text fs-3"><?= $alerts_count ?></p>
      </div>
    </div>
  </div>
</div>

<div class="list-group">
  <a href="<?= BASE_URL ?>?r=admin/hospitals" class="list-group-item list-group-item-action">
    Управление больницами
  </a>
  <a href="<?= BASE_URL ?>?r=admin/users" class="list-group-item list-group-item-action">
    Управление пользователями (администраторы больниц)
  </a>
</div>