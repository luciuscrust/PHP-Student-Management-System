<?php

require_once __DIR__ . '/../models/GradeModel.php';

class GradeController
{
    private GradeModel $gradeModel;

    public function __construct()
    {
        $this->gradeModel = new GradeModel();
    }

    public function getGrades(): void
    {
        $rows = $this->gradeModel->getAll();

        $grades = array_map(function ($row) {
            return [
                'id'       => (int)$row['id'],
                'grade_no' => (int)$row['grade_no'],
            ];
        }, $rows);

        http_response_code(200);
        echo json_encode([
            'grades' => $grades
        ]);
    }

    public function getGradeById(): void
    {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            return;
        }

        $id = (int)$_GET['id'];
        $grade = $this->gradeModel->getById($id);

        if (!$grade) {
            http_response_code(404);
            echo json_encode(['error' => 'Grade not found']);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'grade' => [
                'id'       => (int)$grade['id'],
                'grade_no' => (int)$grade['grade_no'],
            ]
        ]);
    }

    public function createGrade(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!isset($body['grade_no'])) {
            http_response_code(400);
            echo json_encode(['error' => 'grade_no is required']);
            return;
        }

        $gradeNo = (int)$body['grade_no'];

        if ($gradeNo <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'grade_no must be a positive integer']);
            return;
        }

        if ($this->gradeModel->getByGradeNo($gradeNo)) {
            http_response_code(409);
            echo json_encode(['error' => 'Grade already exists']);
            return;
        }

        $id = $this->gradeModel->create($gradeNo);

        http_response_code(201);
        echo json_encode([
            'message' => 'Grade created',
            'grade' => [
                'id'       => $id,
                'grade_no' => $gradeNo
            ]
        ]);
    }

    public function updateGrade(): void
    {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);

        if (!isset($body['grade_no'])) {
            http_response_code(400);
            echo json_encode(['error' => 'grade_no is required']);
            return;
        }

        $id = (int)$_GET['id'];
        $gradeNo = (int)$body['grade_no'];

        if ($gradeNo <= 0) {
            http_response_code(422);
            echo json_encode(['error' => 'grade_no must be a positive integer']);
            return;
        }

        $existing = $this->gradeModel->getById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Grade not found']);
            return;
        }

        $dup = $this->gradeModel->getByGradeNo($gradeNo);
        if ($dup && (int)$dup['id'] !== $id) {
            http_response_code(409);
            echo json_encode(['error' => 'grade_no already in use']);
            return;
        }

        $this->gradeModel->update($id, $gradeNo);

        http_response_code(200);
        echo json_encode([
            'message' => 'Grade updated',
            'grade' => [
                'id'       => $id,
                'grade_no' => $gradeNo
            ]
        ]);
    }

    public function deleteGrade(): void
    {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            return;
        }

        $id = (int)$_GET['id'];

        $existing = $this->gradeModel->getById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Grade not found']);
            return;
        }

        try {
            $this->gradeModel->delete($id);

            http_response_code(200);
            echo json_encode(['message' => 'Grade deleted']);
        } catch (PDOException $e) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Cannot delete grade because classes exist for this grade'
            ]);
        }
    }
}
