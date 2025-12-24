<?php
$db = db_connect();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $role        = $_POST['role'] ?? 'hospital';
    $hospital_id = $_POST['hospital_id'] ?? null;
    if ($role === 'admin') {
        $hospital_id = null;
    }

    if (!$email || !$password) {
        $error = 'Заполните e-mail и пароль';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Неверный формат e-mail';
    } else {
        // Проверка, что email ещё не занят
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким e-mail уже существует';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO users (email, password_hash, role, hospital_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$email, $password_hash, $role, $hospital_id ?: null]);
        }
    }
}

$hospitals = $db->query("SELECT id, name FROM hospitals ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$users = $db->query("
  SELECT u.*, h.name AS hospital_name
  FROM users u
  LEFT JOIN hospitals h ON u.hospital_id = h.id
  ORDER BY u.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Пользователи</h1>

<div class="card mb-3">
  <div class="card-header">Создать пользователя</div>
  <div class="card-body">
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" class="row g-2">
      <div class="col-md-3">
        <label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Пароль</label>
        <input type="text" name="password" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Роль</label>
        <select name="role" class="form-select" id="role-select">
          <option value="hospital">Админ больницы</option>
          <option value="admin">Глобальный админ</option>
        </select>
      </div>
      <div class="col-md-3" id="hospital-select-wrapper">
        <label class="form-label">Больница</label>
        <select name="hospital_id" class="form-select">
          <option value="">Не выбрано</option>
          <?php foreach ($hospitals as $h): ?>
            <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 mt-3">
        <button class="btn btn-primary w-100">Создать</button>
      </div>
    </form>
  </div>
</div>

<table class="table table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>E-mail</th>
      <th>Роль</th>
      <th>Больница</th>
      <th>Создан</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['role']) ?></td>
      <td><?= htmlspecialchars($u['hospital_name'] ?? '-') ?></td>
      <td><?= $u['created_at'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const roleSelect = document.getElementById('role-select');
  const hospitalWrapper = document.getElementById('hospital-select-wrapper');

  function updateHospitalVisibility() {
    if (roleSelect.value === 'admin') {
      hospitalWrapper.style.display = 'none';
    } else {
      hospitalWrapper.style.display = 'block';
    }
  }

  roleSelect.addEventListener('change', updateHospitalVisibility);
  updateHospitalVisibility();
});
</script>