<?php
$db   = db_connect();
$user = auth_user();
$hospital_id = $user['hospital_id'];

$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Загружаем пациента и проверяем принадлежность к больнице врача
$stmt = $db->prepare("
    SELECT p.*, u.email
    FROM patients p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ? AND p.hospital_id = ?
");
$stmt->execute([$patient_id, $hospital_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo "<div class='alert alert-danger'>Пациент не найден или недоступен</div>";
    return;
}

// Первое устройство пациента (для простоты)
$stmt = $db->prepare("SELECT id, serial FROM devices WHERE patient_id = ? ORDER BY id ASC LIMIT 1");
$stmt->execute([$patient_id]);
$device = $stmt->fetch(PDO::FETCH_ASSOC);

// Чтения за последние 24 часа
$readings = [];
if ($device) {
    $stmt = $db->prepare("
        SELECT ts, glucose_mg_dl
        FROM sensor_readings
        WHERE device_id = ?
          AND ts >= (NOW() - INTERVAL 1 DAY)
        ORDER BY ts ASC
    ");
    $stmt->execute([$device['id']]);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Метрики
$labels = [];
$values = [];
$totalPoints = count($readings);
$minValue = null;
$maxValue = null;
$sumValue = 0;
$inRangeCount = 0; // 70–180 mg/dL

foreach ($readings as $r) {
    $g = (int)$r['glucose_mg_dl'];
    $labels[] = $r['ts'];
    $values[] = $g;

    if ($minValue === null || $g < $minValue) $minValue = $g;
    if ($maxValue === null || $g > $maxValue) $maxValue = $g;
    $sumValue += $g;
    if ($g >= 70 && $g <= 180) $inRangeCount++;
}

$avgValue = $totalPoints > 0 ? round($sumValue / $totalPoints) : null;
$tir = $totalPoints > 0 ? round($inRangeCount * 100 / $totalPoints, 1) : null;

// Последнее значение
$lastValue = null;
$lastTime  = null;
if ($totalPoints > 0) {
    $lastValue = $values[$totalPoints - 1];
    $lastTime  = $labels[$totalPoints - 1];
}

// Класс для подсветки последнего значения
$lastClass = '';
$lastStatusText = '';
if ($lastValue !== null) {
    if ($lastValue < 70 || $lastValue > 250) {
        $lastClass = 'text-danger fw-bold';
    } elseif ($lastValue >= 70 && $lastValue <= 180) {
        $lastClass = 'text-success fw-bold';
    } else {
        $lastClass = 'text-warning fw-bold';
    }

    if ($lastValue < 70) {
        $lastStatusText = 'Гипогликемия';
    } elseif ($lastValue > 250) {
        $lastStatusText = 'Гипергликемия';
    } elseif ($lastValue > 180) {
        $lastStatusText = 'Выше целевого диапазона';
    } else {
        $lastStatusText = 'В целевом диапазоне';
    }
}

// Фильтр и пагинация алертов
$alertType  = $_GET['alert_type']  ?? 'all';
$alertLevel = $_GET['alert_level'] ?? 'all';
$perPage    = 10;
$page       = max(1, (int)($_GET['page'] ?? 1));

$where  = "patient_id = ?";
$params = [$patient_id];

if ($alertType !== 'all' && $alertType !== '') {
    $where  .= " AND type = ?";
    $params[] = $alertType;
}
if ($alertLevel !== 'all' && $alertLevel !== '') {
    $where  .= " AND level = ?";
    $params[] = $alertLevel;
}

// Всего алертов
$stmt = $db->prepare("SELECT COUNT(*) FROM alerts WHERE $where");
$stmt->execute($params);
$totalAlerts = (int)$stmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalAlerts / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// Список алертов с учётом фильтров и пагинации
$sql = "SELECT * FROM alerts WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Хелпер для построения ссылок пагинации
function buildAlertQueryDoctor($page, $patient_id, $type, $level): string {
    $q = [
        'r'           => 'doctor/patient',
        'id'          => $patient_id,
        'page'        => $page,
    ];
    if ($type !== 'all' && $type !== '')   $q['alert_type']  = $type;
    if ($level !== 'all' && $level !== '') $q['alert_level'] = $level;

    return '?' . http_build_query($q);
}
?>

<h1>Пациент: <?= htmlspecialchars($patient['full_name']) ?></h1>
<p>
  E-mail: <?= htmlspecialchars($patient['email']) ?><br>
  Диагноз: <?= htmlspecialchars($patient['diagnosis']) ?><br>
  <?php if ($device): ?>
    Устройство: <strong><?= htmlspecialchars($device['serial']) ?></strong>
  <?php else: ?>
    <span class="text-warning">Устройство не привязано</span>
  <?php endif; ?>
</p>

<?php if ($lastValue !== null): ?>
  <div class="alert alert-light border">
    <div class="row">
      <div class="col-md-4">
        <div>Текущий уровень глюкозы:</div>
        <div class="<?= $lastClass ?>" style="font-size: 1.6rem;">
          <?= $lastValue ?> mg/dL
        </div>
        <div class="text-muted" style="font-size: 0.9rem;">Статус: <?= htmlspecialchars($lastStatusText) ?></div>
        <?php if ($lastTime): ?>
          <div class="text-muted" style="font-size: 0.9rem;">Время: <?= htmlspecialchars($lastTime) ?></div>
        <?php endif; ?>
      </div>
      <div class="col-md-8">
        <div class="row">
          <div class="col-md-3 col-6 mb-2">
            <div class="card text-center">
              <div class="card-body p-2">
                <div class="text-muted" style="font-size: 0.85rem;">TIR (70–180)</div>
                <?php
                  $tirClass = 'text-secondary';
                  if ($tir !== null) {
                      if ($tir < 50)      $tirClass = 'text-danger fw-bold';
                      elseif ($tir < 70)  $tirClass = 'text-warning fw-bold';
                      else                $tirClass = 'text-success fw-bold';
                  }
                ?>
                <div class="<?= $tirClass ?>" style="font-size: 1.3rem;">
                  <?= $tir !== null ? $tir . '%' : '—' ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-2">
            <div class="card text-center">
              <div class="card-body p-2">
                <div class="text-muted" style="font-size: 0.85rem;">Минимум</div>
                <div style="font-size: 1.3rem;">
                  <?= $minValue !== null ? $minValue . ' mg/dL' : '—' ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-2">
            <div class="card text-center">
              <div class="card-body p-2">
                <div class="text-muted" style="font-size: 0.85rem;">Максимум</div>
                <div style="font-size: 1.3rem;">
                  <?= $maxValue !== null ? $maxValue . ' mg/dL' : '—' ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6 mb-2">
            <div class="card text-center">
              <div class="card-body p-2">
                <div class="text-muted" style="font-size: 0.85rem;">Среднее</div>
                <div style="font-size: 1.3rem;">
                  <?= $avgValue !== null ? $avgValue . ' mg/dL' : '—' ?>
                </div>
              </div>
            </div>
          </div>
        </div> <!-- row -->
      </div>
    </div>
  </div>
<?php endif; ?>

<h3>Глюкоза за последние 24 часа</h3>
<?php if ($readings): ?>
  <div class="chart-container">
    <canvas id="glucoseChart"></canvas>
  </div>
  <script>
    (function () {
      const canvas = document.getElementById('glucoseChart');
      if (!canvas || typeof Chart === 'undefined') return;

      const labels = <?= json_encode($labels) ?>;
      const values = <?= json_encode($values) ?>;

      new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Глюкоза (mg/dL)',
            data: values,
            borderColor: 'rgba(13, 110, 253, 1)',
            backgroundColor: 'rgba(13, 110, 253, 0.12)',
            pointRadius: 2,
            pointHoverRadius: 4,
            tension: 0.2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              ticks: { autoSkip: true, maxTicksLimit: 8 }
            },
            y: {
              suggestedMin: 40,
              suggestedMax: 300,
              ticks: { stepSize: 20 }
            }
          },
          plugins: {
            legend: { display: false }
          }
        }
      });
    })();
  </script>
