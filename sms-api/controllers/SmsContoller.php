<?php

require_once __DIR__ . '/../models/SmsModel.php';

class SmsController
{
    private SmsModel $smsModel;

    public function __construct()
    {
        $this->smsModel = new SmsModel();
    }

    public function getGrades(): void
    {
        $rows = $this->smsModel->getGrades();

        $grades = array_map(function ($rows) {
            return [
                'id'        => (int)$rows['id'],
                'grade_no'  => $rows['grade_no'],
            ];
        }, $rows);

        http_response_code(200);
        echo json_encode([
            'grades' => $grades
        ]);
    }
}
