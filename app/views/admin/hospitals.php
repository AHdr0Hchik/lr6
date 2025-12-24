<?php
$db = db_connect();

if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    $exportData = $db->query("SELECT id, name, address FROM hospitals ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

    if (ob_get_level()) ob_end_clean();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hospitals_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    
    fputs($output, "\xEF\xBB\xBF");
    
    fputcsv($output, ['ID', 'Название', 'Адрес'], ';');

    foreach ($exportData as $row) {
        fputcsv($output, $row, ';');
    }

    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($name) {
        $stmt = $db->prepare("INSERT INTO hospitals (name, address) VALUES (?, ?)");
        $stmt->execute([$name, $address]);
    }
}

$hospitals = $db->query("SELECT * FROM hospitals ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<h1>Больницы</h1>
<form method="post" class="row g-2 mb-3">
  <div class="col-md-4">
    <input type="text" name="name" class="form-control" placeholder="Название" required>
  </div>
  <div class="col-md-4">
    <input type="text" name="address" class="form-control" placeholder="Адрес">
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Добавить</button>
  </div>
  <div class="col-md-2">
    <a href="?r=admin/hospitals&export=xlsx" class="btn btn-success w-100">Экспорт в Excel</a>
  </div>
</form>

<table class="table table-striped">
  <thead><tr><th>ID</th><th>Название</th><th>Адрес</th></tr></thead>
  <tbody>
  <?php foreach ($hospitals as $h): ?>
    <tr>
      <td><?= $h['id'] ?></td>
      <td><?= htmlspecialchars($h['name']) ?></td>
      <td><?= htmlspecialchars($h['address']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>