<?php else: ?>
  <div class="alert alert-info">Пока нет данных за последние 24 часа.</div>
<?php endif; ?>

<h3 class="mt-4">Алерты</h3>

<form method="get" class="row g-2 mb-2">
  <input type="hidden" name="r" value="doctor/patient">
  <input type="hidden" name="id" value="<?= $patient_id ?>">
  <div class="col-md-3">
    <label class="form-label">Тип</label>
    <select name="alert_type" class="form-select">
      <option value="all" <?= $alertType === 'all' ? 'selected' : '' ?>>Все</option>
      <option value="hypo" <?= $alertType === 'hypo' ? 'selected' : '' ?>>Гипо</option>
      <option value="severe_hypo" <?= $alertType === 'severe_hypo' ? 'selected' : '' ?>>Тяжёлая гипо</option>
      <option value="hyper" <?= $alertType === 'hyper' ? 'selected' : '' ?>>Гипер</option>
      <option value="rapid_change" <?= $alertType === 'rapid_change' ? 'selected' : '' ?>>Быстрое изменение</option>
      <option value="connection_lost" <?= $alertType === 'connection_lost' ? 'selected' : '' ?>>Потеря связи</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label">Уровень</label>
    <select name="alert_level" class="form-select">
      <option value="all" <?= $alertLevel === 'all' ? 'selected' : '' ?>>Все</option>
      <option value="info" <?= $alertLevel === 'info' ? 'selected' : '' ?>>Info</option>
      <option value="warning" <?= $alertLevel === 'warning' ? 'selected' : '' ?>>Warning</option>
      <option value="critical" <?= $alertLevel === 'critical' ? 'selected' : '' ?>>Critical</option>
    </select>
  </div>
  <div class="col-md-2 align-self-end">
    <button class="btn btn-primary w-100">Фильтровать</button>
  </div>
</form>

<?php if ($alerts): ?>
  <table class="table table-sm">
    <thead>
      <tr>
        <th>Время</th>
        <th>Тип</th>
        <th>Уровень</th>
        <th>Сообщение</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($alerts as $a): ?>
      <?php
        $badgeClass = 'bg-secondary';
        if ($a['level'] === 'warning') $badgeClass = 'bg-warning text-dark';
        if ($a['level'] === 'critical') $badgeClass = 'bg-danger';
      ?>
      <tr>
        <td><?= $a['created_at'] ?></td>
        <td><?= htmlspecialchars($a['type']) ?></td>
        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($a['level']) ?></span></td>
        <td><?= htmlspecialchars($a['message']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($totalPages > 1): ?>
    <nav>
      <ul class="pagination pagination-sm">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page > 1 ? buildAlertQueryDoctor($page - 1, $patient_id, $alertType, $alertLevel) : '#' ?>">«</a>
        </li>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <li class="page-item <?= $p == $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= buildAlertQueryDoctor($p, $patient_id, $alertType, $alertLevel) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
          <a class="page-link" href="<?= $page < $totalPages ? buildAlertQueryDoctor($page + 1, $patient_id, $alertType, $alertLevel) : '#' ?>">»</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>

<?php else: ?>
  <div class="alert alert-info">Алертов по выбранным фильтрам нет.</div>
<?php endif; ?>