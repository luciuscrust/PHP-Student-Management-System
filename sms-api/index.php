<?php

require_once __DIR__ . '/controllers/ApiController.php';

require_once __DIR__ . '/controllers/GradeContoller.php';
require_once __DIR__ . '/controllers/ClassController.php';
require_once __DIR__ . '/controllers/StudentReportController.php';
require_once __DIR__ . '/controllers/ReportController.php';
require_once __DIR__ . '/controllers/GradeSubjectAverageController.php';
require_once __DIR__ . '/controllers/StudentController.php';


require_once __DIR__ . '/controllers/AuthContoller.php';

require_once __DIR__ . '/helpers/Auth.php';
require_once __DIR__ . '/helpers/JsonHelpers.php';

use helpers\Auth;
use helpers\JsonHelpers;

$apiController  = new ApiController();

$gradeController  = new GradeController();
$classController  = new ClassController();
$reportController = new ReportController();
$studentReportController = new StudentReportController();
$gradeAvgController = new GradeSubjectAverageController();
$studentController = new StudentController();


$authController = new AuthController();

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

//for handling PUT and DELETE methods
if (in_array($method, ['PUT', 'DELETE'])) {
    parse_str(file_get_contents('php://input'), $_POST);
}


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

// GET /grade-subject-averages
if ($uri === '/grade-subject-averages' && $method === 'GET') {
    Auth::requireRole('admin');
    $gradeAvgController->getGradeSubjectAveragesAdmin();
    exit;
}

// GET /teacher/grade-subject-averages
if ($uri === '/teacher/grade-subject-averages' && $method === 'GET') {
    Auth::requireRole('teacher');
    $gradeAvgController->getGradeSubjectAveragesTeacher();
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
// If no year is specified, the most recent scoring year for the class will be used

// Admin: single student report
// GET /student-report?student_id=?&year=?
if ($uri === '/student-report' && $method === 'GET') {
    Auth::requireRole('admin');
    $studentReportController->getStudentReportAdmin();
    exit;
}

// Teacher: single student report (restricted)
// GET /teacher/student-report?student_id=?&year=?
if ($uri === '/teacher/student-report' && $method === 'GET') {
    Auth::requireRole('teacher');
    $studentReportController->getStudentReportTeacher();
    exit;
}

// Admin Class report: class_id is provided
// GET /class-report?class_id=?&year=?
if ($uri === '/class-report' && $method === 'GET') {
    Auth::requireRole('admin');
    $reportController->getClassReportAdmin();
    exit;
}

// Teacher Class report: class_id comes from session
// GET /class-report?year=?
if ($uri === '/teacher/class-report' && $method === 'GET') {
    Auth::requireRole('teacher');
    $reportController->getClassReportTeacher();
    exit;
}

// Student Management Routes

// Add student
// POST /students
if ($uri === '/students' && $method === 'POST') {
    Auth::requireRole('admin');
    $studentController->addStudent();
    exit;
}

// Update student
// PUT /students
if ($uri === '/students' && $method === 'PUT') {
    Auth::requireRole('admin');
    $studentController->updateStudent();
    exit;
}

// Delete student
// DELETE /students
if ($uri === '/students' && $method === 'DELETE') {
    Auth::requireRole('admin');
    $studentController->deleteStudent();
    exit;
}

// Save or update scores
// POST /students/scores
if ($uri === '/students/scores' && $method === 'POST') {
    Auth::requireRole('teacher');
    $studentController->saveScores();
    exit;
}

// Get student scores
// GET /students/scores?student_id=&school_year=
if ($uri === '/students/scores' && $method === 'GET') {
    Auth::requireRole('teacher');
    $studentController->getStudentScores();
    exit;
}



// 404
JsonHelpers::json(404, [
    "success" => false,
    "error"   => "Route not found",
    "route"   => $uri,
    "method"  => $method
]);
