<?php

require_once __DIR__ . '/../config/db.php';

class GradeModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT id, grade_no FROM grades ORDER BY grade_no ASC");
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, grade_no FROM grades WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getByGradeNo(int $gradeNo): ?array
    {
        $stmt = $this->db->prepare("SELECT id, grade_no FROM grades WHERE grade_no = :grade_no LIMIT 1");
        $stmt->execute([':grade_no' => $gradeNo]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(int $gradeNo): int
    {
        $stmt = $this->db->prepare("INSERT INTO grades (grade_no) VALUES (:grade_no)");
        $stmt->execute([':grade_no' => $gradeNo]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $gradeNo): bool
    {
        $stmt = $this->db->prepare("UPDATE grades SET grade_no = :grade_no WHERE id = :id");
        $stmt->execute([':grade_no' => $gradeNo, ':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM grades WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
