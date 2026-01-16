<?php
session_start();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request body.']);
    exit;
}

$csrf = $input['csrf_token'] ?? '';
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request (CSRF).']);
    exit;
}

$user = $input['user'] ?? null;
if (!is_array($user) || empty($user['role'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Login failed (missing user payload).']);
    exit;
}

$_SESSION['user'] = [
    'id' => isset($user['id']) ? (int)$user['id'] : null,
    'role' => $user['role'],
    'email' => $user['email'] ?? null,
    'class_id' => array_key_exists('class_id', $user) && $user['class_id'] !== null ? (int)$user['class_id'] : null
];

session_regenerate_id(true);
unset($_SESSION['csrf_token']);

if ($_SESSION['user']['role'] === 'admin') {
    echo json_encode(['url' => './views/admin/dashboard.php']);
    exit;
}

if ($_SESSION['user']['role'] === 'teacher') {
    echo json_encode(['url' => './views/admin/dashboard.php']);
    exit;
}

echo json_encode(['url' => '../../index.php']);
