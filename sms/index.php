<?php
session_start();

if (!empty($_SESSION['user'])) {
	$role = $_SESSION['user']['role'] ?? null;

	if ($role === 'admin') {
		header('Location: ./views/admin/dashboard.php');
		exit;
	}

	if ($role === 'teacher') {
		header('Location: ./views/shared/student.php');
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

		<form id="loginForm" novalidate>
			<input type="hidden" name="csrf_token" id="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

			<label class="block mb-1 text-sm font-medium">Email</label>
			<input
				required
				name="email"
				id="email"
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

			<div id="errBox" class="hidden mb-4 p-3 rounded text-sm text-red-700 bg-red-100"></div>

			<button
				id="submitBtn"
				type="submit"
				class="w-full py-2 px-4 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-70 disabled:cursor-not-allowed">
				Sign in
			</button>
		</form>

		<script>
			(() => {
				const form = document.getElementById('loginForm');
				const emailEl = document.getElementById('email');
				const pwdEl = document.getElementById('password');
				const csrfEl = document.getElementById('csrf_token');
				const errBox = document.getElementById('errBox');
				const submitBtn = document.getElementById('submitBtn');

				const pattern = /(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}/;

				function showError(msg) {
					errBox.textContent = msg;
					errBox.classList.remove('hidden');
				}

				function clearError() {
					errBox.textContent = '';
					errBox.classList.add('hidden');
				}

				form.addEventListener('submit', async (e) => {
					e.preventDefault();
					clearError();

					const email = (emailEl.value || '').trim();
					const password = pwdEl.value || '';

					if (!email) {
						showError('Please enter your email address.');
						emailEl.focus();
						return;
					}
					if (!pattern.test(password)) {
						showError('Password must be at least 8 characters and include letters, numbers and a symbol.');
						pwdEl.focus();
						return;
					}

					submitBtn.disabled = true;
					submitBtn.textContent = 'Signing in...';

					try {
						const apiRes = await fetch('http://localhost/PHP-Student-Management-System/sms-api/auth/login', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json',
								'Accept': 'application/json'
							},
							credentials: 'include',
							body: JSON.stringify({
								email,
								password
							})
						});

						let apiData = null;
						try {
							apiData = await apiRes.json();
						} catch (_) {}

						if (!apiRes.ok || !apiData || !apiData.success) {
							showError((apiData && apiData.message) ? apiData.message : 'Invalid credentials.');
							return;
						}

						const sessRes = await fetch('./views/auth/session_login.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/json'
							},
							body: JSON.stringify({
								csrf_token: csrfEl.value,
								user: apiData.user
							})
						});

						const sessData = await sessRes.json();

						if (!sessRes.ok || !sessData || !sessData.url) {
							showError((sessData && sessData.error) ? sessData.error : 'Login failed while creating session.');
							return;
						}

						window.location.href = sessData.url;

					} catch (err) {
						showError('Server error. Try again later.');
					} finally {
						submitBtn.disabled = false;
						submitBtn.textContent = 'Sign in';
					}
				});
			})();
		</script>

</body>

</html>