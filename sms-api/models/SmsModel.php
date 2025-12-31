<?php

require_once __DIR__ . '/../config/db.php';

class SmsModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getGrades(): array
    {
        $sql = "SELECT id, grade_no FROM grades";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
