<?php

// Include Controller Logic
require_once __DIR__ . '/controllers/ApiController.php';
require_once __DIR__ . '/controllers/SmsContoller.php';

$apiController = new ApiController();
$smsController = new SmsController();

// API Logic
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

if ($uri === '') {
    $uri = '/';
}

// Routes

// Health Check
if (($uri === '/health' || $uri === '/health/') && $method === 'GET') {
    $apiController->healthCheck();
}

// Get all grades
if ($uri === '/get-all-grades' && $method === 'GET') {
    $smsController->getGrades();
    exit;
}

// 404 Fallback 
http_response_code(404);
echo json_encode([
    "error"  => "Route not found",
    "route"  => $uri,
    "method" => $method
]);
exit;
