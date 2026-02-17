<?php
require_once 'db.php';

$message = '';
$messageType = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_student'])) {
    $student_id = trim($_POST['student_id']);
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $birthdate = $_POST['birthdate'];
    $address = trim($_POST['address']);
    $course = $_POST['course'];
    $year_level = $_POST['year_level'];
    $section = $_POST['section']; // e.g., 'BSIT-3A'

    if ($student_id && $last_name && $first_name && $section) {
        try {
            $pdo->beginTransaction();

            // 1. Insert Student
            $stmt = $pdo->prepare("INSERT INTO students (student_id, last_name, first_name, middle_name, birthdate, address, course, year_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $last_name, $first_name, $middle_name, $birthdate, $address, $course, $year_level]);
            $student_db_id = $pdo->lastInsertId();

            // 2. System Logic: Auto-enroll based on Section
            // Find all classes for this section
            $stmt_classes = $pdo->prepare("SELECT id FROM class_schedule WHERE section = ?");
            $stmt_classes->execute([$section]);
            $classes = $stmt_classes->fetchAll(PDO::FETCH_COLUMN);

            if ($classes) {
                $stmt_enroll = $pdo->prepare("INSERT INTO enrollments (student_id, class_schedule_id) VALUES (?, ?)");
                foreach ($classes as $class_id) {
                    $stmt_enroll->execute([$student_db_id, $class_id]);
                }
                $enrolled_count = count($classes);
                $message = "Student registered and automatically enrolled in $enrolled_count subjects for $section.";
                $messageType = "success";
            } else {
                $message = "Student registered, but no classes found for section $section.";
                $messageType = "warning";
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) { // Duplicate entry
                $message = "Error: Student ID already exists.";
            } else {
                $message = "Database Error: " . $e->getMessage();
            }
            $messageType = "danger";
        }
    } else {
        $message = "Please fill in all required fields.";
        $messageType = "danger";
    }
}

// Fetch recent students for display
$recent_students = $pdo->query("SELECT * FROM students ORDER BY id DESC LIMIT 5")->fetchAll();

// Fetch available sections (distinct from schedule)
$sections = $pdo->query("SELECT DISTINCT section FROM class_schedule")->fetchAll(PDO::FETCH_COLUMN);
if (empty($sections)) {
    // Fallback if no schedule exists yet
    $sections = ['BSIT-3A', 'BSIT-1A'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Portal - ISCC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-school-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-arrow-left-circle me-2"></i>
                <div>
                    <span class="fw-bold">Back to Dashboard</span>
                </div>
            </a>
            <span class="navbar-text text-white">Registrar Portal</span>
        </div>
    </nav>

    <div class="container mt-4">

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Registration Form -->
            <div class="col-lg-5 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="card-title text-school-primary fw-bold">
                            <i class="bi bi-person-plus-fill me-2"></i>Register Student
                        </h5>
                        <p class="text-muted small">Step 1: Encode Student Data & Assign Section</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="registrar.php">
                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold text-muted">Student ID <span class="text-danger">*</span></label>
                                <input type="text" name="student_id" class="form-control" placeholder="e.g., 2023-0001" required>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label small text-uppercase fw-bold text-muted">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label small text-uppercase fw-bold text-muted">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold text-muted">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold text-muted">Birthdate <span class="text-danger">*</span></label>
                                <input type="date" name="birthdate" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold text-muted">Address</label>
                                <input type="text" name="address" class="form-control" placeholder="City, Province">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-uppercase fw-bold text-muted">Course</label>
                                <select name="course" class="form-select" required>
                                    <option value="BS Information Technology">BS Information Technology</option>
                                    <option value="BS Hospitality Management">BS Hospitality Management</option>
                                    <option value="BS Tourism Management">BS Tourism Management</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label small text-uppercase fw-bold text-muted">Year Level</label>
                                    <select name="year_level" class="form-select" required>
                                        <option value="1">1st Year</option>
                                        <option value="2">2nd Year</option>
                                        <option value="3" selected>3rd Year</option>
                                        <option value="4">4th Year</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label small text-uppercase fw-bold text-muted">Section (Block)</label>
                                    <select name="section" class="form-select bg-warning bg-opacity-10 border-warning text-dark fw-bold" required>
                                        <option value="" disabled selected>Select Section</option>
                                        <?php foreach ($sections as $sec): ?>
                                            <option value="<?= htmlspecialchars($sec) ?>"><?= htmlspecialchars($sec) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text text-warning small"><i class="bi bi-info-circle-fill"></i> Auto-enrolls in subjects</div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="register_student" class="btn btn-school-primary py-2">
                                    <i class="bi bi-save me-2"></i>Save & Enroll
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Students List -->
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-school-primary fw-bold mb-0">
                            <i class="bi bi-list-check me-2"></i>Recent Enrollments
                        </h5>
                        <span class="badge bg-light text-dark border">Latest 5</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="small text-muted text-uppercase">ID</th>
                                        <th class="small text-muted text-uppercase">Name</th>
                                        <th class="small text-muted text-uppercase">Course</th>
                                        <th class="small text-muted text-uppercase text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_students) > 0): ?>
                                        <?php foreach ($recent_students as $student): ?>
                                            <tr>
                                                <td class="fw-bold text-secondary"><?= htmlspecialchars($student['student_id']) ?></td>
                                                <td>
                                                    <span class="fw-bold d-block"><?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?></span>
                                                    <small class="text-muted"><?= htmlspecialchars($student['course']) ?> - <?= htmlspecialchars($student['year_level']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                                        Enrolled
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No students registered yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Explanation Card -->
                <div class="card bg-info bg-opacity-10 border-info mt-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-info"><i class="bi bi-lightbulb-fill me-2"></i>How the Integrated Flow Works</h6>
                        <p class="small text-dark mb-0">
                            When you select a <strong>Section</strong> (e.g., BSIT-3A), the system automatically looks up the schedule and enrolls the student in all linked subjects (NET 101, GVC 101, etc.). This eliminates manual subject encoding per student.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
