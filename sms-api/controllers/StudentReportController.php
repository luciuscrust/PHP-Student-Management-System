<?php

use helpers\JsonHelpers;

require_once __DIR__ . '/../models/ReportModel.php';

class StudentReportController
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

    private function resolveYear(int $studentId): int
    {
        if (isset($_GET['year'])) {
            $year = (int)$_GET['year'];
            if ($year > 0) return $year;
        }

        $latest = $this->reportModel->getMostRecentYearForStudent($studentId);
        if ($latest === null) {

            JsonHelpers::json(404, [
                'error' => 'No scores found for this student (no year available).'
            ]);
        }

        return $latest;
    }

    /**
     * ADMIN: GET /student-report?student_id=123&year=2025
     * year optional (defaults to most recent = 2025)
     */

    public function getStudentReportAdmin(): void
    {
        if (!isset($_GET['student_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'student_id is required']);
            exit;
        }

        $studentId = (int)$_GET['student_id'];
        $year = $this->resolveYear($studentId);

        $this->respondStudentReport($studentId, $year);
    }

    /**
     * TEACHER: GET /teacher/student-report?student_id=123&year=2025
     * year optional (defaults to most recent = 2025)
     * Verifies student belongs to teacher's class
     */

    public function getStudentReportTeacher(): void
    {
        if (!isset($_GET['student_id'])) {
            JsonHelpers::json(404, [
                'error' => 'student_id is required'
            ]);
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            JsonHelpers::json(401, [
                'error' => 'Not authenticated'
            ]);
        }

        $user = $_SESSION['user'];
        if (($user['role'] ?? '') !== 'teacher') {

            JsonHelpers::json(403, [
                'error' => 'Forbidden'
            ]);
        }

        $teacherClassId = (int)($user['class_id'] ?? 0);
        if ($teacherClassId <= 0) {

            JsonHelpers::json(409, [
                'error' => 'Teacher is not assigned to a class'
            ]);
        }

        $studentId = (int)$_GET['student_id'];

        $studentClassId = $this->reportModel->getStudentClassId($studentId);
        if ($studentClassId === null) {

            JsonHelpers::json(404, [
                'error' => 'Student not found'
            ]);
        }

        if ($studentClassId !== $teacherClassId) {

            JsonHelpers::json(403, [
                'error' => 'Forbidden: student is not in your class'
            ]);
        }

        $year = $this->resolveYear($studentId);

        $this->respondStudentReport($studentId, $year);
    }

    private function respondStudentReport(int $studentId, int $year): void
    {
        $rows = $this->reportModel->getStudentReportRows($studentId, $year);

        if (count($rows) === 0) {
            JsonHelpers::json(404, [
                'error' => 'Student not found or no subjects available'
            ]);
        }

        $first = $rows[0];

        $report = [
            'student' => [
                'id'         => (int)$first['student_id'],
                'first_name' => $first['first_name'],
                'last_name'  => $first['last_name'],
                'class_id'   => (int)$first['class_id'],
                'class_name' => $first['class_name'],
                'grade_no'   => (int)$first['grade_no'],
            ],
            'year' => $year,
            'subjects' => [],
            'overall_average' => null
        ];

        $averages = [];

        foreach ($rows as $r) {
            $t1 = $r['first_term'];
            $t2 = $r['second_term'];
            $t3 = $r['third_term'];

            $avg = $this->avgIgnoreNull($t1, $t2, $t3);
            if ($avg !== null) $averages[] = $avg;

            $report['subjects'][] = [
                'subject_id'   => (int)$r['subject_id'],
                'subject_name' => $r['subject_name'],
                'first_term'   => ($t1 !== null) ? (float)$t1 : null,
                'second_term'  => ($t2 !== null) ? (float)$t2 : null,
                'third_term'   => ($t3 !== null) ? (float)$t3 : null,
                'average'      => $avg
            ];
        }

        if (count($averages) > 0) {
            $report['overall_average'] = round(array_sum($averages) / count($averages), 2);
        }

        JsonHelpers::json(200, $report);
    }
}
