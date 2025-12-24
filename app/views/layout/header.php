<?php $user = auth_user(); ?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Glucose Monitor</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
  >

  <!-- Chart.js подключаем в <head>, чтобы он был доступен до кода графиков -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size: 16px;
      background-color: #f5f5f5;
    }
    .navbar-brand {
      font-weight: 600;
    }
    .container {
      max-width: 1100px;
    }
    h1, h3 {
      margin-bottom: 0.75rem;
    }
    .card {
      border-radius: 0.75rem;
    }
    .btn, .form-control, .form-select {
      font-size: 16px;
    }
    .table {
      font-size: 0.95rem;
    }

    .chart-container {
      position: relative;
      height: 260px;
      max-width: 100%;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-3">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>">Glucose</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <?php if ($user): ?>
          <li class="nav-item">
            <span class="navbar-text me-3">
              <?= htmlspecialchars($user['email']) ?> (<?= htmlspecialchars($user['role']) ?>)
            </span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>?r=auth/logout">Выход</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container mb-5">