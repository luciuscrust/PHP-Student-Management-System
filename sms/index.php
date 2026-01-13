<?php
session_start();

if (!empty($_SESSION['user'])) {
	$role = $_SESSION['user']['role'] ?? null;

	if ($role === 'admin') {
		header('Location: ./views/admin/dashboard.php');
		exit;
	}

	if ($role === 'teacher') {
		header('Location: ./views/teacher/dashboard.php');
		exit;
	}
}

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];
?>
<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1" />
	<title>Login - School Management System</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-50 flex items-center justify-center">
	<div class="w-full max-w-md p-8 bg-white rounded shadow">
		<h1 class="text-2xl font-semibold mb-6 text-center">School Management System<br />Login</h1>

		<?php if (!empty($_SESSION['flash'])): ?>
			<div class="mb-4 p-3 rounded text-sm <?= htmlspecialchars($_SESSION['flash_type'] ?? 'text-red-700 bg-red-100') ?>">
				<?= htmlspecialchars($_SESSION['flash']) ?>
			</div>
			<?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
		<?php endif; ?>

		<form id="loginForm" action="./views/auth/authenticate.php" method="POST" novalidate>
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

			<label class="block mb-1 text-sm font-medium">Email</label>
			<input
				required
				name="email"
				type="email"
				class="mb-3 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-400"
				placeholder="you@example.com" />

			<label class="block mb-1 text-sm font-medium">Password</label>
			<input
				required
				name="password"
				id="password"
				type="password"
				class="mb-2 block w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-indigo-400"
				placeholder="Enter your password"
				minlength="8"
				aria-describedby="pwHelp" />
			<p id="pwHelp" class="text-xs text-gray-500 mb-4">
				Password must be at least 8 characters, include letters, numbers and a symbol.
			</p>

			<button
				type="submit"
				class="w-full py-2 px-4 bg-indigo-600 text-white rounded hover:bg-indigo-700">
				Sign in
			</button>
		</form>
	</div>

	<script>
		(function() {
			const form = document.getElementById('loginForm');
			const pwd = document.getElementById('password');

			const pattern = /(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}/;

			form.addEventListener('submit', function(e) {
				const value = pwd.value || '';
				if (!pattern.test(value)) {
					e.preventDefault();
					alert('Password must be at least 8 characters and include letters, numbers and a symbol.');
					pwd.focus();
				}
			});
		})();
	</script>
</body>

</html>