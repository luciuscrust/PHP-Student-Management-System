<?php
session_start();

// Adjusted path (authenticate.php is inside sms/ so db.php is ../sms-api/config/db.php)
require_once __DIR__ . '/../sms-api/config/db.php';

function set_flash($msg, $type = 'text-red-700 bg-red-100') {
    $_SESSION['flash'] = $msg;
    $_SESSION['flash_type'] = $type;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// CSRF check
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    set_flash('Invalid request (CSRF).');
    header('Location: index.php');
    exit;
}

// Basic server-side input validation & sanitation
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email) {
    set_flash('Please provide a valid email address.');
    header('Location: index.php');
    exit;
}

// Enforce password policy server-side: at least 8 chars, letter, number, symbol
if (!preg_match('/(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}/', $password)) {
    set_flash('Password must be at least 8 characters and include letters, numbers and a symbol.');
    header('Location: index.php');
    exit;
}

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, role, email, password_hash, class_id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Do not reveal whether email exists
        set_flash('Invalid credentials.');
        header('Location: index.php');
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        set_flash('Invalid credentials.');
        header('Location: index.php');
        exit;
    }

    // Successful login: set session
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'class_id' => $user['class_id'] !== null ? (int)$user['class_id'] : null
    ];

    // Optional: remove csrf token on successful login
    unset($_SESSION['csrf_token']);

    // Redirect to a dashboard page - ensure dashboard.php exists in sms\ or change target
    header('Location: dashboard.php');
    exit;

} catch (PDOException $e) {
    // In production, log error instead of showing details
    set_flash('Server error. Try again later.');
    header('Location: index.php');
    exit;
}