<?php
session_start();

require_once __DIR__ . '../../../helpers/RoleGuard.php';
requireRole('admin');

$user = $_SESSION['user'] ?? ['email' => ''];
?>
<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width,initial-scale=1" />
	<title>Admin Dashboard</title>
	<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100 p-6">
	<div class="max-w-6xl mx-auto space-y-6">

		<div class="bg-white p-6 rounded shadow">
			<div class="flex items-start justify-between gap-4">
				<div>
					<h1 class="text-xl font-semibold">Welcome, Admin</h1>
					<p class="text-sm text-gray-600 mt-1">Manage users and view grades.</p>
				</div>

				<a href="../auth/logout.php" class="text-indigo-600 hover:underline">Logout</a>
			</div>
		</div>

		<!-- USERS: Create + Delete -->
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

			<!-- Create User -->
			<div class="bg-white p-6 rounded shadow space-y-4">
				<div class="flex items-center justify-between">
					<h2 class="text-lg font-semibold">Create User</h2>
					<span id="userStatus" class="hidden text-xs px-2 py-1 rounded bg-gray-100 text-gray-700"></span>
				</div>

				<div id="userErrBox" class="hidden p-3 rounded text-sm text-red-700 bg-red-100"></div>
				<div id="userOkBox" class="hidden p-3 rounded text-sm text-green-700 bg-green-100"></div>

				<form id="createUserForm" class="space-y-3" autocomplete="off">
					<div>
						<label class="block mb-1 text-sm font-medium">Role</label>
						<select id="role" class="w-full border rounded px-3 py-2">
							<option value="teacher">teacher</option>
							<option value="admin">admin</option>
						</select>
						<p class="text-xs text-gray-500 mt-1">Admins cannot be deleted via UI.</p>
					</div>

					<div>
						<label class="block mb-1 text-sm font-medium">Email</label>
						<input id="email" type="email" class="w-full border rounded px-3 py-2" placeholder="user@example.com" required />
					</div>

					<div>
						<label class="block mb-1 text-sm font-medium">Password</label>
						<input id="password" type="password" class="w-full border rounded px-3 py-2" placeholder="StrongPass123#" required />
						<p class="text-xs text-gray-500 mt-1">8+ chars, letters + numbers + symbol.</p>
					</div>

					<div id="classWrap">
						<label class="block mb-1 text-sm font-medium">Class (teacher only)</label>
						<select id="class_id" class="w-full border rounded px-3 py-2">
							<option value="">Select Class</option>
						</select>
						<p class="text-xs text-gray-500 mt-1">Required when role = teacher.</p>
					</div>

					<button type="submit" class="w-full py-2 px-4 bg-indigo-600 text-white rounded hover:bg-indigo-700">
						Create User
					</button>
				</form>
			</div>

			<!-- Delete User -->
			<div class="bg-white p-6 rounded shadow space-y-4">
				<h2 class="text-lg font-semibold">Delete User</h2>

				<div class="text-sm text-gray-600">
					Delete by ID (teachers / non-admins only). Admin users cannot be deleted.
				</div>

				<div class="flex gap-2">
					<input id="deleteUserId" type="number" class="flex-1 border rounded px-3 py-2" placeholder="User ID e.g. 5" min="1" />
					<button id="deleteBtn" type="button" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
						Delete
					</button>
				</div>

				<div id="delErrBox" class="hidden p-3 rounded text-sm text-red-700 bg-red-100"></div>
				<div id="delOkBox" class="hidden p-3 rounded text-sm text-green-700 bg-green-100"></div>
			</div>

		</div>

		<!-- GRADES -->
		<div class="bg-white p-6 rounded shadow">
			<div class="flex items-center justify-between gap-4 mb-4">
				<h2 class="text-lg font-semibold">Grades</h2>

				<div class="flex items-center gap-2">
					<span id="statusPill" class="hidden text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">Loading…</span>
					<button id="refreshBtn" type="button" class="text-sm px-3 py-2 rounded border hover:bg-gray-50">
						Refresh
					</button>
				</div>
			</div>

			<div id="errBox" class="hidden mb-4 p-3 rounded text-sm text-red-700 bg-red-100"></div>

			<div id="loading" class="space-y-3">
				<div class="h-12 bg-gray-100 rounded animate-pulse"></div>
				<div class="h-12 bg-gray-100 rounded animate-pulse"></div>
				<div class="h-12 bg-gray-100 rounded animate-pulse"></div>
			</div>

			<ul id="gradesList" class="hidden divide-y"></ul>

			<p id="emptyState" class="hidden text-sm text-gray-600">
				No grades were returned from the API.
			</p>
		</div>
	</div>

	<script>
		(() => {
			const API_BASE = 'http://localhost/PHP-Student-Management-System/sms-api';

			const show = (el) => el.classList.remove('hidden');
			const hide = (el) => el.classList.add('hidden');

			function escapeHtml(str) {
				return String(str ?? '')
					.replaceAll('&', '&amp;')
					.replaceAll('<', '&lt;')
					.replaceAll('>', '&gt;')
					.replaceAll('"', '&quot;')
					.replaceAll("'", '&#039;');
			}

			async function apiRequest(path, options = {}) {
				const res = await fetch(`${API_BASE}${path}`, {
					credentials: 'include',
					headers: {
						'Accept': 'application/json',
						...(options.headers || {})
					},
					...options
				});

				let data = null;
				try {
					data = await res.json();
				} catch (_) {}

				if (!res.ok) {
					throw new Error(data?.error || data?.message || `Request failed (HTTP ${res.status}).`);
				}
				return data;
			}

			// =========================================================
			// USERS: Create + Delete
			// =========================================================

			const createUserForm = document.getElementById('createUserForm');
			const roleEl = document.getElementById('role');
			const emailEl = document.getElementById('email');
			const passwordEl = document.getElementById('password');
			const classWrap = document.getElementById('classWrap');
			const classIdEl = document.getElementById('class_id');

			const userStatus = document.getElementById('userStatus');
			const userErrBox = document.getElementById('userErrBox');
			const userOkBox = document.getElementById('userOkBox');

			const deleteUserIdEl = document.getElementById('deleteUserId');
			const deleteBtn = document.getElementById('deleteBtn');
			const delErrBox = document.getElementById('delErrBox');
			const delOkBox = document.getElementById('delOkBox');

			function setUserStatus(text) {
				if (!text) {
					hide(userStatus);
					userStatus.textContent = '';
					return;
				}
				userStatus.textContent = text;
				show(userStatus);
			}

			function clearUserMsgs() {
				hide(userErrBox);
				hide(userOkBox);
				userErrBox.textContent = '';
				userOkBox.textContent = '';
			}

			function showUserError(msg) {
				userErrBox.textContent = msg || 'Something went wrong.';
				show(userErrBox);
				hide(userOkBox);
			}

			function showUserOk(msg) {
				userOkBox.textContent = msg || 'Success.';
				show(userOkBox);
				hide(userErrBox);
				setTimeout(() => hide(userOkBox), 2500);
			}

			function syncRoleUI() {
				const role = roleEl.value;
				if (role === 'teacher') show(classWrap);
				else hide(classWrap);
			}
			roleEl.addEventListener('change', syncRoleUI);
			syncRoleUI();


			async function loadAllClassesIntoDropdown() {
				classIdEl.innerHTML = '<option value="">Select Class</option>';

				try {
					const gradesRes = await apiRequest('/grades', {
						method: 'GET'
					});
					const grades = gradesRes?.grades;
					if (!Array.isArray(grades)) return;

					for (const g of grades) {
						const gradeId = g.id;
						const gradeNo = g.grade_no;

						const clsRes = await apiRequest(`/get-classes?grade_id=${encodeURIComponent(gradeId)}`, {
							method: 'GET'
						});
						const classes =
							Array.isArray(clsRes?.data) ? clsRes.data :
							Array.isArray(clsRes?.classes) ? clsRes.classes : [];

						if (!classes.length) continue;

						const og = document.createElement('optgroup');
						og.label = `Grade ${gradeNo}`;

						for (const c of classes) {
							const id = c.id;
							const name = c.name ?? c.class ?? `Class ${id}`;
							const opt = new Option(String(name), String(id));
							og.appendChild(opt);
						}
						classIdEl.appendChild(og);
					}
				} catch (_) {}
			}
			loadAllClassesIntoDropdown();

			createUserForm.addEventListener('submit', async (e) => {
				e.preventDefault();
				clearUserMsgs();

				const role = roleEl.value;
				const email = (emailEl.value || '').trim();
				const password = passwordEl.value || '';
				const class_id = classIdEl.value ? Number(classIdEl.value) : null;

				if (!email) return showUserError('Email is required.');
				if (!password) return showUserError('Password is required.');
				if (role === 'teacher' && (!class_id || class_id <= 0)) return showUserError('Class is required for teacher.');

				setUserStatus('Creating user…');

				try {
					const payload = {
						role,
						email,
						password,
						class_id
					};

					if (role === 'admin') payload.class_id = null;

					const res = await apiRequest('/users', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json'
						},
						body: JSON.stringify(payload)
					});

					showUserOk(`User created (ID: ${res?.user?.id ?? '—'})`);

					emailEl.value = '';
					passwordEl.value = '';
					classIdEl.value = '';
				} catch (err) {
					showUserError(err?.message || 'Failed to create user.');
				} finally {
					setUserStatus('');
				}
			});

			deleteBtn.addEventListener('click', async () => {
				hide(delErrBox);
				hide(delOkBox);
				delErrBox.textContent = '';
				delOkBox.textContent = '';

				const id = Number(deleteUserIdEl.value);
				if (!id || id <= 0) {
					delErrBox.textContent = 'Please enter a valid user ID.';
					show(delErrBox);
					return;
				}

				if (!confirm(`Delete user #${id}? Admin users cannot be deleted.`)) return;

				try {
					deleteBtn.disabled = true;

					await apiRequest(`/users?id=${encodeURIComponent(id)}`, {
						method: 'DELETE'
					});

					delOkBox.textContent = `User #${id} deleted successfully.`;
					show(delOkBox);
					deleteUserIdEl.value = '';
				} catch (err) {
					delErrBox.textContent = err?.message || 'Failed to delete user.';
					show(delErrBox);
				} finally {
					deleteBtn.disabled = false;
					setTimeout(() => hide(delOkBox), 2500);
				}
			});

			// =========================================================
			// Grades + averages
			// =========================================================

			const listEl = document.getElementById('gradesList');
			const errBox = document.getElementById('errBox');
			const loading = document.getElementById('loading');
			const emptyState = document.getElementById('emptyState');
			const refreshBtn = document.getElementById('refreshBtn');
			const statusPill = document.getElementById('statusPill');

			let avgByGradeNo = {};

			function setStatus(text) {
				if (!text) {
					hide(statusPill);
					statusPill.textContent = '';
					return;
				}
				statusPill.textContent = text;
				show(statusPill);
			}

			function showError(msg) {
				errBox.textContent = msg || 'Something went wrong.';
				show(errBox);
			}

			function clearError() {
				errBox.textContent = '';
				hide(errBox);
			}

			function setLoading(isLoading) {
				if (isLoading) {
					setStatus('Loading…');
					show(loading);
					hide(listEl);
					hide(emptyState);
				} else {
					setStatus('');
					hide(loading);
				}
			}

			async function apiGet(path) {
				return apiRequest(path, {
					method: 'GET'
				});
			}

			async function loadGradeAverages() {
				try {
					const data = await apiGet('/grade-subject-averages');

					const grades = data?.grades;
					if (!Array.isArray(grades)) {
						avgByGradeNo = {};
						return;
					}

					const map = {};
					for (const g of grades) {
						const gradeNo = Number(g?.grade_no);
						const subjects = Array.isArray(g?.subjects) ? g.subjects : [];
						if (!Number.isFinite(gradeNo)) continue;

						map[gradeNo] = subjects.map(s => ({
							subject_name: s.subject_name ?? s.name ?? '',
							avg: (s.avg !== null && s.avg !== undefined) ? Number(s.avg) : null,
							count: (s.count !== null && s.count !== undefined) ? Number(s.count) : null
						}));
					}

					avgByGradeNo = map;

				} catch (e) {
					avgByGradeNo = {};
				}
			}

			function renderGrades(grades) {
				listEl.innerHTML = '';

				if (!Array.isArray(grades) || grades.length === 0) {
					hide(listEl);
					show(emptyState);
					return;
				}

				hide(emptyState);
				show(listEl);

				for (const g of grades) {
					const li = document.createElement('li');

					const subjectAvgs = avgByGradeNo?.[g.grade_no] || [];
					const avgHtml = subjectAvgs.length ?
						`
						<div class="mt-2 flex flex-wrap gap-2">
							${subjectAvgs.slice(0, 8).map(s => {
								const label = escapeHtml(s.subject_name || 'Subject');
								const avg = (s.avg === null || Number.isNaN(s.avg)) ? '-' : Number(s.avg).toFixed(2);
								const count = (s.count === null || Number.isNaN(s.count)) ? '' : ` • n=${escapeHtml(String(s.count))}`;
								return `
									<span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">
										${label}: <span class="font-medium">${escapeHtml(String(avg))}</span>${count}
									</span>
								`;
							}).join('')}
						</div>
					  ` :
						`<div class="mt-2 text-xs text-gray-500">No averages available.</div>`;

					li.innerHTML = `
					<button
						type="button"
						class="w-full text-left p-4 hover:bg-gray-50 flex items-start justify-between gap-4"
						data-grade-id="${String(g.id)}"
						data-grade-name="${String(g.name ?? '')}">
						<div class="min-w-0">
							<div class="font-medium text-gray-900">${escapeHtml(g.name ?? 'Unnamed Grade')}</div>
							${avgHtml}
						</div>
					</button>
				`;

					li.querySelector('button').addEventListener('click', () => {
						const gradeId = g.id;
						const gradeName = g.name;

						try {
							localStorage.setItem('selected_grade_id', String(gradeId));
							localStorage.setItem('selected_grade_name', String(gradeName));
						} catch (_) {}

						window.location.href = `./class.php?grade_id=${encodeURIComponent(gradeId)}`;
					});

					listEl.appendChild(li);
				}
			}

			async function loadGradesAndAverages() {
				clearError();
				setLoading(true);

				try {
					setStatus('Loading grades…');

					const [gradesRes] = await Promise.all([
						apiGet('/grades'),
						loadGradeAverages()
					]);

					const grades = gradesRes?.grades;
					if (!Array.isArray(grades)) {
						throw new Error("Unexpected API response: missing grades array.");
					}

					const normalized = grades.map(g => ({
						id: g.id,
						name: `Grade ${g.grade_no}`,
						grade_no: Number(g.grade_no)
					}));

					renderGrades(normalized);

				} catch (err) {
					showError(err?.message || 'Could not load grades.');
					hide(listEl);
					hide(emptyState);
				} finally {
					setLoading(false);
				}
			}

			refreshBtn.addEventListener('click', loadGradesAndAverages);
			loadGradesAndAverages();
		})();
	</script>
</body>

</html>