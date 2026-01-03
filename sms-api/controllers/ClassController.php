<?php

require_once __DIR__ . '/../models/ClassModel.php';

class ClassController
{
    private ClassModel $ClassModel;

    public function __construct()
    {
        $this->ClassModel = new ClassModel();
    }

    public function getClassesByGrade(): void
    {
        if (!isset($_GET['grade_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'grade_id is required']);
            return;
        }

        $gradeId = (int) $_GET['grade_id'];

        $rows = $this->ClassModel->getClassesByGrade($gradeId);

        $classes = array_map(function ($row) {
            return [
                'id'    => (int) $row['id'],
                'class' => $row['class'],
            ];
        }, $rows);

        http_response_code(200);
        echo json_encode([
            'classes' => $classes
        ]);
    }
}
