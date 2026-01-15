<?php

require_once __DIR__ . '/../models/ReportModel.php';

class GradeSubjectAverageController
{
    private ReportModel $reportModel;

    public function __construct()
    {
        $this->reportModel = new ReportModel();
    }

    private function parseSubjects(): array
    {
        $default = ['Math', 'Grammar', 'Science', 'Social Studies'];

        if (!isset($_GET['subjects']) || trim((string)$_GET['subjects']) === '') {
            return $default;
        }

        $raw = (string)$_GET['subjects'];
        $parts = array_filter(array_map('trim', explode(',', $raw)));

        $parts = array_values(array_unique(array_slice($parts, 0, 10)));

        return count($parts) > 0 ? $parts : $default;
    }

    private function parseTerm(): string
    {
        $term = $_GET['term'] ?? 'overall';
        $term = is_string($term) ? strtolower(trim($term)) : 'overall';

        $allowed = ['first_term', 'second_term', 'third_term', 'overall'];
        return in_array($term, $allowed, true) ? $term : 'overall';
    }

    private function resolveYearAdmin(): int
    {
        if (isset($_GET['year'])) {
            $year = (int)$_GET['year'];
            if ($year > 0) return $year;
        }

        $latest = $this->reportModel->getMostRecentYearOverall();
        if ($latest === null) {
            http_response_code(404);
            echo json_encode(['error' => 'No scores found (no year available).']);
            exit;
        }

        return $latest;
    }

    private function resolveYearTeacher(int $classId): int
    {
        if (isset($_GET['year'])) {
            $year = (int)$_GET['year'];
            if ($year > 0) return $year;
        }

        $latest = $this->reportModel->getMostRecentYearForClass($classId);
        if ($latest === null) {
            http_response_code(404);
            echo json_encode(['error' => 'No scores found for your class (no year available).']);
            exit;
        }

        return $latest;
    }

    /**
     * ADMIN: GET /grade-subject-averages?subjects=Math,Science&year=2025&term=overall
     * If 'subjects' and 'year' are not specified it will use all the subject and the most recent year
     */

    public function getGradeSubjectAveragesAdmin(): void
    {
        $subjects = $this->parseSubjects();
        $term = $this->parseTerm();
        $year = $this->resolveYearAdmin();

        $rows = $this->reportModel->getGradeSubjectAveragesRows($year, $subjects, $term);

        if (count($rows) === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'No matching data found for the selected filters.']);
            exit;
        }

        $this->respond($rows, $year, $term, $subjects);
    }

    /**
     * TEACHER: GET /teacher/grade-subject-averages?subjects=Math,Science&year=2025&term=overall
     * Restricts to teacher's grade via teacher class_id
     */

    public function getGradeSubjectAveragesTeacher(): void
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

        $subjects = $this->parseSubjects();
        $term = $this->parseTerm();
        $year = $this->resolveYearTeacher($classId);

        $gradeId = $this->reportModel->getGradeIdForClass($classId);
        if ($gradeId === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Class not found']);
            exit;
        }

        $rows = $this->reportModel->getGradeSubjectAveragesRowsByGrade($gradeId, $year, $subjects, $term);

        if (count($rows) === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'No matching data found for your grade with the selected filters.']);
            exit;
        }

        $this->respond($rows, $year, $term, $subjects);
    }

    private function respond(array $rows, int $year, string $term, array $subjects): void
    {
        $result = [];
        foreach ($rows as $r) {
            $gradeNo = (int)$r['grade_no'];
            $subject = (string)$r['subject_name'];

            if (!isset($result[$gradeNo])) {
                $result[$gradeNo] = [
                    'grade_no' => $gradeNo,
                    'subjects' => []
                ];
            }

            $result[$gradeNo]['subjects'][] = [
                'subject_name' => $subject,
                'avg' => $r['avg_score'] !== null ? (float)$r['avg_score'] : null,
                'count' => (int)$r['count_scores']
            ];
        }

        usort($result, fn($a, $b) => $a['grade_no'] <=> $b['grade_no']);

        http_response_code(200);
        echo json_encode([
            'year' => $year,
            'term' => $term,
            'subjects' => array_values($subjects),
            'grades' => $result
        ]);
        exit;
    }
}
