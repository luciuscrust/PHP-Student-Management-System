<?php
session_start();

$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: ../auth/index.php');
    exit;
}

$role = $user['role'] ?? '';
$isTeacher = ($role === 'teacher');
$isAdmin   = ($role === 'admin');

if (!$isTeacher && !$isAdmin) {
    header('Location: ../auth/index.php');
    exit;
}

$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($studentId <= 0) {
    header('Location: ./dashboard.php');
    exit;
}

$apiBase = 'http://localhost/PHP-Student-Management-System/sms-api';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Student Report Card</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto space-y-6">

        <div class="bg-white p-6 rounded shadow">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">
                        <span id="studentNameBadge" class="text-xl font-semibold"></span>
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Student ID: <?= htmlspecialchars((string)$studentId) ?>
                    </p>
                </div>

            </div>
        </div>

        <div class="bg-white p-6 rounded shadow space-y-4">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold">Report Card</h2>
                <span id="statusPill" class="hidden text-xs px-2 py-1 rounded bg-gray-100 text-gray-700"></span>
            </div>

            <div id="errBox" class="hidden p-3 rounded text-sm text-red-700 bg-red-100"></div>

            <div id="summary" class="hidden grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded border bg-gray-50">
                    <div class="text-xs text-gray-500">Grade</div>
                    <div id="gradeNo" class="font-medium text-gray-900">—</div>
                </div>

                <div class="p-4 rounded border bg-gray-50">
                    <div class="text-xs text-gray-500">Class</div>
                    <div id="className" class="font-medium text-gray-900">—</div>
                </div>

                <div class="p-4 rounded border bg-gray-50">
                    <div class="text-xs text-gray-500">Overall Average</div>
                    <div id="overallAvg" class="font-medium text-gray-900">—</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border">
                    <thead class="bg-gray-50">
                        <tr class="text-sm text-left">
                            <th class="p-3 border">Subject</th>
                            <th class="p-3 border text-center">Term 1</th>
                            <th class="p-3 border text-center">Term 2</th>
                            <th class="p-3 border text-center">Term 3</th>
                            <th class="p-3 border text-center">Average</th>
                        </tr>
                    </thead>
                    <tbody id="tbody" class="text-sm"></tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        (() => {
            const API_BASE = <?= json_encode($apiBase) ?>;
            const IS_TEACHER = <?= $isTeacher ? 'true' : 'false' ?>;
            const STUDENT_ID = <?= json_encode($studentId) ?>;

            const statusPill = document.getElementById('statusPill');
            const errBox = document.getElementById('errBox');
            const tbody = document.getElementById('tbody');

            const summary = document.getElementById('summary');
            const className = document.getElementById('className');
            const gradeNo = document.getElementById('gradeNo');
            const overallAvg = document.getElementById('overallAvg');

            const studentNameBadge = document.getElementById('studentNameBadge');

            try {
                const nm = localStorage.getItem('selected_student_name');
                if (nm) {
                    studentNameBadge.textContent = nm;
                    studentNameBadge.classList.remove('hidden');
                }
            } catch (_) {}

            const show = el => el.classList.remove('hidden');
            const hide = el => el.classList.add('hidden');

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
                    throw new Error(data?.error || data?.message || `Request failed (HTTP ${res.status}).`);
                }

                return data;
            }

            function resolvePath() {
                const base = IS_TEACHER ? '/teacher/student-report' : '/student-report';
                return `${base}?student_id=${encodeURIComponent(STUDENT_ID)}`;
            }

            function render(report) {

                const student = report?.student;

                if (student) {
                    className.textContent = student.class_name ?? '—';
                    gradeNo.textContent = (student.grade_no != null) ? `Grade ${student.grade_no}` : '—';
                }

                overallAvg.textContent = (report?.overall_average != null) ?
                    Number(report.overall_average).toFixed(2) :
                    '—';

                show(summary);

                const subjects = Array.isArray(report?.subjects) ? report.subjects : [];
                tbody.innerHTML = '';

                if (subjects.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="p-4 text-gray-600">No subjects found.</td></tr>`;
                    return;
                }

                for (const s of subjects) {
                    tbody.innerHTML += `
            <tr class="border-t">
              <td class="p-3 border">${escapeHtml(s.subject_name)}</td>
              <td class="p-3 border text-center">${s.first_term ?? '-'}</td>
              <td class="p-3 border text-center">${s.second_term ?? '-'}</td>
              <td class="p-3 border text-center">${s.third_term ?? '-'}</td>
              <td class="p-3 border text-center">${(s.average != null) ? Number(s.average).toFixed(2) : '-'}</td>
            </tr>
          `;
                }
            }

            async function loadReport() {
                setStatus('Loading…');
                hide(errBox);

                try {
                    const report = await apiGet(resolvePath());
                    render(report);
                } catch (e) {
                    showError(e.message || 'Failed to load report.');
                } finally {
                    setStatus('');
                }
            }

            loadReport();
        })();
    </script>
</body>

</html>