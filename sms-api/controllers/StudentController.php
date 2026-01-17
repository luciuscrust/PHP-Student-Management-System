<?php

use helpers\JsonHelpers;

require_once __DIR__ . '/../models/StudentModel.php';

class StudentController
{
    private StudentModel $StudentModel;

    public function __construct()
    {
        $this->StudentModel = new StudentModel();
    }

    /**
     * Add a new student
     * POST: class_id, first_name, last_name
     */

    private function scoreOrNull($value): ?float
    {
        if ($value === '' || $value === null) return null;
        return (float)$value;
    }


    public function addStudent(): void
    {
        if (
            !isset($_POST['class_id'], $_POST['first_name'], $_POST['last_name'])
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        $studentId = $this->StudentModel->addStudent(
            (int) $_POST['class_id'],
            trim($_POST['first_name']),
            trim($_POST['last_name'])
        );

        JsonHelpers::json(201, [
            'message'    => 'Student added successfully',
            'student_id' => $studentId
        ]);
    }

    /**
     * Update student by ID
     * POST: id, class_id, first_name, last_name
     */

    public function updateStudent(): void
    {
        if (
            !isset($_POST['id'], $_POST['class_id'], $_POST['first_name'], $_POST['last_name'])
        ) {
            $id = $_POST['id'];
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        $success = $this->StudentModel->updateStudent(
            (int) $_POST['id'],
            (int) $_POST['class_id'],
            trim($_POST['first_name']),
            trim($_POST['last_name'])
        );

        JsonHelpers::json($success ? 200 : 500, [
            'success' => $success
        ]);
    }

    /**
     * Delete student by ID
     * POST: id
     */

    public function deleteStudent(): void
    {
        if (!isset($_POST['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Student ID is required']);
            return;
        }

        $success = $this->StudentModel->deleteStudent((int) $_POST['id']);

        JsonHelpers::json($success ? 200 : 500, [
            'success' => $success
        ]);
    }

    /**
     * Save / update student scores
     * POST:
     * student_id, subject_id, school_year,
     * first_term, second_term, third_term
     */

    public function saveScores(): void
    {
        $required = ['student_id', 'subject_id', 'school_year'];

        foreach ($required as $field) {
            if (!isset($_POST[$field]) || $_POST[$field] === '') {
                JsonHelpers::json(400, ['error' => "$field is required"]);
                return;
            }
        }

        $first  = $this->scoreOrNull($_POST['first_term'] ?? null);
        $second = $this->scoreOrNull($_POST['second_term'] ?? null);
        $third  = $this->scoreOrNull($_POST['third_term'] ?? null);

        if ($first === null && $second === null && $third === null) {
            JsonHelpers::json(400, ['error' => "At least one term score is required"]);
            return;
        }

        $success = $this->StudentModel->saveScores(
            (int) $_POST['student_id'],
            (int) $_POST['subject_id'],
            (int) $_POST['school_year'],
            $first,
            $second,
            $third
        );

        JsonHelpers::json($success ? 200 : 500, ['success' => $success]);
    }


    /**
     * Get student scores
     * GET: student_id, school_year
     */

    public function getStudentScores(): void
    {
        if (!isset($_GET['student_id'], $_GET['school_year'])) {
            http_response_code(400);
            echo json_encode(['error' => 'student_id and school_year are required']);
            return;
        }

        $scores = $this->StudentModel->getStudentScores(
            (int) $_GET['student_id'],
            (int) $_GET['school_year']
        );

        JsonHelpers::json(200, [
            'scores' => $scores

        ]);
    }
}
