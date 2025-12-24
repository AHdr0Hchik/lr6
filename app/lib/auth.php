<?php
require_once __DIR__ . '/db.php';

session_start();

function auth_login(string $email, string $password): bool {
    $db = db_connect();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'hospital_id' => $user['hospital_id'],
        ];
        return true;
    }
    return false;
}

function auth_user() {
    return $_SESSION['user'] ?? null;
}

function auth_require(): void {
    if (!auth_user()) {
        header('Location: ' . BASE_URL . '?r=auth/login');
        exit;
    }
}

function auth_require_role(array $roles): void {
    $user = auth_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}

function auth_logout(): void {
    session_destroy();
    header('Location: ' . BASE_URL . '?r=auth/login');
    exit;
}