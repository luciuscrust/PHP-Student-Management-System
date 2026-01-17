<?php

use helpers\JsonHelpers;

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

        JsonHelpers::json(200, [
            'grades' => $grades
        ]);
    }
}
