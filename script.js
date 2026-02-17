
// Global State
let currentUser = null;
let currentEnrollStudentId = null;
let currentClassId = null;

// DOM Elements
const loginSection = document.getElementById('loginSection');
const registrarDashboard = document.getElementById('registrarDashboard');
const teacherDashboard = document.getElementById('teacherDashboard');
const userSection = document.getElementById('userSection');
const userNameDisplay = document.getElementById('userNameDisplay');
const loginForm = document.getElementById('loginForm');
const loginError = document.getElementById('loginError');
const logoutBtn = document.getElementById('logoutBtn');

// Modals
const teacherFormModal = document.getElementById('teacherFormModal');
const subjectFormModal = document.getElementById('subjectFormModal');
const classFormModal = document.getElementById('classFormModal');
const enrollmentModal = document.getElementById('enrollmentModal');
const studentFormSection = document.getElementById('formSection');

// Forms
const teacherForm = document.getElementById('teacherForm');
const subjectForm = document.getElementById('subjectForm');
const classForm = document.getElementById('classForm');
const studentForm = document.getElementById('studentForm');

const enrollClassSelect = document.getElementById('enrollClassSelect');
const enrollBtn = document.getElementById('enrollBtn');

// Teacher View Elements
const teacherSchedule = document.getElementById('teacherSchedule');
const attendanceView = document.getElementById('attendanceView');
const attendanceClassName = document.getElementById('attendanceClassName');
const attendanceList = document.getElementById('attendanceList');
const attendanceDate = document.getElementById('attendanceDate');
const backToScheduleBtn = document.getElementById('backToScheduleBtn');
const saveAttendanceBtn = document.getElementById('saveAttendanceBtn');

// Report Elements
const reportClassSelect = document.getElementById('reportClassSelect');
const generateClassReportBtn = document.getElementById('generateClassReportBtn');
const classReportResult = document.getElementById('classReportResult');
const classReportTableBody = document.getElementById('classReportTableBody');

const reportStudentSearch = document.getElementById('reportStudentSearch');
const searchStudentReportBtn = document.getElementById('searchStudentReportBtn');
const studentReportResult = document.getElementById('studentReportResult');
const reportStudentName = document.getElementById('reportStudentName');
const studentReportTableBody = document.getElementById('studentReportTableBody');


// Initialize
document.addEventListener('DOMContentLoaded', () => {
    checkSession();
    setupEventListeners();
});

