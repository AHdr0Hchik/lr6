<?php
echo 123;
// Запускать из консоли: php emulator.php
require_once __DIR__ . '/../config/config.php';

const DEVICE_COUNT = 1;
const API_URL = 'http://glucose/api.php'; // путь к API
const STEP_SECONDS_REAL = 1;      // каждая секунда реального времени
const STEP_MINUTES_VIRTUAL = 5;   // = 5 минут виртуального времени

$devices = [];

for ($i = 1; $i <= DEVICE_COUNT; $i++) {
    $devices[] = [
        'serial'   => sprintf('SIM-001', $i),
        'scenario' => pickScenario($i),
        'glucose'  => rand(90, 130),
        'time'     => time()
    ];
}

while (true) {
    foreach ($devices as &$d) {
        echo $d;
        $d['time'] += STEP_MINUTES_VIRTUAL * 60;
        $prev = $d['glucose'];
        $d['glucose'] = simulateGlucoseStep($d['glucose'], $d['scenario']);
        $trend = ($d['glucose'] - $prev) / STEP_MINUTES_VIRTUAL;

        // сценарий connection_loss: иногда просто пропускаем отправку
        if ($d['scenario'] === 'connection_loss' && rand(1, 10) <= 3) {
            continue;
        }

        $payload = [
            'serial'    => $d['serial'],
            'timestamp' => gmdate('c', $d['time']),
            'glucose'   => round($d['glucose']),
            'trend'     => round($trend, 2),
            'battery'   => rand(30, 100),
            'api_key'   => API_SHARED_KEY
        ];

        sendReading($payload);
    }

    sleep(STEP_SECONDS_REAL);
}

function pickScenario(int $i): string {
    $scenarios = ['normal', 'nocturnal_hypo', 'hyper_after_meal', 'connection_loss'];
    return $scenarios[$i % count($scenarios)];
}

function simulateGlucoseStep(float $current, string $scenario): float {
    switch ($scenario) {
        case 'normal':
            $delta = rand(-5, 5);
            break;
        case 'nocturnal_hypo':
            $delta = rand(-8, 3);
            break;
        case 'hyper_after_meal':
            $delta = rand(-3, 10);
            break;
        case 'connection_loss':
            $delta = rand(-5, 5);
            break;
        default:
            $delta = rand(-5, 5);
    }
    $new = $current + $delta;
    return max(40, min($new, 350));
}

function sendReading(array $payload): void {
    $ch = curl_init(API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}