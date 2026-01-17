<?php

require_once __DIR__ . '/../config/db.php';

class StudentModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Add a new student
     */

    public function addStudent(int $classId, string $firstName, string $lastName): int
    {
        $sql = "INSERT INTO students (class_id, first_name, last_name)
                VALUES (:class_id, :first_name, :last_name)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'class_id'   => $classId,
            'first_name' => $firstName,
            'last_name'  => $lastName
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update student details by ID
     */

    public function updateStudent(
        int $studentId,
        int $classId,
        string $firstName,
        string $lastName
    ): bool {
        $sql = "UPDATE students
                SET class_id = :class_id,
                    first_name = :first_name,
                    last_name = :last_name
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'class_id'   => $classId,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'id'         => $studentId
        ]);
    }

    /**
     * Delete a student and all their scores
     */

    public function deleteStudent(int $studentId): bool
    {
        try {
            $this->db->beginTransaction();

            $this->db->prepare(
                "DELETE FROM scores WHERE student_id = :id"
            )->execute(['id' => $studentId]);

            $this->db->prepare(
                "DELETE FROM students WHERE id = :id"
            )->execute(['id' => $studentId]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Add or update scores for a student per subject and school year
     */

    public function saveScores(
        int $studentId,
        int $subjectId,
        int $schoolYear,
        float $firstTerm,
        float $secondTerm,
        float $thirdTerm
    ): bool {
        $sql = "INSERT INTO scores 
                (student_id, subject_id, school_year, first_term, second_term, third_term)
                VALUES
                (:student_id, :subject_id, :school_year, :first_term, :second_term, :third_term)
                ON DUPLICATE KEY UPDATE
                    first_term = VALUES(first_term),
                    second_term = VALUES(second_term),
                    third_term = VALUES(third_term)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'student_id'  => $studentId,
            'subject_id'  => $subjectId,
            'school_year' => $schoolYear,
            'first_term'  => $firstTerm,
            'second_term' => $secondTerm,
            'third_term'  => $thirdTerm
        ]);
    }

    /**
     * Get a student with all scores
     */
    public function getStudentScores(int $studentId, int $schoolYear): array
    {
        $sql = "SELECT 
                    subjects.name AS subject,
                    scores.first_term,
                    scores.second_term,
                    scores.third_term
                FROM scores
                JOIN subjects ON subjects.id = scores.subject_id
                WHERE scores.student_id = :student_id
                  AND scores.school_year = :school_year
                ORDER BY subjects.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'student_id'  => $studentId,
            'school_year' => $schoolYear
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
