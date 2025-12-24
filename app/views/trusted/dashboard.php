<?php
$db   = db_connect();
$user = auth_user();
$trusted_user_id = $user['id'];

$stmt = $db->prepare("
    SELECT
      p.id AS patient_id,
      p.full_name,
      u.email AS patient_email,
      (SELECT d.last_value FROM devices d WHERE d.patient_id = p.id ORDER BY d.last_timestamp DESC LIMIT 1) AS lastValue,
      (SELECT d.last_timestamp FROM devices d WHERE d.patient_id = p.id ORDER BY d.last_timestamp DESC LIMIT 1) AS last_ts,
      (SELECT COUNT(*) FROM alerts a WHERE a.patient_id = p.id AND a.status = 'new') AS new_alerts
    FROM trusted_relations tr
    JOIN patients p ON tr.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE tr.trusted_user_id = ?
    ORDER BY p.id DESC
");
$stmt->execute([$trusted_user_id]);
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Доверенные пациенты</h1>

<?php if ($patients): ?>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Пациент</th>
        <th>E-mail</th>
        <th>Последняя глюкоза</th>
        <th>Время</th>
        <th>Новых алертов</th>
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
        <td><?= htmlspecialchars($p['full_name']) ?></td>
        <td><?= htmlspecialchars($p['patient_email']) ?></td>
        <td class="<?= $class ?>"><?= $value !== null ? $value . ' mg/dL' : '-' ?></td>
        <td><?= $p['last_ts'] ?: '-' ?></td>
        <td><?= $p['new_alerts'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <div class="alert alert-info">
    Пока нет пациентов, которые дали вам доступ. Пациент может добавить вас в разделе
    «Доверенные лица», если у вас роль <code>trusted</code>.
  </div>
<?php endif; ?>