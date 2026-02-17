<?php
// Dashboard for Student Registration System
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISCC Student System Dashboard</title>
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
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-mortarboard-fill me-2 fs-3"></i>
                <div>
                    <span class="fw-bold">ISCC</span>
                    <span class="d-block fs-6 fw-light">Integrated System</span>
                </div>
            </a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="text-center mb-5 fade-in">
            <h1 class="fw-bold text-school-primary">System Dashboard</h1>
            <p class="text-muted lead">Manage the flow from Enrollment to Attendance</p>
        </div>

        <div class="row g-4 justify-content-center">

            <!-- Phase A: Registrar -->
            <div class="col-md-4 fade-in" style="animation-delay: 0.1s;">
                <div class="card dashboard-card shadow-sm p-4 text-center" onclick="window.location.href='registrar.php'">
                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h3 class="h4 fw-bold text-dark">Registrar Portal</h3>
                    <p class="text-muted small mb-3">Phase A: The Source</p>
                    <p class="card-text text-secondary">
                        Encode student data, assign subjects, and manage sections. The entry point for all data.
                    </p>
                    <a href="registrar.php" class="btn btn-outline-primary mt-auto">Open Registrar</a>
                </div>
            </div>

            <!-- Phase B: System Logic (Visual Only) -->
            <div class="col-md-4 fade-in" style="animation-delay: 0.2s;">
                <div class="card dashboard-card shadow-sm p-4 text-center bg-light border-0">
                    <div class="icon-box bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-cpu"></i>
                    </div>
                    <h3 class="h4 fw-bold text-dark">System Logic</h3>
                    <p class="text-muted small mb-3">Phase B: The Processing</p>
                    <p class="card-text text-secondary">
                        Automatically maps students to teachers based on subject codes (e.g., NET 101 -> Engr. Doe).
                    </p>
                    <button class="btn btn-sm btn-light disabled border">Automated Process</button>
                </div>
            </div>

            <!-- Phase C: Teacher -->
            <div class="col-md-4 fade-in" style="animation-delay: 0.3s;">
                <div class="card dashboard-card shadow-sm p-4 text-center" onclick="window.location.href='teacher.php'">
                    <div class="icon-box bg-success bg-opacity-10 text-success">
                        <i class="bi bi-easel"></i>
                    </div>
                    <h3 class="h4 fw-bold text-dark">Teacher Portal</h3>
                    <p class="text-muted small mb-3">Phase C: The Execution</p>
                    <p class="card-text text-secondary">
                        View class rosters and mark attendance. Only shows students enrolled in your subjects.
                    </p>
                    <a href="teacher.php" class="btn btn-outline-success mt-auto">Open Teacher</a>
                </div>
            </div>

        </div>

        <!-- Explanation Section -->
        <div class="mt-5 pt-4 border-top">
            <h4 class="fw-bold text-school-primary mb-4"><i class="bi bi-info-circle me-2"></i>How It Works</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item bg-transparent">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1 text-primary">1. Registrar Input</h5>
                            </div>
                            <p class="mb-1 small text-muted">The registrar enters student details (Name, ID) and assigns them to a block (e.g., BSIT-3A). This links the student to all subjects offered for that block.</p>
                        </div>
                        <div class="list-group-item bg-transparent">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1 text-secondary">2. System Mapping</h5>
                            </div>
                            <p class="mb-1 small text-muted">The system checks the <strong>Class Schedule</strong> database. If BSIT-3A has "NET 101" with "Engr. Doe", the student is automatically added to Engr. Doe's list.</p>
                        </div>
                        <div class="list-group-item bg-transparent">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1 text-success">3. Teacher Attendance</h5>
                            </div>
                            <p class="mb-1 small text-muted">The teacher logs in, selects their subject, and sees the roster. They can mark attendance, which validates that the student is in the right class at the right time.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-center bg-white rounded shadow-sm p-4">
                    <div class="text-center">
                        <p class="fw-bold text-muted mb-2">Data Flow Visualization</p>
                        <div class="d-flex justify-content-center align-items-center gap-2">
                            <span class="badge bg-primary p-2">Student ID</span>
                            <i class="bi bi-arrow-right text-muted"></i>
                            <span class="badge bg-secondary p-2">Subject Code</span>
                            <i class="bi bi-arrow-right text-muted"></i>
                            <span class="badge bg-success p-2">Teacher Roster</span>
                        </div>
                        <div class="mt-3 text-muted small">
                            "Handshake Fields" ensure data integrity across the system.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer class="bg-white text-center py-3 mt-auto border-top">
        <div class="container">
            <small class="text-muted">&copy; JAYJR-R-DOLORES_BSIT-3A | Integrated Student System</small>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
