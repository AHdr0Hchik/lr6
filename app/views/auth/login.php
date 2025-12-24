<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (auth_login($email, $pass)) {
        header('Location: ' . BASE_URL);
        exit;
    } else {
        $error = 'Неверный e-mail или пароль';
    }
}
?>
<div class="row justify-content-center">
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">Вход в систему</div>
      <div class="card-body">
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">Войти</button>
        </form>
      </div>
    </div>
  </div>
</div>