function setupEventListeners() {
    loginForm.addEventListener('submit', handleLogin);
    logoutBtn.addEventListener('click', handleLogout);

    // Modal Close Buttons
    document.querySelectorAll('.close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById(btn.dataset.target).style.display = 'none';
        });
    });

    document.getElementById('closeFormBtn').addEventListener('click', () => {
        studentFormSection.style.display = 'none';
    });

    // Registrar Tabs
    const registrarTabs = document.getElementById('registrarTabs');
    if (registrarTabs) {
        registrarTabs.addEventListener('click', (e) => {
            if (e.target.classList.contains('nav-link')) {
                e.preventDefault();
                document.querySelectorAll('#registrarTabs .nav-link').forEach(l => l.classList.remove('active'));
                e.target.classList.add('active');

                document.querySelectorAll('.dashboard-tab').forEach(t => t.style.display = 'none');
                document.getElementById(`tab-${e.target.dataset.tab}`).style.display = 'block';
            }
        });
    }

    // Registrar Buttons
    const addNewTeacherBtn = document.getElementById('addNewTeacherBtn');
    if(addNewTeacherBtn) {
        addNewTeacherBtn.addEventListener('click', () => {
            teacherFormModal.style.display = 'flex';
            teacherForm.reset();
        });
    }

    const addNewSubjectBtn = document.getElementById('addNewSubjectBtn');
    if(addNewSubjectBtn) {
        addNewSubjectBtn.addEventListener('click', () => {
            subjectFormModal.style.display = 'flex';
            subjectForm.reset();
        });
    }

    const addNewClassBtn = document.getElementById('addNewClassBtn');
    if(addNewClassBtn) {
        addNewClassBtn.addEventListener('click', () => {
            classFormModal.style.display = 'flex';
            classForm.reset();
            loadClassFormOptions();
        });
    }

    const addNewStudentBtn = document.getElementById('addNewStudentBtn');
    if(addNewStudentBtn) {
        addNewStudentBtn.addEventListener('click', () => {
            studentFormSection.style.display = 'flex';
            studentForm.reset();
            document.getElementById('studentIdDb').value = '';
        });
    }

    enrollBtn.addEventListener('click', submitEnrollment);

    // Form Submits
    teacherForm.addEventListener('submit', submitTeacher);
    subjectForm.addEventListener('submit', submitSubject);
    classForm.addEventListener('submit', submitClass);
    studentForm.addEventListener('submit', submitStudent);

    // Teacher View Listeners
    if(backToScheduleBtn) {
        backToScheduleBtn.addEventListener('click', () => {
            attendanceView.style.display = 'none';
            teacherDashboard.style.display = 'block';
        });
    }

    if(attendanceDate) {
        attendanceDate.valueAsDate = new Date();
        attendanceDate.addEventListener('change', () => {
            if (currentClassId) loadClassRoster(currentClassId);
        });
    }

    if(saveAttendanceBtn) {
        saveAttendanceBtn.addEventListener('click', saveAttendance);
    }

    // Report Listeners
    if(generateClassReportBtn) {
        generateClassReportBtn.addEventListener('click', generateClassReport);
    }

    if(searchStudentReportBtn) {
        searchStudentReportBtn.addEventListener('click', searchStudentReport);
    }
}

// Auth Functions
function checkSession() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=check_session'
    })
    .then(res => res.json())
    .then(data => {
        if (data.logged_in) {
            currentUser = data;
            showDashboard();
        } else {
            showLogin();
        }
    });
}

function handleLogin(e) {
    e.preventDefault();
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value.trim();

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=login&username=${username}&password=${password}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            currentUser = data;
            showDashboard();
            loginForm.reset();
            loginError.textContent = '';
        } else {
            loginError.textContent = data.message;
        }
    });
}

function handleLogout() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=logout'
    })
    .then(res => res.json())
    .then(() => {
        currentUser = null;
        showLogin();
        // Hide specific views
        attendanceView.style.display = 'none';
    });
}

function showLogin() {
    loginSection.style.display = 'flex';
    registrarDashboard.style.display = 'none';
    teacherDashboard.style.display = 'none';
    userSection.style.display = 'none';
    attendanceView.style.display = 'none';
}

function showDashboard() {
    loginSection.style.display = 'none';
    userSection.style.display = 'flex';
    userNameDisplay.textContent = `${currentUser.name} (${currentUser.role})`;

    if (currentUser.role === 'registrar') {
        registrarDashboard.style.display = 'block';
        loadRegistrarData();
    } else {
        teacherDashboard.style.display = 'block';
        loadTeacherData();
    }
}

// Registrar Data Loading
function loadRegistrarData() {
    loadTeachers();
    loadSubjects();
    loadClasses();
    loadStudents();
    loadReportOptions();
}

function loadTeachers() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list_teachers'
    })
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('teacherTableBody');
        if(tbody) {
            tbody.innerHTML = '';
            if (data.success && data.teachers) {
                data.teachers.forEach(t => {
                    tbody.innerHTML += `
                        <tr>
                            <td>${t.username}</td>
                            <td>${t.name}</td>
                            <td>${t.department}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary">Edit</button>
                            </td>
                        </tr>
                    `;
                });
            }
        }
    });
}

