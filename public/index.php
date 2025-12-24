<?php
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/db.php';
require_once __DIR__ . '/../app/lib/alerts.php';

$route = $_GET['r'] ?? 'home';

// Подключаем layout
include __DIR__ . '/../app/views/layout/header.php';

switch ($route) {
    case 'auth/login':
        include __DIR__ . '/../app/views/auth/login.php';
        break;
    case 'auth/logout':
        auth_logout();
        break;

    case 'admin/dashboard':
        auth_require_role(['admin']);
        include __DIR__ . '/../app/views/admin/dashboard.php';
        break;
    case 'admin/hospitals':
        auth_require_role(['admin']);
        include __DIR__ . '/../app/views/admin/hospitals.php';
        break;
    case 'admin/users':
        auth_require_role(['admin']);
        include __DIR__ . '/../app/views/admin/users.php';
        break;

    case 'hospital/dashboard':
        auth_require_role(['hospital']);
        include __DIR__ . '/../app/views/hospital/dashboard.php';
        break;
    case 'hospital/doctors':
        auth_require_role(['hospital']);
        include __DIR__ . '/../app/views/hospital/doctors.php';
        break;
    case 'hospital/patients':
        auth_require_role(['hospital']);
        include __DIR__ . '/../app/views/hospital/patients.php';
        break;
    case 'hospital/devices':
        auth_require_role(['hospital']);
        include __DIR__ . '/../app/views/hospital/devices.php';
        break;

    case 'doctor/dashboard':
        auth_require_role(['doctor']);
        include __DIR__ . '/../app/views/doctor/dashboard.php';
        break;
    case 'doctor/patient':
        auth_require_role(['doctor']);
        include __DIR__ . '/../app/views/doctor/patient_view.php';
        break;

    case 'patient/dashboard':
        auth_require_role(['patient']);
        include __DIR__ . '/../app/views/patient/dashboard.php';
        break;
    case 'patient/trusted':
        auth_require_role(['patient']);
        include __DIR__ . '/../app/views/patient/trusted.php';
        break;

    case 'trusted/dashboard':
        auth_require_role(['trusted']);
        include __DIR__ . '/../app/views/trusted/dashboard.php';
        break;

    case 'home':
    default:
        $user = auth_user();
        if ($user) {
            // простой редирект на дашборд по роли
            switch ($user['role']) {
                case 'admin':
                    header('Location: ' . BASE_URL . '?r=admin/dashboard'); exit;
                case 'hospital':
                    header('Location: ' . BASE_URL . '?r=hospital/dashboard'); exit;
                case 'doctor':
                    header('Location: ' . BASE_URL . '?r=doctor/dashboard'); exit;
                case 'patient':
                    header('Location: ' . BASE_URL . '?r=patient/dashboard'); exit;
                case 'trusted':
                    header('Location: ' . BASE_URL . '?r=trusted/dashboard'); exit;
            }
        } else {
            header('Location: ' . BASE_URL . '?r=auth/login'); exit;
        }
}

include __DIR__ . '/../app/views/layout/footer.php';