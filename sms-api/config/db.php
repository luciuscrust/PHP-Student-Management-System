<?php

class Database
{
    private static ?PDO $conn = null;

    public static function getConnection(): PDO
    {
        if (self::$conn === null) {
            $host = 'localhost';
            $db   = 'sms_db';
            $user = 'root';
            $pass = '';

            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            self::$conn = new PDO($dsn, $user, $pass, $options);

            self::ensureSchema(self::$conn);
        }

        return self::$conn;
    }

    private static function ensureSchema(PDO $pdo): void
    {
        $statements = [

            // grades
            "CREATE TABLE IF NOT EXISTS grades (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                grade_no INT NOT NULL,
                UNIQUE KEY uniq_grade_no (grade_no)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            // classes
            "CREATE TABLE IF NOT EXISTS classes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                grade_id INT UNSIGNED NOT NULL,
                class VARCHAR(50) NOT NULL,
                UNIQUE KEY uniq_class (class),
                INDEX (grade_id),
                CONSTRAINT fk_classes_grade
                    FOREIGN KEY (grade_id) REFERENCES grades(id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            // users
            "CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                role VARCHAR(50) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                class_id INT UNSIGNED NULL,
                UNIQUE KEY uniq_users_email (email),
                INDEX (class_id),
                CONSTRAINT fk_users_class
                    FOREIGN KEY (class_id) REFERENCES classes(id)
                    ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            // students
            "CREATE TABLE IF NOT EXISTS students (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                class_id INT UNSIGNED NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                INDEX (class_id),
                CONSTRAINT fk_students_class
                    FOREIGN KEY (class_id) REFERENCES classes(id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            // subjects
            "CREATE TABLE IF NOT EXISTS subjects (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                grade_id INT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                INDEX (grade_id),
                CONSTRAINT fk_subjects_grade
                    FOREIGN KEY (grade_id) REFERENCES grades(id)
                    ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            // scores
            "CREATE TABLE IF NOT EXISTS scores (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                subject_id INT UNSIGNED NOT NULL,
                student_id INT UNSIGNED NOT NULL,
                school_year INT NOT NULL,

                first_term  DECIMAL(5,2) NULL,
                second_term DECIMAL(5,2) NULL,
                third_term  DECIMAL(5,2) NULL,

                UNIQUE KEY uniq_scores (student_id, subject_id, school_year),
                INDEX (subject_id),
                INDEX (student_id),

                CONSTRAINT fk_scores_subject
                    FOREIGN KEY (subject_id) REFERENCES subjects(id)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT fk_scores_student
                    FOREIGN KEY (student_id) REFERENCES students(id)
                    ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        ];

        foreach ($statements as $sql) {
            $pdo->exec($sql);
        }
    }
}
