<?php
$db   = db_connect();
$user = auth_user();
$hospital_id = $user['hospital_id'];

$stmt = $db->prepare("
    SELECT p.id,
           p.full_name,
           u.email,
           (SELECT d.last_value FROM devices d WHERE d.patient_id = p.id ORDER BY d.last_timestamp DESC LIMIT 1) AS lastValue,
           (SELECT d.last_timestamp FROM devices d WHERE d.patient_id = p.id ORDER BY d.last_timestamp DESC LIMIT 1) AS last_ts,
           (SELECT COUNT(*) FROM alerts a WHERE a.patient_id = p.id AND a.status = 'new') AS new_alerts
    FROM patients p
    JOIN users u ON p.user_id = u.id
    WHERE p.hospital_id = ?
    ORDER BY p.id DESC
");
$stmt->execute([$hospital_id]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Пациенты</h1>

<table class="table table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>ФИО</th>
      <th>E-mail</th>
      <th>Последняя глюкоза</th>
      <th>Время</th>
      <th>Новых алертов</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($patients as $p): ?>
    <?php
      $value = $p['lastValue'];
      $class = '';
      if ($value !== null) {
          if ($value < 70 || $value > 250) {
              $class = 'text-danger fw-bold';
          } elseif ($value >= 70 && $value <= 180) {
              $class = 'text-success';
          } else {
              $class = 'text-warning';
          }
      }
    ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td><?= htmlspecialchars($p['full_name']) ?></td>
      <td><?= htmlspecialchars($p['email']) ?></td>
      <td class="<?= $class ?>"><?= $value !== null ? $value . ' mg/dL' : '-' ?></td>
      <td><?= $p['last_ts'] ?: '-' ?></td>
      <td><?= $p['new_alerts'] ?></td>
      <td>
        <a href="<?= BASE_URL ?>?r=doctor/patient&id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">
          Открыть
        </a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>