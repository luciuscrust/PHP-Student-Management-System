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

// GET /grades/show?id=1
if ($uri === '/grades/show' && $method === 'GET') {
    Auth::requireRole('admin');
    $gradeController->getGradeById();
    exit;
}

// POST /grades
// body: { "grade_no": 7 }
if ($uri === '/grades' && $method === 'POST') {
    Auth::requireRole('admin');
    $gradeController->createGrade();
    exit;
}

// PUT /grades?id=1
// body: { "grade_no": 8 }
if ($uri === '/grades' && $method === 'PUT') {
    Auth::requireRole('admin');
    $gradeController->updateGrade();
    exit;
}

// DELETE /grades?id=1
if ($uri === '/grades' && $method === 'DELETE') {
    Auth::requireRole('admin');
    $gradeController->deleteGrade();
    exit;
}



// Class Related Routes
if ($uri === '/get-classes' && $method === 'GET') {
    Auth::requireRole('admin');
    $classController->getClassesByGrade();
    exit;
}

// if ($uri === '/get-classes' && $method === 'GET') {
//     Auth::requireRole('admin');
//     $smsController->getClassesByGrade();
//     exit;
// }

// if ($uri === '/get-classes' && $method === 'GET') {
//     Auth::requireRole('admin');
//     $smsController->getClassesByGrade();
//     exit;
// }

// if ($uri === '/get-classes' && $method === 'GET') {
//     Auth::requireRole('admin');
//     $smsController->getClassesByGrade();
//     exit;
// }



// 404
JsonHelpers::json(404, [
    "success" => false,
    "error"   => "Route not found",
    "route"   => $uri,
    "method"  => $method
]);
