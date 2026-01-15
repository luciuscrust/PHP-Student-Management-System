const API_BASE = '../sms-api';
let students = [];
let isTeacher = false;

function initializeClassStudents(isTeacherRole) {
  isTeacher = isTeacherRole;
  
  if (!isTeacher) {
    document.getElementById('gradeSelect').onchange = e => loadClasses(e.target.value);
    loadGrades();
  }
  
  document.getElementById('loadBtn').onclick = loadStudents;
}
//function to display feedback messages to the user
function showMessage(msg, type = 'error') {
  const area = document.getElementById('messageArea');
  area.textContent = msg;
  area.className = `message ${type}`;
  setTimeout(() => area.textContent = '', 5000);
}
//function to fetch available grades from the system and populate the grade dropdown menue
async function loadGrades() {
  try {
    const res = await fetch(`${API_BASE}/grades`);
    const data = await res.json();
    const sel = document.getElementById('gradeSelect');
    data.grades.forEach(g => sel.add(new Option(`Grade ${g.grade_no}`, g.id)));
  } catch {
    showMessage('Failed to load grades');
  }
}
//function to load classes based on selected grade and updates the class dropdown menue
async function loadClasses(gradeId) {
  const sel = document.getElementById('classSelect');
  sel.innerHTML = '<option value="">Select Class</option>';
  sel.disabled = !gradeId;
  
  if (gradeId) {
    try {
      const res = await fetch(`${API_BASE}/get-classes?grade_id=${gradeId}`);
      const data = await res.json();
      data.classes.forEach(c => sel.add(new Option(c.class, c.id)));
    } catch {
      showMessage('Failed to load classes');
    }
  }
}
//Loads the list of students for the selected class, or the class assigned to a teacher
//Retrives their scores from the system and displays it in a table
//provides options to view each subject score and calculate term average
async function loadStudents() {
  let url = `${API_BASE}/teacher/class-report`;

  if (!isTeacher) {
    const classId = document.getElementById('classSelect').value;
    if (!classId) {
      showMessage('Please select a class');
      return;
    }
    url = `${API_BASE}/class-report?class_id=${classId}`;
  }

  try {
    const res = await fetch(url);
    const data = await res.json();
    if (!res.ok) {
      showMessage(data.error || 'Failed to load');
      return;
    }

    students = data.students;
    document.getElementById('classInfo').textContent = `${data.students.length} students`;
    document.getElementById('studentsContainer').style.display = 'block';
    
    const tbody = document.getElementById('studentsTableBody');
    tbody.innerHTML = '';
    
    data.students.forEach((s, i) => {
      tbody.innerHTML += `
        <tr class="student-row">
          <td>${s.id}</td>
          <td>${s.first_name}</td>
          <td>${s.last_name}</td>
          <td class="actions">
            <button onclick="toggleScores(${i})" class="btn btn-indigo">View Scores</button>
            <button onclick="showCalc(${i})" class="btn btn-green">Calculate Term Average</button>
          </td>
        </tr>
        <tr id="scores-${i}" style="display:none">
          <td colspan="4">
            <table class="scores-table">
              <tr><th>Subject</th><th>Term 1</th><th>Term 2</th><th>Term 3</th></tr>
              ${s.scores.map(sc => `
                <tr>
                  <td>${sc.subject_name}</td>
                  <td class="center">${sc.first_term ?? '-'}</td>
                  <td class="center">${sc.second_term ?? '-'}</td>
                  <td class="center">${sc.third_term ?? '-'}</td>
                </tr>
              `).join('')}
            </table>
          </td>
        </tr>
        <tr id="calc-${i}" style="display:none">
          <td colspan="4">
            <div class="calc-container">
              <select id="term-${i}" class="term-select">
                <option value="">Select Term</option>
                <option value="first_term">Term 1</option>
                <option value="second_term">Term 2</option>
                <option value="third_term">Term 3</option>
              </select>
              <button onclick="calc(${i})" class="btn btn-green btn-sm">Calculate</button>
              <button onclick="hideCalc(${i})" class="btn btn-gray btn-sm">Close</button>
            </div>
            <div id="result-${i}" class="term-result"></div>
          </td>
        </tr>
      `;
    });
    
    showMessage('Loaded successfully', 'success');
  } catch {
    showMessage('Error loading students');
  }
}
//shows or hides the students scores
function toggleScores(i) {
  const el = document.getElementById(`scores-${i}`);
  el.style.display = el.style.display === 'none' ? 'table-row' : 'none';
}

function showCalc(i) {
  document.getElementById(`calc-${i}`).style.display = 'table-row';
}

function hideCalc(i) {
  document.getElementById(`calc-${i}`).style.display = 'none';
  document.getElementById(`result-${i}`).textContent = '';
}
//Calculates and shows the students average score for a specific term
function calc(i) {
  const term = document.getElementById(`term-${i}`).value;
  const result = document.getElementById(`result-${i}`);

  if (!term) {
    result.textContent = 'Please select a term';
    return;
  }

  const scores = students[i].scores;
  let sum = 0, count = 0, details = [];

  scores.forEach(s => {
    if (s[term] != null) {
      sum += parseFloat(s[term]);
      count++;
      details.push(`${s.subject_name}: ${s[term]}`);
    }
  });

  if (count === 0) {
    result.textContent = 'No scores for this term';
    return;
  }

  result.innerHTML = `
    <div class="result-box">
      <div>${details.join(', ')}</div>
      <div class="average">Average: ${(sum / count).toFixed(2)}</div>
    </div>
  `;
}