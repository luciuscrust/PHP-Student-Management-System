<?php
session_start();

$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: ../auth/index.php');
    exit;
}

$role = $user['role'] ?? '';
$isTeacher = ($role === 'teacher');

$apiBase = 'http://localhost/PHP-Student-Management-System/sms-api';

$classId = null;
if (!$isTeacher) {
    $classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Class Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100 p-6">
    <div class="max-w-5xl mx-auto space-y-6">

        <div class="bg-white p-6 rounded shadow">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">
                        <?= ucfirst(htmlspecialchars($role)) ?> - Class Report
                        <span class="text-sm font-normal text-gray-500"></span>
                    </h1>

                    <?php if ($isTeacher): ?>
                        <p class="text-sm text-gray-600 mt-1">Showing your assigned class report.</p>
                    <?php else: ?>
                        <p class="text-sm text-gray-600 mt-1">
                            Admin view.
                            <?php if ($classId): ?>
                                Loaded for class_id: <span class="font-medium"><?= htmlspecialchars((string)$classId) ?></span>
                            <?php else: ?>
                                Provide <span class="font-medium">?class_id=</span> in the URL to load a class report.
                                Example: <span class="font-mono">class_students.php?class_id=3</span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <a href="../auth/logout.php" class="text-indigo-600 hover:underline">Logout</a>
            </div>
        </div>

        <div class="bg-white p-6 rounded shadow space-y-4">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold">Students & Scores</h2>

                <div class="flex items-center gap-2">
                    <span id="statusPill" class="hidden text-xs px-2 py-1 rounded bg-gray-100 text-gray-700"></span>
                    <button
                        id="refreshBtn"
                        type="button"
                        class="text-sm px-3 py-2 rounded border hover:bg-gray-50">
                        Refresh
                    </button>
                </div>
            </div>

            <div id="errBox" class="hidden p-3 rounded text-sm text-red-700 bg-red-100"></div>
            <div id="okBox" class="hidden p-3 rounded text-sm text-green-700 bg-green-100"></div>
        </div>

        <div id="studentsContainer" class="bg-white p-6 rounded shadow space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Students</h2>
                    <p id="classInfo" class="text-sm text-gray-600">—</p>
                </div>
            </div>

            <div id="loading" class="hidden space-y-3">
                <div class="h-12 bg-gray-100 rounded animate-pulse"></div>
                <div class="h-12 bg-gray-100 rounded animate-pulse"></div>
                <div class="h-12 bg-gray-100 rounded animate-pulse"></div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-sm">
                            <th class="p-3 border">ID</th>
                            <th class="p-3 border">First Name</th>
                            <th class="p-3 border">Last Name</th>
                            <th class="p-3 border">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody" class="text-sm"></tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        (() => {
            const API_BASE = <?= json_encode($apiBase) ?>;
            const IS_TEACHER = <?= $isTeacher ? 'true' : 'false' ?>;
            const CLASS_ID = <?= json_encode($classId) ?>;

            let students = [];

            const refreshBtn = document.getElementById('refreshBtn');
            const statusPill = document.getElementById('statusPill');
            const errBox = document.getElementById('errBox');
            const okBox = document.getElementById('okBox');
            const classInfo = document.getElementById('classInfo');
            const tbody = document.getElementById('studentsTableBody');
            const loading = document.getElementById('loading');

            const show = (el) => el.classList.remove('hidden');
            const hide = (el) => el.classList.add('hidden');

            function setStatus(text) {
                if (!text) {
                    hide(statusPill);
                    statusPill.textContent = '';
                    return;
                }
                statusPill.textContent = text;
                show(statusPill);
            }

            function clearMessages() {
                hide(errBox);
                hide(okBox);
                errBox.textContent = '';
                okBox.textContent = '';
            }

            function showError(msg) {
                errBox.textContent = msg || 'Something went wrong.';
                show(errBox);
                hide(okBox);
            }

            function showOk(msg) {
                okBox.textContent = msg || 'Loaded.';
                show(okBox);
                hide(errBox);
                setTimeout(() => hide(okBox), 2000);
            }

            function setLoading(isLoading) {
                if (isLoading) {
                    setStatus('Loading…');
                    refreshBtn.disabled = true;
                    show(loading);
                } else {
                    setStatus('');
                    refreshBtn.disabled = false;
                    hide(loading);
                }
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
                    const msg = data?.message || data?.error || `Request failed (HTTP ${res.status}).`;
                    throw new Error(msg);
                }

                return data;
            }

            function resolveReportPath() {
                // Teacher: class comes from session
                if (IS_TEACHER) return '/teacher/class-report';

                // Admin: must provide class_id
                if (!CLASS_ID) return null;
                return `/class-report?class_id=${encodeURIComponent(CLASS_ID)}`;
            }

            async function loadStudentsAndScores() {
                clearMessages();
                tbody.innerHTML = '';
                classInfo.textContent = '—';

                const path = resolveReportPath();
                if (!path) {
                    showError('Missing class_id. Add ?class_id=3 to the URL.');
                    return;
                }

                try {
                    setLoading(true);

                    const data = await apiGet(path);

                    const list =
                        (Array.isArray(data?.students) ? data.students :
                            Array.isArray(data?.data?.students) ? data.data.students :
                            null);

                    if (!Array.isArray(list)) {
                        throw new Error('Unexpected response: missing students array.');
                    }

                    students = list;

                    classInfo.textContent = `${students.length} student${students.length === 1 ? '' : 's'}`;
                    renderStudents(students);

                    showOk('Loaded successfully.');
                } catch (e) {
                    showError(e.message || 'Error loading students.');
                } finally {
                    setLoading(false);
                }
            }

            function renderStudents(list) {
                tbody.innerHTML = '';

                list.forEach((s, i) => {
                    const scoresRows = (s.scores || []).map(sc => `
						<tr class="border-t">
							<td class="p-2">${escapeHtml(sc.subject_name ?? '')}</td>
							<td class="p-2 text-center">${sc.first_term ?? '-'}</td>
							<td class="p-2 text-center">${sc.second_term ?? '-'}</td>
							<td class="p-2 text-center">${sc.third_term ?? '-'}</td>
						</tr>
					`).join('');

                    const row = document.createElement('tr');
                    row.className = 'border-t';
                    row.innerHTML = `
						<td class="p-3 border">${escapeHtml(s.id)}</td>
						<td class="p-3 border">${escapeHtml(s.first_name)}</td>
						<td class="p-3 border">${escapeHtml(s.last_name)}</td>
						<td class="p-3 border">
							<button data-action="toggleScores" data-index="${i}"
								class="px-3 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700">
								View Scores
							</button>
						</td>
					`;
                    tbody.appendChild(row);

                    const scoresTr = document.createElement('tr');
                    scoresTr.id = `scores-${i}`;
                    scoresTr.className = 'hidden';
                    scoresTr.innerHTML = `
						<td colspan="4" class="p-3 border bg-gray-50">
							<div class="overflow-x-auto">
								<table class="min-w-full border">
									<thead class="bg-white">
										<tr class="text-sm">
											<th class="p-2 border">Subject</th>
											<th class="p-2 border text-center">Term 1</th>
											<th class="p-2 border text-center">Term 2</th>
											<th class="p-2 border text-center">Term 3</th>
										</tr>
									</thead>
									<tbody class="text-sm">
										${scoresRows || `
											<tr><td colspan="4" class="p-3 text-gray-600">No scores available.</td></tr>
										`}
									</tbody>
								</table>
							</div>
						</td>
					`;
                    tbody.appendChild(scoresTr);
                });
            }

            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-action]');
                if (!btn) return;

                const action = btn.dataset.action;
                const i = parseInt(btn.dataset.index, 10);
                if (Number.isNaN(i)) return;

                if (action === 'toggleScores') toggleScores(i);
            });

            function toggleScores(i) {
                const el = document.getElementById(`scores-${i}`);
                if (!el) return;
                el.classList.toggle('hidden');
            }

            function escapeHtml(str) {
                return String(str ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            refreshBtn.addEventListener('click', loadStudentsAndScores);

            loadStudentsAndScores();
        })();
    </script>

</body>

</html>