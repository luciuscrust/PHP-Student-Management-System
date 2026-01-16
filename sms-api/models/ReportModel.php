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

    public function getMostRecentYearOverall(): ?int
    {
        $sql = "SELECT MAX(school_year) AS year FROM scores";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['year'] !== null ? (int)$row['year'] : null;
    }

    public function getGradeIdForClass(int $classId): ?int
    {
        $sql = "SELECT grade_id FROM classes WHERE id = :class_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':class_id' => $classId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row && $row['grade_id'] !== null ? (int)$row['grade_id'] : null;
    }

    /**
     * Admin scope: all grades, grouped by grade + subject
     * $term: first_term|second_term|third_term|overall
     */

    public function getGradeSubjectAveragesRows(int $year, array $subjects, string $term): array
    {
        $termExpr = $this->buildTermExpr($term);

        $placeholders = [];
        $params = [':year' => $year];

        foreach ($subjects as $i => $sub) {
            $ph = ":sub_$i";
            $placeholders[] = $ph;
            $params[$ph] = $sub;
        }

        $in = implode(',', $placeholders);

        $sql = "
        SELECT
            g.grade_no,
            sub.name AS subject_name,
            AVG($termExpr) AS avg_score,
            COUNT($termExpr) AS count_scores
        FROM scores sc
        JOIN students st ON st.id = sc.student_id
        JOIN classes c ON c.id = st.class_id
        JOIN grades g ON g.id = c.grade_id
        JOIN subjects sub ON sub.id = sc.subject_id
        WHERE sc.school_year = :year
          AND sub.name IN ($in)
          AND $termExpr IS NOT NULL
        GROUP BY g.grade_no, sub.name
        ORDER BY g.grade_no ASC, sub.name ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Teacher scope: restrict to a specific grade_id
     */

    public function getGradeSubjectAveragesRowsByGrade(int $gradeId, int $year, array $subjects, string $term): array
    {
        $termExpr = $this->buildTermExpr($term);

        $placeholders = [];
        $params = [
            ':year' => $year,
            ':grade_id' => $gradeId
        ];

        foreach ($subjects as $i => $sub) {
            $ph = ":sub_$i";
            $placeholders[] = $ph;
            $params[$ph] = $sub;
        }

        $in = implode(',', $placeholders);

        $sql = "
        SELECT
            g.grade_no,
            sub.name AS subject_name,
            AVG($termExpr) AS avg_score,
            COUNT($termExpr) AS count_scores
        FROM scores sc
        JOIN students st ON st.id = sc.student_id
        JOIN classes c ON c.id = st.class_id
        JOIN grades g ON g.id = c.grade_id
        JOIN subjects sub ON sub.id = sc.subject_id
        WHERE sc.school_year = :year
          AND g.id = :grade_id
          AND sub.name IN ($in)
          AND $termExpr IS NOT NULL
        GROUP BY g.grade_no, sub.name
        ORDER BY g.grade_no ASC, sub.name ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Builds SQL expression for term averaging.
     * - overall = average of available terms per score row (ignores NULLs)
     * - otherwise uses one term column
     */

    private function buildTermExpr(string $term): string
    {
        if ($term === 'first_term') return 'sc.first_term';
        if ($term === 'second_term') return 'sc.second_term';
        if ($term === 'third_term') return 'sc.third_term';

        return "(
        (COALESCE(sc.first_term,0) + COALESCE(sc.second_term,0) + COALESCE(sc.third_term,0))
        /
        NULLIF(
            (CASE WHEN sc.first_term IS NULL THEN 0 ELSE 1 END)
          + (CASE WHEN sc.second_term IS NULL THEN 0 ELSE 1 END)
          + (CASE WHEN sc.third_term IS NULL THEN 0 ELSE 1 END),
        0)
    )";
    }
}
