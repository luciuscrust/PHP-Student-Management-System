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
	<div class="max-w-4xl mx-auto space-y-6">

		<div class="bg-white p-6 rounded shadow">
			<div class="flex items-start justify-between gap-4">
				<div>
					<h1 class="text-xl font-semibold">Welcome, Admin</h1>
					<p class="text-sm text-gray-600 mt-1">Select a grade to continue.</p>
				</div>

				<a href="../auth/logout.php" class="text-indigo-600 hover:underline">Logout</a>
			</div>
		</div>

		<div class="bg-white p-6 rounded shadow">
			<div class="flex items-center justify-between gap-4 mb-4">
				<h2 class="text-lg font-semibold">Grades</h2>

				<div class="flex items-center gap-2">
					<span id="statusPill" class="hidden text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">Loading…</span>
					<button
						id="refreshBtn"
						type="button"
						class="text-sm px-3 py-2 rounded border hover:bg-gray-50">
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

			const listEl = document.getElementById('gradesList');
			const errBox = document.getElementById('errBox');
			const loading = document.getElementById('loading');
			const emptyState = document.getElementById('emptyState');
			const refreshBtn = document.getElementById('refreshBtn');
			const statusPill = document.getElementById('statusPill');

			let avgByGradeNo = {};

			function show(el) {
				el.classList.remove('hidden');
			}

			function hide(el) {
				el.classList.add('hidden');
			}

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

			function escapeHtml(str) {
				return String(str ?? '')
					.replaceAll('&', '&amp;')
					.replaceAll('<', '&lt;')
					.replaceAll('>', '&gt;')
					.replaceAll('"', '&quot;')
					.replaceAll("'", '&#039;');
			}

			async function apiGet(path) {
				const res = await fetch(`${API_BASE}${path}`, {
					method: 'GET',
					headers: {
						'Accept': 'application/json'
					},
					credentials: 'include'
				});

				let data = null;
				try {
					data = await res.json();
				} catch (_) {}

				if (!res.ok) {
					throw new Error(data?.message || data?.error || `Request failed (HTTP ${res.status}).`);
				}

				return data;
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