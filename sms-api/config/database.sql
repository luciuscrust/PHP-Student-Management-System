-- Once db and tables have been created by db.php, the sql contained in this
-- file can be run to add padding data

USE sms_db;

-- =======================
-- THIS IS HERE FOR INSERTING SEED DATA (CAN BE COPIED AND PASTED)
-- =======================

-- 6 grades (1–6)
INSERT INTO grades (grade_no) VALUES
(1),(2),(3),(4),(5),(6);

-- 4 classes per grade: 1A–1D, 2A–2D, ..., 6A–6D
INSERT INTO classes (grade_id, `class`) VALUES
((SELECT id FROM grades WHERE grade_no=1), '1A'),
((SELECT id FROM grades WHERE grade_no=1), '1B'),
((SELECT id FROM grades WHERE grade_no=1), '1C'),
((SELECT id FROM grades WHERE grade_no=1), '1D'),

((SELECT id FROM grades WHERE grade_no=2), '2A'),
((SELECT id FROM grades WHERE grade_no=2), '2B'),
((SELECT id FROM grades WHERE grade_no=2), '2C'),
((SELECT id FROM grades WHERE grade_no=2), '2D'),

((SELECT id FROM grades WHERE grade_no=3), '3A'),
((SELECT id FROM grades WHERE grade_no=3), '3B'),
((SELECT id FROM grades WHERE grade_no=3), '3C'),
((SELECT id FROM grades WHERE grade_no=3), '3D'),

((SELECT id FROM grades WHERE grade_no=4), '4A'),
((SELECT id FROM grades WHERE grade_no=4), '4B'),
((SELECT id FROM grades WHERE grade_no=4), '4C'),
((SELECT id FROM grades WHERE grade_no=4), '4D'),

((SELECT id FROM grades WHERE grade_no=5), '5A'),
((SELECT id FROM grades WHERE grade_no=5), '5B'),
((SELECT id FROM grades WHERE grade_no=5), '5C'),
((SELECT id FROM grades WHERE grade_no=5), '5D'),

((SELECT id FROM grades WHERE grade_no=6), '6A'),
((SELECT id FROM grades WHERE grade_no=6), '6B'),
((SELECT id FROM grades WHERE grade_no=6), '6C'),
((SELECT id FROM grades WHERE grade_no=6), '6D');

-- 4 subjects per grade: Math, Grammar, Science, Social Studies
INSERT INTO subjects (grade_id, name)
SELECT g.id, s.name
FROM grades g
JOIN (
  SELECT 'Math' AS name
  UNION ALL SELECT 'Grammar'
  UNION ALL SELECT 'Science'
  UNION ALL SELECT 'Social Studies'
) s;

-- Users
-- teacher password: iamtheteacher123#
-- admin password:   iamtheadmin123#
INSERT INTO users (role, email, password_hash, class_id) VALUES
(
  'teacher',
  'teacher@teacher.com',
  '$2y$10$flMW.BX/1HNJ0Xtwd.sFXuyRL9hpjMI/e2HoKnJED2plS/Ozbr4DS',
  (SELECT id FROM classes WHERE `class`='1A' LIMIT 1)
),
(
  'admin',
  'admin@admin.com',
  '$2y$10$dYmEecQgS6Q70VvZgM9/6e6L0ny3MZk8GdTqgQxPUncM3r.0jRkE2',
  NULL
);

-- 30 students in class 1A (We decided to only populate class 1A with students for the time being)
INSERT INTO students (class_id, first_name, last_name) VALUES
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Asha', 'Singh'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'David', 'Persaud'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Ravi', 'Ali'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Mia', 'Williams'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Jayden', 'Thomas'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Kyla', 'Fraser'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Noah', 'Johnson'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Amaya', 'Adams'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Ethan', 'James'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Zara', 'Mohammed'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Liam', 'Carter'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Aaliyah', 'Rodrigues'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Daniel', 'Harris'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Priya', 'Browne'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Samuel', 'Benn'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Nia', 'Ramkissoon'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Joshua', 'Jordan'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Keisha', 'Grant'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Marcus', 'Davis'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Anika', 'Edwards'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Ryan', 'Hughes'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Leila', 'Campbell'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Omar', 'Jackson'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Sofia', 'Baptiste'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Caleb', 'Clarke'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Hannah', 'Phillips'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Isaiah', 'Barnes'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Naomi', 'Walsh'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Andre', 'Joseph'),
((SELECT id FROM classes WHERE `class`='1A' LIMIT 1), 'Talia', 'Hope');

-- ==========================================
-- RANDOM SCORES SEED (Grade 1, Class 1A)
-- Creates scores for Math, Grammar, Science, Social Studies
-- for Term 1, Term 2, Term 3 in the same row
-- ==========================================

-- School year to seed (In this case 2025)
SET @seed_year = 2025;

-- Insert one scores row per (student, subject, year)
-- Random range: 40–100
INSERT INTO scores (subject_id, student_id, school_year, first_term, second_term, third_term)
SELECT
  sub.id AS subject_id,
  stu.id AS student_id,
  @seed_year AS school_year,

  -- Term 1
  ROUND(40 + (RAND() * 60), 2) AS first_term,

  -- Term 2
  ROUND(40 + (RAND() * 60), 2) AS second_term,

  -- Term 3
  ROUND(40 + (RAND() * 60), 2) AS third_term
FROM students stu
JOIN classes c
  ON c.id = stu.class_id
JOIN grades g
  ON g.id = c.grade_id
JOIN subjects sub
  ON sub.grade_id = g.id
WHERE c.`class` = '1A'
  AND g.grade_no = 1
  AND sub.name IN ('Math', 'Grammar', 'Science', 'Social Studies')
ON DUPLICATE KEY UPDATE
  first_term  = VALUES(first_term),
  second_term = VALUES(second_term),
  third_term  = VALUES(third_term);