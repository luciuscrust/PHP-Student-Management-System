<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user = $_SESSION['user'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 p-6">
  <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-xl font-semibold">Welcome, <?= htmlspecialchars($user['email']) ?></h1>
    <p class="mt-2">Role: <?= htmlspecialchars($user['role']) ?><?php if ($user['class_id']) echo ' — class id: '.(int)$user['class_id']; ?></p>
    <p class="mt-4">
      <a href="logout.php" class="text-indigo-600 hover:underline">Logout</a>
    </p>
  </div>
</body>
</html>