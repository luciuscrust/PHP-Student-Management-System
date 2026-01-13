<?php
session_start();

require_once __DIR__ . '../../../helpers/RoleGuard.php';

requireRole('teacher');

$user = $_SESSION['user'];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Teacher Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100 p-6">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl font-semibold">Welcome, Teacher: <?= htmlspecialchars($user['email']) ?></h1>
        <p class="mt-4">
            <a href="../auth/logout.php" class="text-indigo-600 hover:underline">Logout</a>
        </p>
    </div>

</body>

</html>