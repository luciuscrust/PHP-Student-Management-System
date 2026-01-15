<?php
session_start();
if (empty($_SESSION['user'])){
  header('Location: index.php');
  exit;
}
$user = $_SESSION['user'];
$isTeacher = ($user['role']==='teacher');
$teacherClassId = $isTeacher ? ($user['class_id'] ?? null) : null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name = "viewport" content = "width=device-width, initial-scale=1" />
  <title>Class Students - SMS </title>
  <link rel="stylesheet" href="assets/class-students.css" >
</head>
<body>
  <!--Header-->
  <div class="header">
    <div class="header-content">
      <h1>Class Students & Scores</h1>
      <div class ="user-links">
        <span> <?= htmlspecialchars($user['email']) ?> (<?= htmlspecialchars($user['role'])?>)</span>
        <a href="dashboard.php">Dashboard</a>
        
      </div>
    </div>
  </div>
  <div class="container">
    <!--Filters-->
    <div class="filters">
      <h2>Select Class</h2>

      <div class="filters-grid">
        <?php if(!$isTeacher): ?>
          <div>
            <label> Grade</label>
            <select id="gradeSelect">
              <option value ="">Select Grade</option>
            </select>
          </div>

          <div>
            <label> Class </label>
            <select id="classSelect" disabled> 
              <option value ="">Select Class</option>
            </select>
          </div>
          <?php else: ?>
            <div class="info-text">
              <p>Your assigned class will be loaded automatically</p>
            </div>
            <?php endif; ?>

            <div>
              <button id="loadBtn">Load Students </button>
            </div>
      </div>

      <div id ="messageArea"></div>
    </div>
    <!--Students Table-->
    <div id="studentsContainer" class="students-table"> 
      <div class="table-header">
        <h2>Students</h2>
        <p id="classInfo"></p>
      </div>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>First Name</th>
              <th>Last Nmae</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody id="studentsTableBody"></tbody>
        </table>
      </div>
    </div>
  </div>
      
<!--Link to external JavaScript-->
<script src="assets/class-students.js"></script>
<script>
  initializeClassStudents(<?= json_encode($isTeacher) ?>);
  </script>
</body>

</html>