<?php

require_once __DIR__ . '/controllers/ApiController.php';

require_once __DIR__ . '/controllers/GradeContoller.php';
require_once __DIR__ . '/controllers/ClassContoller.php';

require_once __DIR__ . '/controllers/AuthContoller.php';

require_once __DIR__ . '/helpers/Auth.php';
require_once __DIR__ . '/helpers/JsonHelpers.php';

use helpers\Auth;
use helpers\JsonHelpers;

$apiController  = new ApiController();
$gradeController  = new GradeController();
$classController  = new ClassController();

$authController = new AuthController();

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
if ($uri === '') $uri = '/';

// --------------------
// Public routes
// --------------------
if (($uri === '/health' || $uri === '/health/') && $method === 'GET') {
    $apiController->healthCheck();
    exit;
}

if (($uri === '/auth/login' || $uri === '/auth/login/') && $method === 'POST') {
    $authController->login();
    exit;
}

if (($uri === '/auth/logout' || $uri === '/auth/logout/') && $method === 'POST') {
    $authController->logout();
    exit;
}

if (($uri === '/auth/me' || $uri === '/auth/me/') && $method === 'GET') {
    $authController->me();
    exit;
}

// --------------------
// Protected routes
// --------------------
Auth::requireLogin();


// Grade Related Routes
// GET /grades
if ($uri === '/grades' && $method === 'GET') {
    Auth::requireRole('admin');
    $gradeController->getGrades();
    exit;
}


// Class Related Routes
// GET /get-classes?grade_id=id
if ($uri === '/get-classes' && $method === 'GET') {
    Auth::requireRole('admin');
    $classController->getClassesByGrade();
    exit;
}


// Student and Score Related Routes
// Admin report: class_id is provided
// GET /class-report?class_id=?&year=?
// If no year is specified, the most recent scoring year for the class will be used
if ($uri === '/class-report' && $method === 'GET') {
    Auth::requireRole('admin');
    $reportController->getClassReportAdmin();
    exit;
}

// Teacher report: class_id comes from session
// GET /class-report?year=?
if ($uri === '/teacher/class-report' && $method === 'GET') {
    Auth::requireRole('teacher');
    $reportController->getClassReportTeacher();
    exit;
}


// 404
JsonHelpers::json(404, [
    "success" => false,
    "error"   => "Route not found",
    "route"   => $uri,
    "method"  => $method
]);
