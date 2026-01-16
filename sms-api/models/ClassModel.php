<?php

require_once __DIR__ . '/../config/db.php';

class ClassModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getClassesByGrade($gradeId)
    {
        $sql = "SELECT id, class 
                FROM classes 
                WHERE grade_id = :grade_id
                ORDER BY class";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['grade_id' => $gradeId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
