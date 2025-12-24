<?php
$db   = db_connect();
$user = auth_user();

$stmt = $db->prepare("SELECT * FROM patients WHERE user_id = ?");
$stmt->execute([$user['id']]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo "<div class='alert alert-danger'>Профиль пациента не найден</div>";
    return;
}

$patient_id = (int)$patient['id'];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'add') {
        $email = trim($_POST['email'] ?? '');
        if (!$email) {
            $error = 'Введите e-mail доверенного лица';
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND role = 'trusted'");
            $stmt->execute([$email]);
            $u = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$u) {
                $error = 'Пользователь с таким e-mail и ролью доверенного лица не найден';
            } else {
                $trusted_id = (int)$u['id'];
                $stmt = $db->prepare("
                    SELECT id FROM trusted_relations
                    WHERE patient_id = ? AND trusted_user_id = ?
                ");
                $stmt->execute([$patient_id, $trusted_id]);
                if (!$stmt->fetch()) {
                    $stmt = $db->prepare("
                        INSERT INTO trusted_relations (patient_id, trusted_user_id)
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$patient_id, $trusted_id]);
                }
            }
        }
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $rel_id = (int)($_POST['rel_id'] ?? 0);
        $stmt = $db->prepare("
            DELETE FROM trusted_relations
            WHERE id = ? AND patient_id = ?
        ");
        $stmt->execute([$rel_id, $patient_id]);
    }
}

$stmt = $db->prepare("
    SELECT tr.*, u.email
    FROM trusted_relations tr
    JOIN users u ON tr.trusted_user_id = u.id
    WHERE tr.patient_id = ?
    ORDER BY tr.id DESC
");
$stmt->execute([$patient_id]);
$relations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Доверенные лица</h1>

<p>Вы можете дать доступ к своим данным близким людям.</p>

<div class="card mb-3">
  <div class="card-header">Добавить доверенное лицо</div>
  <div class="card-body">
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" class="row g-2">
      <input type="hidden" name="action" value="add">
      <div class="col-md-6">
        <label class="form-label">E-mail доверенного лица (роль trusted)</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Права</label>
        <input type="text" class="form-control" value="Просмотр данных, получение алертов" disabled>
      </div>
      <div class="col-md-2 mt-3">
        <button class="btn btn-primary w-100">Добавить</button>
      </div>
    </form>
  </div>
</div>

<h3>Текущие доверенные лица</h3>
<?php if ($relations): ?>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>E-mail</th>
        <th>Может просматривать</th>
        <th>Получает алерты</th>
        <th>Добавлен</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($relations as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['email']) ?></td>
        <td><?= $r['can_view_data'] ? 'Да' : 'Нет' ?></td>
        <td><?= $r['can_receive_alerts'] ? 'Да' : 'Нет' ?></td>
        <td><?= $r['created_at'] ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Удалить доверенное лицо?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="rel_id" value="<?= $r['id'] ?>">
            <button class="btn btn-sm btn-outline-danger">Удалить</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <div class="alert alert-info">У вас пока нет доверенных лиц.</div>
<?php endif; ?>