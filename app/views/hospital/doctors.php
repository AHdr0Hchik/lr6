<?php
$db   = db_connect();
$user = auth_user();
$hospital_id = $user['hospital_id'];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'doctor'; // doctor или trusted

    if (!$email || !$password) {
        $error = 'Заполните e-mail и пароль';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Неверный формат e-mail';
    } elseif (!in_array($role, ['doctor', 'trusted'], true)) {
        $error = 'Неверная роль';
    } else {
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
            $stmt->execute([$email, $password_hash, $role, $hospital_id]);
        }
    }
}

$stmt = $db->prepare("
    SELECT u.*
    FROM users u
    WHERE u.hospital_id = ?
      AND (u.role = 'doctor' OR u.role = 'trusted')
    ORDER BY u.id DESC
");
$stmt->execute([$hospital_id]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Врачи и доверенные пользователи</h1>

<div class="card mb-3">
  <div class="card-header">Создать врача / доверенное лицо</div>
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
        <select name="role" class="form-select">
          <option value="doctor">Врач</option>
          <option value="trusted">Доверенное лицо</option>
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
      <th>Создан</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= $u['id'] ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><?= htmlspecialchars($u['role']) ?></td>
      <td><?= $u['created_at'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>