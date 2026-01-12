<?php

require_once __DIR__ . '/../config/db.php';

class ReportModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getMostRecentYearForClass(int $classId): ?int
    {
        $sql = "
        SELECT MAX(sc.school_year) AS year
        FROM scores sc
        JOIN students st ON st.id = sc.student_id
        WHERE st.class_id = :class_id
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':class_id' => $classId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['year'] !== null ? (int)$row['year'] : null;
    }


    public function getClassReportRows(int $classId, int $year): array
    {
        $sql = "
            SELECT
                st.id        AS student_id,
                st.first_name,
                st.last_name,
                st.class_id,

                sub.id       AS subject_id,
                sub.name     AS subject_name,

                sc.school_year,
                sc.first_term,
                sc.second_term,
                sc.third_term

            FROM students st
            JOIN classes c
              ON c.id = st.class_id
            JOIN grades g
              ON g.id = c.grade_id
            JOIN subjects sub
              ON sub.grade_id = g.id

            LEFT JOIN scores sc
              ON sc.student_id = st.id
             AND sc.subject_id = sub.id
             AND sc.school_year = :year

            WHERE st.class_id = :class_id
              AND sub.name IN ('Math', 'Grammar', 'Science', 'Social Studies')

            ORDER BY st.last_name ASC, st.first_name ASC, sub.name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':class_id' => $classId,
            ':year'     => $year
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMostRecentYearForStudent(int $studentId): ?int
    {
        $sql = "SELECT MAX(school_year) AS year FROM scores WHERE student_id = :student_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['year'] !== null ? (int)$row['year'] : null;
    }

    public function getStudentClassId(int $studentId): ?int
    {
        $sql = "SELECT class_id FROM students WHERE id = :student_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int)$row['class_id'] : null;
    }

    public function getStudentReportRows(int $studentId, int $year): array
    {
        $sql = "
        SELECT
            st.id AS student_id,
            st.first_name,
            st.last_name,
            st.class_id,

            c.class AS class_name,
            g.grade_no,

            sub.id AS subject_id,
            sub.name AS subject_name,

            sc.school_year,
            sc.first_term,
            sc.second_term,
            sc.third_term

        FROM students st
        JOIN classes c ON c.id = st.class_id
        JOIN grades g  ON g.id = c.grade_id
        JOIN subjects sub ON sub.grade_id = g.id

        LEFT JOIN scores sc
          ON sc.student_id = st.id
         AND sc.subject_id = sub.id
         AND sc.school_year = :year

        WHERE st.id = :student_id
          AND sub.name IN ('Math', 'Grammar', 'Science', 'Social Studies')

        ORDER BY sub.name ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':year'       => $year
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
