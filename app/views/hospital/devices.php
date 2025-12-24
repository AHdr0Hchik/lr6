<?php
$db = db_connect();
$user = auth_user();
$hospital_id = $user['hospital_id'];

// Список пациентов больницы
$stmt = $db->prepare("SELECT p.id, p.full_name FROM patients p WHERE p.hospital_id = ?");
$stmt->execute([$hospital_id]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка формы привязки
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serial = trim($_POST['serial'] ?? '');
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    if ($serial && $patient_id) {
        // есть ли такое устройство?
        $stmt = $db->prepare("SELECT id FROM devices WHERE serial = ?");
        $stmt->execute([$serial]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($device) {
            $stmt = $db->prepare("UPDATE devices SET patient_id = ? WHERE id = ?");
            $stmt->execute([$patient_id, $device['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO devices (serial, patient_id) VALUES (?, ?)");
            $stmt->execute([$serial, $patient_id]);
        }
    }
}

// Список устройств больницы (по пациентам)
$stmt = $db->prepare("
  SELECT d.*, p.full_name
  FROM devices d
  LEFT JOIN patients p ON d.patient_id = p.id
  WHERE p.hospital_id = ?
");
$stmt->execute([$hospital_id]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Устройства</h1>
<form method="post" class="row g-2 mb-3">
  <div class="col-md-3">
    <input type="text" name="serial" class="form-control" placeholder="Серийный номер" required>
  </div>
  <div class="col-md-4">
    <select name="patient_id" class="form-select" required>
      <option value="">Выберите пациента</option>
      <?php foreach ($patients as $p): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Привязать</button>
  </div>
</form>

<table class="table table-striped">
  <thead><tr><th>Serial</th><th>Пациент</th><th>Последнее значение</th><th>Время</th></tr></thead>
  <tbody>
  <?php foreach ($devices as $d): ?>
    <tr>
      <td><?= htmlspecialchars($d['serial']) ?></td>
      <td><?= htmlspecialchars($d['full_name'] ?? '-') ?></td>
      <td><?= $d['last_value'] ?></td>
      <td><?= $d['last_timestamp'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
