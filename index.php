<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISCC Enrollment & Attendance</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-school-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-mortarboard-fill me-2 fs-3"></i>
                <div>
                    <span class="fw-bold">ISCC</span>
                    <span class="d-block fs-6 fw-light">Enrollment & Attendance</span>
                </div>
            </a>
             <div class="d-flex align-items-center text-white" id="userSection" style="display: none;">
                <span id="userNameDisplay" class="me-3 fw-bold"></span>
                <button id="logoutBtn" class="btn btn-sm btn-outline-light">Logout</button>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">

        <!-- Login Section -->
        <div id="loginSection" class="row justify-content-center" style="display: none;">
            <div class="col-md-5 col-lg-4">
                <div class="card shadow-sm border-0 mt-5">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                             <i class="bi bi-shield-lock-fill text-school-primary fs-1"></i>
                             <h4 class="fw-bold text-school-primary mt-2">Portal Access</h4>
                        </div>
                        <form id="loginForm">
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Username</label>
                                <input type="text" class="form-control" id="loginUsername" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Password</label>
                                <input type="password" class="form-control" id="loginPassword" required>
                            </div>
                            <div class="d-grid pt-2">
                                <button type="submit" class="btn btn-school-primary py-2">Sign In</button>
                            </div>
                            <div id="loginError" class="text-danger mt-3 text-center small"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registrar Dashboard -->
        <div id="registrarDashboard" style="display: none;">
            <ul class="nav nav-pills mb-4" id="registrarTabs">
                <li class="nav-item">
                    <button class="nav-link active" data-tab="students">Students</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-tab="teachers">Teachers</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-tab="subjects">Subjects & Classes</button>
                </li>
                 <li class="nav-item">
                    <button class="nav-link" data-tab="reports">Reports</button>
                </li>
            </ul>

            <!-- Students Tab -->
            <div id="tab-students" class="dashboard-tab">
                 <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-school-primary"><i class="bi bi-people-fill me-2"></i>Student Records</h5>
                         <button id="addNewStudentBtn" class="btn btn-school-primary btn-sm">
                            <i class="bi bi-person-plus-fill me-2"></i>Register New
                        </button>
                    </div>
                    <div class="card-body">
                         <!-- Search Bar -->
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="searchStudentInput" placeholder="Search by Name or ID...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle custom-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Course</th>
                                        <th>Year</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="studentTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                 </div>
            </div>

            <!-- Teachers Tab -->
            <div id="tab-teachers" class="dashboard-tab" style="display: none;">
                 <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-school-primary"><i class="bi bi-person-badge-fill me-2"></i>Faculty Management</h5>
                         <button id="addNewTeacherBtn" class="btn btn-school-primary btn-sm">
                            <i class="bi bi-plus-circle-fill me-2"></i>New Teacher
                        </button>
                    </div>
                    <div class="card-body">
                         <div class="table-responsive">
                            <table class="table table-hover align-middle custom-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="teacherTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                 </div>
            </div>

            <!-- Subjects Tab -->
            <div id="tab-subjects" class="dashboard-tab" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                             <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-school-primary">Subjects</h5>
                                <button id="addNewSubjectBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i> Add</button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm table-hover">
                                        <thead><tr><th>Code</th><th>Description</th></tr></thead>
                                        <tbody id="subjectTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                         <div class="card shadow-sm border-0 h-100">
                             <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-school-primary">Classes (Schedule)</h5>
                                <button id="addNewClassBtn" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus"></i> Add</button>
                            </div>
                            <div class="card-body">
                                 <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm table-hover">
                                        <thead><tr><th>Subject</th><th>Section</th><th>Room</th><th>Teacher</th></tr></thead>
                                        <tbody id="classTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

             <!-- Reports Tab -->
             <div id="tab-reports" class="dashboard-tab" style="display: none;">
                 <div class="row">
                     <div class="col-md-6">
                         <div class="card shadow-sm border-0 mb-4">
                             <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                 <h5 class="fw-bold text-school-primary">Class Attendance Report</h5>
                             </div>
                             <div class="card-body">
                                 <select class="form-select mb-3" id="reportClassSelect">
                                     <option value="">Select Class...</option>
                                 </select>
                                 <button class="btn btn-school-primary w-100 mb-3" id="generateClassReportBtn">Generate Report</button>

                                 <div id="classReportResult" style="display: none;">
                                     <h6 class="fw-bold text-muted small text-uppercase">Results</h6>
                                     <div class="table-responsive">
                                         <table class="table table-sm table-bordered">
                                             <thead class="bg-light">
                                                 <tr><th>Student</th><th>P</th><th>A</th><th>L</th><th>Total</th></tr>
                                             </thead>
                                             <tbody id="classReportTableBody"></tbody>
                                         </table>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>

                     <div class="col-md-6">
                         <div class="card shadow-sm border-0 mb-4">
                             <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                 <h5 class="fw-bold text-school-primary">Student Summary</h5>
                             </div>
                             <div class="card-body">
                                 <div class="input-group mb-3">
                                     <input type="text" class="form-control" id="reportStudentSearch" placeholder="Enter Student ID">
                                     <button class="btn btn-outline-secondary" id="searchStudentReportBtn">Search</button>
                                 </div>

                                 <div id="studentReportResult" style="display: none;">
                                     <h6 class="fw-bold text-school-primary mb-2" id="reportStudentName"></h6>
                                     <div class="table-responsive">
                                         <table class="table table-sm table-bordered">
                                             <thead class="bg-light">
                                                 <tr><th>Subject</th><th>P</th><th>A</th><th>L</th><th>%</th></tr>
                                             </thead>
                                             <tbody id="studentReportTableBody"></tbody>
                                         </table>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>
        </div>

        <!-- Teacher Dashboard -->
        <div id="teacherDashboard" style="display: none;">
             <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold text-school-primary">My Schedule</h4>
                <span class="text-muted" id="currentDateDisplay"></span>
             </div>

             <div id="teacherSchedule" class="row">
                 <!-- Schedule cards -->
             </div>
        </div>

        <!-- Attendance View (Teacher) -->
        <div id="attendanceView" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <button id="backToScheduleBtn" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</button>
                 <h5 class="fw-bold text-school-primary mb-0" id="attendanceClassName"></h5>
                 <input type="date" class="form-control form-control-sm w-auto" id="attendanceDate">
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Student Name</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceList">
                                <!-- Student rows with radio buttons -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 py-3 text-end">
                    <button class="btn btn-school-primary px-4" id="saveAttendanceBtn">Save Attendance</button>
                </div>
            </div>
        </div>

    </div>

    <!-- Modals -->

    <!-- Student Form Modal (Reused) -->
    <div id="formSection" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000; background-color: rgba(0, 33, 71, 0.4); backdrop-filter: blur(2px); align-items: center; justify-content: center;">
         <div class="row justify-content-center w-100" id="formContainerRow">
                <div class="col-md-8 col-lg-6 p-0 p-md-2">
                    <div class="card shadow-sm border-0 fade-in">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                            <h5 class="card-title text-school-primary fw-bold mb-0" id="formTitle">
                                Register New Student
                            </h5>
                            <button type="button" class="btn-close" id="closeFormBtn"></button>
                        </div>
                        <div class="card-body">
                            <form id="studentForm">
                                <input type="hidden" id="studentIdDb">
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">School ID</label>
                                    <input type="text" class="form-control" id="schoolId" required>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label text-muted small fw-bold">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label text-muted small fw-bold">First Name</label>
                                        <input type="text" class="form-control" id="firstName" required>
                                    </div>
                                     <div class="col-12 mb-3">
                                        <label class="form-label text-muted small fw-bold">Middle Name</label>
                                        <input type="text" class="form-control" id="middleName">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Birthdate</label>
                                    <input type="date" class="form-control" id="birthdate" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Course</label>
                                    <select class="form-select" id="course" required>
                                        <option value="BS Hospitality Management">BS Hospitality Management</option>
                                        <option value="BS Information Technology">BS Information Technology</option>
                                        <option value="BS Cooperative Management">BS Cooperative Management</option>
                                        <option value="BS Tourism Management">BS Tourism Management</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Year Level</label>
                                    <select class="form-select" id="yearLevel" required>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3">3rd Year</option>
                                        <option value="4">4th Year</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Address</label>
                                    <textarea class="form-control" id="address" rows="2"></textarea>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-school-primary py-2">Save Student</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
         </div>
    </div>

    <!-- Teacher Form Modal -->
    <div id="teacherFormModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000; background-color: rgba(0, 33, 71, 0.4); backdrop-filter: blur(2px); align-items: center; justify-content: center;">
        <div class="card shadow-sm border-0 fade-in m-3" style="width: 100%; max-width: 500px;">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-school-primary">New Teacher Account</h5>
                <button type="button" class="btn-close close-modal-btn" data-target="teacherFormModal"></button>
            </div>
            <div class="card-body">
                <form id="teacherForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" class="form-control" id="teacherName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Department</label>
                        <input type="text" class="form-control" id="teacherDepartment" list="deptOptions" required>
                        <datalist id="deptOptions">
                            <option value="BSIT Department">
                            <option value="BSHM Department">
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Username</label>
                        <input type="text" class="form-control" id="teacherUsername" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" class="form-control" id="teacherPassword" required>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-school-primary">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Subject Form Modal -->
    <div id="subjectFormModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000; background-color: rgba(0, 33, 71, 0.4); backdrop-filter: blur(2px); align-items: center; justify-content: center;">
        <div class="card shadow-sm border-0 fade-in m-3" style="width: 100%; max-width: 500px;">
             <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-school-primary">New Subject</h5>
                <button type="button" class="btn-close close-modal-btn" data-target="subjectFormModal"></button>
            </div>
            <div class="card-body">
                <form id="subjectForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subject Code</label>
                        <input type="text" class="form-control" id="subjectCode" placeholder="e.g. NET 101" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <input type="text" class="form-control" id="subjectDesc" placeholder="e.g. Networking 1" required>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-school-primary">Save Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Class Form Modal -->
    <div id="classFormModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000; background-color: rgba(0, 33, 71, 0.4); backdrop-filter: blur(2px); align-items: center; justify-content: center;">
        <div class="card shadow-sm border-0 fade-in m-3" style="width: 100%; max-width: 500px;">
             <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-school-primary">Create Class (Schedule)</h5>
                <button type="button" class="btn-close close-modal-btn" data-target="classFormModal"></button>
            </div>
            <div class="card-body">
                <form id="classForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subject</label>
                        <select class="form-select" id="classSubjectSelect" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Teacher</label>
                        <select class="form-select" id="classTeacherSelect" required></select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Section</label>
                            <input type="text" class="form-control" id="classSection" placeholder="e.g. BSIT 3A" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Room</label>
                            <input type="text" class="form-control" id="classRoom" placeholder="e.g. 301" required>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Sem</label>
                            <select class="form-select" id="classSemester">
                                <option value="1st">1st Semester</option>
                                <option value="2nd">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">A.Y.</label>
                            <input type="text" class="form-control" id="classAY" value="2023-2024">
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-school-primary">Create Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Enrollment Modal -->
    <div id="enrollmentModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 2000; background-color: rgba(0, 33, 71, 0.4); backdrop-filter: blur(2px); align-items: center; justify-content: center;">
        <div class="card shadow-sm border-0 fade-in m-3" style="width: 100%; max-width: 600px;">
             <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-school-primary">Manage Enrollment</h5>
                <button type="button" class="btn-close close-modal-btn" data-target="enrollmentModal"></button>
            </div>
            <div class="card-body">
                <h6 id="enrollStudentName" class="mb-3 text-muted"></h6>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Add Subject (Class)</label>
                    <div class="input-group">
                        <select class="form-select" id="enrollClassSelect"></select>
                        <button class="btn btn-school-primary" id="enrollBtn">Enroll</button>
                    </div>
                </div>

                <h6 class="small fw-bold text-uppercase text-muted">Current Subjects</h6>
                <ul class="list-group list-group-flush" id="enrollmentList">
                    <!-- List of enrolled classes -->
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white text-center py-3 mt-auto border-top">
        <div class="container">
            <small class="text-muted">&copy; ISCC Enrollment System</small>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>