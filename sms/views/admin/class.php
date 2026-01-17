<?php
session_start();

require_once __DIR__ . '../../../helpers/RoleGuard.php';
requireRole('admin');

$user = $_SESSION['user'] ?? ['email' => ''];

$gradeId = null;
if (isset($_GET['grade_id'])) $gradeId = (int)$_GET['grade_id'];
if ($gradeId === null && isset($_GET['id'])) $gradeId = (int)$_GET['id'];

if (!$gradeId || $gradeId <= 0) {
    $gradeId = null;
}

$apiBase = 'http://localhost/PHP-Student-Management-System/sms-api';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Classes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto space-y-6">

        <div class="bg-white p-6 rounded shadow">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">Classes</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Admin View.
                    </p>

                </div>

                <div class="flex items-center gap-3">
                    <a href="./dashboard.php" class="text-sm text-gray-700 hover:underline">← Back</a>
                    <a href="../auth/logout.php" class="text-indigo-600 hover:underline">Logout</a>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded shadow">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold">Class List</h2>

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

            <?php if (!$gradeId): ?>
                <div class="mb-4 p-3 rounded text-sm bg-yellow-50 text-yellow-900 border border-yellow-100">
                    Missing <span class="font-mono">grade_id</span>. Open this page like:
                    <span class="font-mono">class.php?grade_id=1</span>
                </div>
            <?php endif; ?>

            <div id="errBox" class="hidden mb-4 p-3 rounded text-sm text-red-700 bg-red-100"></div>


            <div id="loading" class="space-y-3">
                <div class="h-12 bg-gray-100 rounded animate-pulse"></div>
                <div class="h-12 bg-gray-100 rounded animate-pulse"></div>
                <div class="h-12 bg-gray-100 rounded animate-pulse"></div>
            </div>

            <ul id="classesList" class="hidden divide-y"></ul>

            <p id="emptyState" class="hidden text-sm text-gray-600">
                No classes were returned from the API.
            </p>
        </div>
    </div>

    <script>
        (() => {
            const API_BASE = <?= json_encode($apiBase) ?>;
            const GRADE_ID = <?= $gradeId ? json_encode($gradeId) : 'null' ?>;

            const listEl = document.getElementById('classesList');
            const errBox = document.getElementById('errBox');
            const loading = document.getElementById('loading');
            const emptyState = document.getElementById('emptyState');
            const refreshBtn = document.getElementById('refreshBtn');
            const statusPill = document.getElementById('statusPill');

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

            function renderClasses(classes) {
                listEl.innerHTML = '';

                if (!Array.isArray(classes) || classes.length === 0) {
                    hide(listEl);
                    show(emptyState);
                    return;
                }

                hide(emptyState);
                show(listEl);

                for (const c of classes) {
                    const classId = c.id;
                    const className = c.name ?? c.class ?? `Class ${classId}`;

                    const li = document.createElement('li');
                    li.innerHTML = `
						<button
							type="button"
							class="w-full text-left p-4 hover:bg-gray-50 flex items-center justify-between gap-4"
							data-class-id="${escapeHtml(String(classId))}"
							data-class-name="${escapeHtml(String(className))}">
							<div>
								<div class="font-medium text-gray-900">${escapeHtml(String(className))}</div>
							</div>
						</button>
					`;

                    li.querySelector('button').addEventListener('click', () => {

                        try {
                            localStorage.setItem('selected_class_id', String(classId));
                            localStorage.setItem('selected_class_name', String(className));
                            localStorage.setItem('selected_grade_id', String(GRADE_ID ?? ''));
                        } catch (_) {}

                        window.location.href = `../shared/student.php?class_id=${encodeURIComponent(classId)}`;

                    });

                    listEl.appendChild(li);
                }
            }

            async function loadClasses() {
                clearError();
                setLoading(true);

                try {
                    if (!GRADE_ID) {
                        throw new Error('Missing grade_id. Open this page like class.php?grade_id=1');
                    }

                    const res = await fetch(`${API_BASE}/get-classes?grade_id=${encodeURIComponent(GRADE_ID)}`, {
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
                        throw new Error(data?.message || data?.error || `Failed to load classes (HTTP ${res.status}).`);
                    }

                    const classes =
                        Array.isArray(data?.data) ? data.data :
                        Array.isArray(data?.classes) ? data.classes :
                        null;

                    if (!Array.isArray(classes)) {
                        throw new Error('Unexpected API response: missing classes array.');
                    }

                    const normalized = classes.map(c => ({
                        id: c.id,
                        name: c.name ?? c.class ?? c.class_name ?? null
                    }));

                    renderClasses(normalized);

                } catch (err) {
                    showError(err?.message || 'Could not load classes.');
                    hide(listEl);
                    hide(emptyState);
                } finally {
                    setLoading(false);
                }
            }

            refreshBtn.addEventListener('click', loadClasses);

            loadClasses();
        })();
    </script>

</body>

</html>