function loadSubjects() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list_subjects'
    })
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('subjectTableBody');
        if(tbody) {
            tbody.innerHTML = '';
            if (data.success && data.subjects) {
                data.subjects.forEach(s => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="fw-bold text-school-primary">${s.code}</td>
                            <td>${s.description}</td>
                        </tr>
                    `;
                });
            }
        }
    });
}

function loadClasses() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list_classes'
    })
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('classTableBody');
        if(tbody) {
            tbody.innerHTML = '';
            if (data.success && data.classes) {
                data.classes.forEach(c => {
                    tbody.innerHTML += `
                        <tr>
                            <td><small class="fw-bold">${c.code}</small></td>
                            <td>${c.section}</td>
                            <td>${c.room}</td>
                            <td>${c.teacher_name}</td>
                        </tr>
                    `;
                });
            }
        }
    });
}

function loadStudents() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list_students'
    })
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('studentTableBody');
        if(tbody) {
            tbody.innerHTML = '';
            if (data.success && data.students) {
                data.students.forEach(s => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="fw-bold text-muted">#${s.school_id}</td>
                            <td><strong>${s.last_name}</strong>, ${s.first_name}</td>
                            <td><span class="badge bg-light text-dark border">${s.course}</span></td>
                            <td>${s.year_level}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-success me-1" onclick="openEnrollmentModal(${s.id}, '${s.first_name} ${s.last_name}')">
                                    <i class="bi bi-journal-plus"></i> Enroll
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="alert('Edit feature coming soon')">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
        }
    });
}

// Form Submissions
function submitTeacher(e) {
    e.preventDefault();
    const name = document.getElementById('teacherName').value;
    const department = document.getElementById('teacherDepartment').value;
    const username = document.getElementById('teacherUsername').value;
    const password = document.getElementById('teacherPassword').value;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=create_teacher&name=${name}&department=${department}&username=${username}&password=${password}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            teacherFormModal.style.display = 'none';
            loadTeachers();
        } else {
            alert(data.message);
        }
    });
}

function submitSubject(e) {
    e.preventDefault();
    const code = document.getElementById('subjectCode').value;
    const desc = document.getElementById('subjectDesc').value;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=create_subject&code=${code}&description=${desc}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            subjectFormModal.style.display = 'none';
            loadSubjects();
        } else {
            alert(data.message);
        }
    });
}

function submitClass(e) {
    e.preventDefault();
    const subjectId = document.getElementById('classSubjectSelect').value;
    const teacherId = document.getElementById('classTeacherSelect').value;
    const section = document.getElementById('classSection').value;
    const room = document.getElementById('classRoom').value;
    const semester = document.getElementById('classSemester').value;
    const ay = document.getElementById('classAY').value;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=create_class&subject_id=${subjectId}&teacher_id=${teacherId}&section=${section}&room=${room}&semester=${semester}&academic_year=${ay}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            classFormModal.style.display = 'none';
            loadClasses();
        } else {
            alert(data.message);
        }
    });
}

function submitStudent(e) {
    e.preventDefault();
    const schoolId = document.getElementById('schoolId').value;
    const lastName = document.getElementById('lastName').value;
    const firstName = document.getElementById('firstName').value;
    const middleName = document.getElementById('middleName').value;
    const birthdate = document.getElementById('birthdate').value;
    const course = document.getElementById('course').value;
    const yearLevel = document.getElementById('yearLevel').value;
    const address = document.getElementById('address').value;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=create_student&school_id=${schoolId}&last_name=${lastName}&first_name=${firstName}&middle_name=${middleName}&birthdate=${birthdate}&course=${course}&year_level=${yearLevel}&address=${address}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            studentFormSection.style.display = 'none';
            loadStudents();
        } else {
            alert(data.message);
        }
    });
}

// Enrollment Logic
window.openEnrollmentModal = function(studentId, studentName) {
    currentEnrollStudentId = studentId;
    document.getElementById('enrollStudentName').textContent = `Enrolling: ${studentName}`;
    enrollmentModal.style.display = 'flex';
    
    loadStudentEnrollments(studentId);
    loadAvailableClasses();
}

function loadStudentEnrollments(studentId) {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_student_enrollments&student_id=${studentId}`
    })
    .then(res => res.json())
    .then(data => {
        const list = document.getElementById('enrollmentList');
        list.innerHTML = '';
        if (data.success && data.enrollments) {
            if (data.enrollments.length === 0) {
                list.innerHTML = '<li class="list-group-item text-muted">No subjects enrolled yet.</li>';
            } else {
                data.enrollments.forEach(e => {
                    list.innerHTML += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold">${e.code}</span> - ${e.description}
                                <span class="badge bg-light text-dark ms-2">${e.section}</span>
                            </div>
                        </li>
                    `;
                });
            }
        }
    });
}

function loadAvailableClasses() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list_classes'
    })
    .then(res => res.json())
    .then(data => {
        enrollClassSelect.innerHTML = '<option value="">Select Class to Add...</option>';
        if (data.success && data.classes) {
            data.classes.forEach(c => {
                enrollClassSelect.innerHTML += `
                    <option value="${c.id}">${c.code} - ${c.section} (${c.teacher_name})</option>
                `;
            });
        }
    });
}

function submitEnrollment() {
    const classId = enrollClassSelect.value;
    if (!classId) return;
    
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=enroll_student&student_id=${currentEnrollStudentId}&class_id=${classId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadStudentEnrollments(currentEnrollStudentId);
        } else {
            alert(data.message);
        }
    });
}

function loadClassFormOptions() {
    // Load Subjects
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list_subjects'
    })
    .then(res => res.json())
    .then(data => {
        const select = document.getElementById('classSubjectSelect');
        select.innerHTML = '<option value="">Select Subject</option>';
        if (data.success) {
            data.subjects.forEach(s => {
                select.innerHTML += `<option value="${s.id}">${s.code} - ${s.description}</option>`;
            });
        }
    });

    // Load Teachers
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list_teachers'
    })
    .then(res => res.json())
    .then(data => {
        const select = document.getElementById('classTeacherSelect');
        select.innerHTML = '<option value="">Select Teacher</option>';
        if (data.success) {
            data.teachers.forEach(t => {
                select.innerHTML += `<option value="${t.id}">${t.name} (${t.department})</option>`;
            });
        }
    });
}

// Teacher Functions

function loadTeacherData() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_teacher_load'
    })
    .then(res => res.json())
    .then(data => {
        teacherSchedule.innerHTML = '';
        if (data.success && data.classes) {
            if (data.classes.length === 0) {
                 teacherSchedule.innerHTML = '<div class="col-12 text-center text-muted">No classes assigned yet.</div>';
                 return;
            }
            data.classes.forEach(c => {
                const card = document.createElement('div');
                card.className = 'col-md-4 mb-4';
                card.innerHTML = `
                    <div class="card shadow-sm h-100 border-0 card-hover" onclick="openAttendance(${c.id}, '${c.code}', '${c.section}')" style="cursor: pointer;">
                        <div class="card-body">
                            <h5 class="fw-bold text-school-primary mb-1">${c.code}</h5>
                            <p class="text-muted small mb-2">${c.description}</p>
                            <span class="badge bg-light text-dark border">${c.section}</span>
                            <span class="badge bg-light text-dark border"><i class="bi bi-geo-alt-fill me-1"></i>${c.room}</span>
                        </div>
                    </div>
                `;
                teacherSchedule.appendChild(card);
            });
        }
    });
}

window.openAttendance = function(classId, code, section) {
    currentClassId = classId;
    teacherDashboard.style.display = 'none';
    attendanceView.style.display = 'block';
    attendanceClassName.textContent = `${code} - ${section}`;
    loadClassRoster(classId);
}

function loadClassRoster(classId) {
    const date = attendanceDate.value;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_class_roster&class_id=${classId}&date=${date}`
    })
    .then(res => res.json())
    .then(data => {
        attendanceList.innerHTML = '';
        if (data.success && data.students) {
            if (data.students.length === 0) {
                 attendanceList.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-4">No students enrolled in this class.</td></tr>';
                 return;
            }

            data.students.forEach(s => {
                const status = s.attendance_status;

                attendanceList.innerHTML += `
                    <tr>
                        <td class="ps-3 fw-bold">${s.last_name}, ${s.first_name}</td>
                        <td class="text-center">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="status_${s.enrollment_id}" id="present_${s.enrollment_id}" value="Present" ${status === 'Present' ? 'checked' : ''}>
                                <label class="btn btn-outline-success btn-sm" for="present_${s.enrollment_id}">P</label>

                                <input type="radio" class="btn-check" name="status_${s.enrollment_id}" id="late_${s.enrollment_id}" value="Late" ${status === 'Late' ? 'checked' : ''}>
                                <label class="btn btn-outline-warning btn-sm" for="late_${s.enrollment_id}">L</label>

                                <input type="radio" class="btn-check" name="status_${s.enrollment_id}" id="absent_${s.enrollment_id}" value="Absent" ${status === 'Absent' ? 'checked' : ''}>
                                <label class="btn btn-outline-danger btn-sm" for="absent_${s.enrollment_id}">A</label>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
    });
}

function saveAttendance() {
    const date = attendanceDate.value;
    const records = [];

    // Iterate over all radio groups
    const rows = attendanceList.querySelectorAll('tr');
    rows.forEach(row => {
        const radios = row.querySelectorAll('input[type="radio"]');
        radios.forEach(radio => {
            if (radio.checked) {
                // Extract enrollment_id from name="status_123"
                const enrollmentId = radio.name.split('_')[1];
                records.push({
                    enrollment_id: enrollmentId,
                    status: radio.value
                });
            }
        });
    });

    if (records.length === 0) {
        alert("No attendance marked.");
        return;
    }

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=save_attendance&class_id=${currentClassId}&date=${date}&attendance_data=${JSON.stringify(records)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Attendance saved successfully.");
        } else {
            alert(data.message);
        }
    });
}

// Reports

function loadReportOptions() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list_classes'
    })
    .then(res => res.json())
    .then(data => {
        if(reportClassSelect) {
            reportClassSelect.innerHTML = '<option value="">Select Class...</option>';
            if (data.success && data.classes) {
                data.classes.forEach(c => {
                    reportClassSelect.innerHTML += `<option value="${c.id}">${c.code} - ${c.section}</option>`;
                });
            }
        }
    });
}

function generateClassReport() {
    const classId = reportClassSelect.value;
    if(!classId) return;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_class_attendance_report&class_id=${classId}`
    })
    .then(res => res.json())
    .then(data => {
        classReportResult.style.display = 'block';
        classReportTableBody.innerHTML = '';
        if(data.success && data.report) {
            data.report.forEach(r => {
                classReportTableBody.innerHTML += `
                    <tr>
                        <td>${r.last_name}, ${r.first_name}</td>
                        <td class="text-success">${r.present_count}</td>
                        <td class="text-danger">${r.absent_count}</td>
                        <td class="text-warning">${r.late_count}</td>
                        <td>${r.total_days}</td>
                    </tr>
                `;
            });
        }
    });
}

function searchStudentReport() {
    const query = reportStudentSearch.value;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list_students'
    })
    .then(res => res.json())
    .then(data => {
        if(data.success && data.students) {
            // Find by school_id
            const student = data.students.find(s => s.school_id === query);
            if(student) {
                loadStudentSummary(student.id, `${student.first_name} ${student.last_name}`);
            } else {
                alert("Student not found. Please enter exact School ID.");
            }
        }
    });
}

function loadStudentSummary(studentId, name) {
    reportStudentName.textContent = name;

    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_student_attendance_summary&student_id=${studentId}`
    })
    .then(res => res.json())
    .then(data => {
        studentReportResult.style.display = 'block';
        studentReportTableBody.innerHTML = '';
        if(data.success && data.summary) {
            data.summary.forEach(s => {
                const total = s.total_days;
                const percent = total > 0 ? Math.round((s.present_count / total) * 100) : 0;

                studentReportTableBody.innerHTML += `
                    <tr>
                        <td><small>${s.code}</small></td>
                        <td>${s.present_count}</td>
                        <td>${s.absent_count}</td>
                        <td>${s.late_count}</td>
                        <td>${percent}%</td>
                    </tr>
                `;
            });
        }
    });
}
