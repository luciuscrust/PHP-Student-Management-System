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
}
