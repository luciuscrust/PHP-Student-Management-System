<?php
session_start();

function set_flash($msg, $type = 'text-red-700 bg-red-100')
{
    $_SESSION['flash'] = $msg;
    $_SESSION['flash_type'] = $type;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    set_flash('Invalid request (CSRF).');
    header('Location: index.php');
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email) {
    set_flash('Please provide a valid email address.');
    header('Location: index.php');
    exit;
}

if (!preg_match('/(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}/', $password)) {
    set_flash('Password must be at least 8 characters and include letters, numbers and a symbol.');
    header('Location: index.php');
    exit;
}

try {
    $backendUrl = 'http://localhost/PHP-Student-Management-System/sms-api/auth/login';

    $payload = json_encode([
        'email' => $email,
        'password' => $password
    ]);

    $ch = curl_init($backendUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POSTFIELDS => $payload,

        CURLOPT_HEADER => true,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        unset($ch);

        set_flash('Server error. Try again later.');
        header('Location: index.php');
        exit;
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    unset($ch);

    $rawHeaders = substr($response, 0, $headerSize);
    $rawBody    = substr($response, $headerSize);

    $data = json_decode($rawBody, true);

    if ($status !== 200 || !is_array($data) || empty($data['success'])) {
        $msg = $data['message'] ?? 'Invalid credentials.';
        set_flash($msg);
        header('Location: index.php');
        exit;
    }

    $user = $data['user'] ?? null;
    if (!$user) {
        set_flash('Login failed (missing user payload).');
        header('Location: index.php');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = $user;

    if (preg_match('/Set-Cookie:\s*PHPSESSID=([^;]+)/i', $rawHeaders, $m)) {
        $_SESSION['backend_phpsessid'] = $m[1];
    }

    unset($_SESSION['csrf_token']);

    // Redirect based on role/class_id
    if (($user['role'] ?? '') === 'admin') {
        header('Location: ../admin/dashboard.php'); // admin view
        exit;
    }

    // teacher
    header('Location: ../teacher/dashboard.php'); // teacher view
    exit;
} catch (Throwable $e) {
    set_flash('Server error. Try again later.');
    header('Location: index.php');
    exit;
}
