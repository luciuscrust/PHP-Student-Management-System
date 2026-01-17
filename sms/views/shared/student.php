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
                        <span id="classNameBadge" class="ml-2 hidden text-s font-medium px-2 py-1 rounded bg-gray-100 text-gray-700"></span>
                        - Class Report
                    </h1>
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

            <?php if (!$isTeacher): ?>
                <div class="border rounded p-4 bg-gray-50">
                    <h3 class="font-semibold mb-3">Student Management</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Student ID (for update/delete)</label>
                            <input id="stu_id" type="number" class="w-full border rounded px-3 py-2" placeholder="e.g. 12" min="1" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">First Name</label>
                            <input id="stu_first" type="text" class="w-full border rounded px-3 py-2" placeholder="First name" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Last Name</label>
                            <input id="stu_last" type="text" class="w-full border rounded px-3 py-2" placeholder="Last name" />
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button id="addStudentBtn" type="button"
                            class="px-3 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700">
                            Add Student
                        </button>

                        <button id="updateStudentBtn" type="button"
                            class="px-3 py-2 text-sm rounded bg-amber-600 text-white hover:bg-amber-700">
                            Update Student
                        </button>

                        <button id="deleteStudentBtn" type="button"
                            class="px-3 py-2 text-sm rounded bg-red-600 text-white hover:bg-red-700">
                            Delete Student
                        </button>
                    </div>

                    <p class="text-xs text-gray-600 mt-2">
                        Add Student - First Name | Last Name <br>
                        Update Student - Student_ID | Updated First Name | Updated Last Name <br>
                        Delete Student - Student_ID <br>
                    </p>
                </div>
            <?php endif; ?>

            <div class="border rounded p-4 bg-gray-50 mt-4">
                <h3 class="font-semibold mb-3">Score Management</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Student ID</label>
                        <input id="score_student_id" type="number" class="w-full border rounded px-3 py-2" placeholder="e.g. 12" min="1" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Subject</label>
                        <select id="score_subject"
                            class="w-full border rounded px-3 py-2">
                            <option value="">Select subject</option>
                            <option value="math">Math</option>
                            <option value="grammar">Grammar</option>
                            <option value="science">Science</option>
                            <option value="social">Social Studies</option>
                        </select>
                    </div>


                    <div>
                        <label class="block text-sm font-medium mb-1">School Year</label>
                        <input id="score_school_year" type="number" class="w-full border rounded px-3 py-2" placeholder="e.g. 2025" min="2000" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Term 1</label>
                        <input id="score_first_term" type="number" step="0.01" class="w-full border rounded px-3 py-2" placeholder="e.g. 78" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Term 2</label>
                        <input id="score_second_term" type="number" step="0.01" class="w-full border rounded px-3 py-2" placeholder="e.g. 82" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Term 3</label>
                        <input id="score_third_term" type="number" step="0.01" class="w-full border rounded px-3 py-2" placeholder="e.g. 80" />
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button id="saveScoresBtn" type="button"
                        class="px-3 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-sky-700">
                        Save / Update Scores
                    </button>
                </div>

                <p class="text-xs text-gray-600 mt-2">
                    This will create or update the student’s scores for the selected subject & school year.
                </p>
            </div>


            <div id="loading" class="hidden space-y-3">
                <div class="h-12 bg-gray-100 rounded animate-pulse"></div>
                <div class="h-12 bg-gray-100 rounded animate-pulse"></div>
                <div class="h-12 bg-gray-100 rounded animate-pulse"></div>
            </div>

            <div class="overflow-x-auto">
                <table class="text-center min-w-full border">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-sm">
                            <th class="text-center py-3 border">ID</th>
                            <th class="text-center py-3 border">First Name</th>
                            <th class="text-center py-3 border">Last Name</th>
                            <th class="text-center py-3 border">Actions</th>
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

            const classNameBadge = document.getElementById('classNameBadge');

            try {
                const className = localStorage.getItem('selected_class_name');
                if (className && classNameBadge) {
                    classNameBadge.textContent = className;
                    classNameBadge.classList.remove('hidden');
                }
            } catch (_) {}

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

            function calculateSubjectId(subjectKey) {
                const gradeIdRaw = localStorage.getItem('selected_grade_id');
                const gradeId = Number(gradeIdRaw);

                if (!gradeId || gradeId <= 0) {
                    throw new Error('Invalid or missing selected_grade_id in localStorage.');
                }

                const base = 4 * (gradeId - 1);

                switch (subjectKey) {
                    case 'math':
                        return 1 + base;
                    case 'grammar':
                        return 2 + base;
                    case 'science':
                        return 3 + base;
                    case 'social':
                        return 4 + base;
                    default:
                        throw new Error('Invalid subject selected.');
                }
            }

            async function apiRequest(path, {
                method = 'GET',
                body = null
            } = {}) {
                const opts = {
                    method,
                    headers: {
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                };

                if (body !== null) {
                    const fd = new FormData();
                    Object.entries(body).forEach(([k, v]) => fd.append(k, v ?? ''));
                    opts.body = fd;
                }

                const res = await fetch(`${API_BASE}${path}`, opts);

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
                if (IS_TEACHER) return '/teacher/class-report';

                if (!CLASS_ID) return null;
                return `/class-report?class_id=${encodeURIComponent(CLASS_ID)}`;
            }

            async function loadStudentsAndScores() {
                clearMessages();
                tbody.innerHTML = '';
                classInfo.textContent = '—';

                const path = resolveReportPath();
                if (!path) {
                    showError('Error: Missing class_id. Add "?class_id=CLASS_ID" to the URL.');
                    return;
                }

                try {
                    setLoading(true);

                    const data = await apiRequest(path);

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
						<td class="py-3 border">${escapeHtml(s.id)}</td>
						<td class="py-3 border">${escapeHtml(s.first_name)}</td>
						<td class="py-3 border">${escapeHtml(s.last_name)}</td>			
                        <td class="py-3 border">
                            <div class="flex justify-center gap-2 flex-wrap">
                                <button data-action="toggleScores" data-index="${i}"
                                    class="px-3 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700">
                                    View Scores
                                </button>

                                <button data-action="viewReport" data-student-id="${escapeHtml(s.id)}"
                                    data-student-name="${escapeHtml(`${s.first_name} ${s.last_name}`)}"
                                    class="px-3 py-2 text-sm rounded bg-emerald-600 text-white hover:bg-emerald-700">
                                    View Report Card
                                </button>
                            </div>
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

                if (action === 'toggleScores') {
                    const i = parseInt(btn.dataset.index, 10);
                    if (!Number.isNaN(i)) toggleScores(i);
                    return;
                }

                if (action === 'viewReport') {
                    const studentId = btn.dataset.studentId;
                    const studentName = btn.dataset.studentName;

                    try {
                        localStorage.setItem('selected_student_name', studentName || '');
                    } catch (_) {}

                    window.location.href = `./student_report.php?student_id=${encodeURIComponent(studentId)}`;
                }
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

            // -------------------------
            // Student routes usage (Admin)
            // -------------------------

            if (!IS_TEACHER) {
                // -------------------------
                // Student Add, Update and Delete Operation
                // -------------------------

                const stuIdEl = document.getElementById('stu_id');
                const stuFirstEl = document.getElementById('stu_first');
                const stuLastEl = document.getElementById('stu_last');

                const addBtn = document.getElementById('addStudentBtn');
                const updBtn = document.getElementById('updateStudentBtn');
                const delBtn = document.getElementById('deleteStudentBtn');

                function requireClassIdOrFail() {
                    if (!CLASS_ID) {
                        showError('Missing class_id in URL. Add "?class_id=CLASS_ID" to use student operations.');
                        return false;
                    }
                    return true;
                }

                addBtn?.addEventListener('click', async () => {
                    clearMessages();
                    if (!requireClassIdOrFail()) return;

                    const first_name = (stuFirstEl.value || '').trim();
                    const last_name = (stuLastEl.value || '').trim();

                    if (!first_name || !last_name) {
                        showError('First name and last name are required.');
                        return;
                    }

                    try {
                        setStatus('Adding student…');

                        await apiRequest('/add-student', {
                            method: 'POST',
                            body: {
                                class_id: String(CLASS_ID),
                                first_name,
                                last_name
                            }
                        });

                        showOk('Student added.');
                        stuFirstEl.value = '';
                        stuLastEl.value = '';

                        setTimeout(() => {
                            loadStudentsAndScores();
                        }, 1100);

                    } catch (e) {
                        showError(e.message || 'Failed to add student.');
                    } finally {
                        setStatus('');
                    }
                });

                updBtn?.addEventListener('click', async () => {
                    clearMessages();
                    if (!requireClassIdOrFail()) return;

                    const id = Number(stuIdEl.value);
                    const first_name = (stuFirstEl.value || '').trim();
                    const last_name = (stuLastEl.value || '').trim();

                    if (!id || id <= 0) return showError('Valid Student ID is required for update.');
                    if (!first_name || !last_name) return showError('First name and last name are required for update.');

                    try {
                        console.log(`Class ID: ${CLASS_ID}\nStudent ID: ${id}\nFirst Name: ${first_name}\nLast Name: ${last_name}`)

                        setStatus('Updating student…');

                        await apiRequest('/update-student', {
                            method: 'POST',
                            body: {
                                id: String(id),
                                class_id: String(CLASS_ID),
                                first_name,
                                last_name
                            }
                        });

                        showOk('Student updated.');


                        setTimeout(() => {
                            loadStudentsAndScores();
                        }, 1100);


                    } catch (error) {
                        showError(error.message || 'Failed to update student.');
                    } finally {
                        setStatus('');
                    }
                });

                delBtn?.addEventListener('click', async () => {
                    clearMessages();

                    const id = Number(stuIdEl.value);
                    if (!id || id <= 0) return showError('Valid Student ID is required for delete.');

                    if (!confirm(`Delete student #${id}?`)) return;

                    try {
                        setStatus('Deleting student…');

                        await apiRequest('/delete-student', {
                            method: 'POST',
                            body: {
                                id: String(id)
                            }
                        });

                        showOk('Student deleted.');
                        stuIdEl.value = '';

                        setTimeout(() => {
                            loadStudentsAndScores();
                        }, 1100);

                    } catch (e) {
                        showError(e.message || 'Failed to delete student.');
                    } finally {
                        setStatus('');
                    }
                });
            }


            // -------------------------
            // Add and Update Student Subject Scores Across Terms
            // -------------------------

            const scoreStudentIdEl = document.getElementById('score_student_id');
            const scoreSubjectEl = document.getElementById('score_subject');
            const scoreSchoolYearEl = document.getElementById('score_school_year');
            const scoreFirstEl = document.getElementById('score_first_term');
            const scoreSecondEl = document.getElementById('score_second_term');
            const scoreThirdEl = document.getElementById('score_third_term');
            const saveScoresBtn = document.getElementById('saveScoresBtn');

            saveScoresBtn?.addEventListener('click', async () => {
                clearMessages();

                const student_id = Number(scoreStudentIdEl.value);

                const subjectKey = scoreSubjectEl.value;

                if (!subjectKey) {
                    return showError('Please select a subject.');
                }

                let subject_id;
                try {
                    subject_id = calculateSubjectId(subjectKey);
                } catch (err) {
                    return showError(err.message);
                }

                const school_year = Number(scoreSchoolYearEl.value);

                const first_term =
                    scoreFirstEl.value === '' ? null : Number(scoreFirstEl.value);

                const second_term =
                    scoreSecondEl.value === '' ? null : Number(scoreSecondEl.value);

                const third_term =
                    scoreThirdEl.value === '' ? null : Number(scoreThirdEl.value);

                if (!student_id || student_id <= 0) {
                    return showError('Valid Student ID is required.');
                }

                if (!subject_id || subject_id <= 0) {
                    return showError('Valid Subject ID is required.');
                }

                if (!school_year || school_year <= 0) {
                    return showError('Valid School Year is required.');
                }

                if (first_term === null && second_term === null && third_term === null) {
                    return showError('Enter at least one term score.');
                }

                if (
                    (first_term !== null && Number.isNaN(first_term)) ||
                    (second_term !== null && Number.isNaN(second_term)) ||
                    (third_term !== null && Number.isNaN(third_term))
                ) {
                    return showError('Term scores must be valid numbers.');
                }


                if (Number.isNaN(first_term) || Number.isNaN(second_term) || Number.isNaN(third_term)) {
                    return showError('Term 1, Term 2, and Term 3 must be numbers.');
                }

                try {
                    setStatus('Saving scores…');

                    await apiRequest('/students/scores', {
                        method: 'POST',
                        body: {
                            student_id: String(student_id),
                            subject_id: String(subject_id),
                            school_year: String(school_year),

                            first_term: first_term === null ? '' : String(first_term),
                            second_term: second_term === null ? '' : String(second_term),
                            third_term: third_term === null ? '' : String(third_term),
                        }
                    });

                    showOk('Scores saved.');

                    setTimeout(() => loadStudentsAndScores(), 1100);

                } catch (e) {
                    showError(e.message || 'Failed to save scores.');
                } finally {
                    setStatus('');
                }
            });



            refreshBtn.addEventListener('click', loadStudentsAndScores);

            loadStudentsAndScores();
        })();
    </script>

</body>

</html>