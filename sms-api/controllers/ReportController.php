<?php

use helpers\JsonHelpers;

require_once __DIR__ . '/../models/ReportModel.php';

class ReportController
{
    private ReportModel $reportModel;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
    }

    private function avgIgnoreNull($t1, $t2, $t3): ?float
    {
        $vals = [];
        foreach ([$t1, $t2, $t3] as $v) {
            if ($v !== null) $vals[] = (float)$v;
        }
        if (count($vals) === 0) return null;

        return round(array_sum($vals) / count($vals), 2);
    }

    /**
     * ADMIN: GET /class-report?class_id=1&year=2025
     * If year is not preovided, it will default to the most resent scoring year for the specified class
     */

    public function getClassReportAdmin(): void
    {
        if (!isset($_GET['class_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'class_id is required']);
            exit;
        }

        $classId = (int)$_GET['class_id'];

        if (isset($_GET['year'])) {
            $year = (int)$_GET['year'];
        } else {
            $year = $this->reportModel->getMostRecentYearForClass($classId);

            if ($year === null) {
                JsonHelpers::json(404, [
                    'error' => 'No scores found for this class'
                ]);
            }
        }

        $this->respondWithReport($classId, $year);
    }


    /**
     * TEACHER: GET /teacher/class-report?year=2025
     * If year is not preovided, it will default to the most resent scoring year for the specified class
     * class_id comes from session (so teachers can’t change it to smthing else)
     */

    public function getClassReportTeacher(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            exit;
        }

        $user = $_SESSION['user'];

        if (($user['role'] ?? '') !== 'teacher') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $classId = (int)($user['class_id'] ?? 0);
        if ($classId <= 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Teacher is not assigned to a class']);
            exit;
        }

        if (isset($_GET['year'])) {
            $year = (int)$_GET['year'];
        } else {
            $year = $this->reportModel->getMostRecentYearForClass($classId);

            if ($year === null) {
                JsonHelpers::json(404, [
                    'error' => 'No scores found for this class'
                ]);
            }
        }

        $this->respondWithReport($classId, $year);
    }


    private function respondWithReport(int $classId, int $year): void
    {
        $rows = $this->reportModel->getClassReportRows($classId, $year);

        $studentsMap = [];

        foreach ($rows as $r) {
            $studentId = (int)$r['student_id'];

            if (!isset($studentsMap[$studentId])) {
                $studentsMap[$studentId] = [
                    'id'         => $studentId,
                    'class_id'   => (int)$r['class_id'],
                    'first_name' => $r['first_name'],
                    'last_name'  => $r['last_name'],
                    'scores'     => []
                ];
            }

            $t1 = $r['first_term'];
            $t2 = $r['second_term'];
            $t3 = $r['third_term'];

            $studentsMap[$studentId]['scores'][] = [
                'subject_id'   => (int)$r['subject_id'],
                'subject_name' => $r['subject_name'],
                'school_year'  => $year,
                'first_term'   => ($t1 !== null) ? (float)$t1 : null,
                'second_term'  => ($t2 !== null) ? (float)$t2 : null,
                'third_term'   => ($t3 !== null) ? (float)$t3 : null,
                'average'      => $this->avgIgnoreNull($t1, $t2, $t3),
            ];
        }

        JsonHelpers::json(200, [
            'class_id' => $classId,
            'year'     => $year,
            'auto_year' => !isset($_GET['year']),
            'students' => array_values($studentsMap)
        ]);
    }
}
