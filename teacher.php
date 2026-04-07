<?php
require_once 'db.php';

$message = '';

// Get all teachers for the dropdown (Simulation of login)
$teachers = $pdo->query("SELECT * FROM teachers")->fetchAll();

// Default selection
$selected_teacher_id = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : (isset($teachers[0]['id']) ? $teachers[0]['id'] : 0);
$selected_class_id = isset($_GET['class_id']) ? $_GET['class_id'] : 0;
$attendance_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch classes for the selected teacher
$classes = [];
if ($selected_teacher_id) {
    $stmt = $pdo->prepare("
        SELECT cs.id, s.sub_code, s.title, cs.section, cs.schedule_time
        FROM class_schedule cs
        JOIN subjects s ON cs.subject_id = s.id
        WHERE cs.teacher_id = ?
    ");
    $stmt->execute([$selected_teacher_id]);
    $classes = $stmt->fetchAll();
}

// Handle Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $class_id = $_POST['class_id'];
    $date = $_POST['attendance_date'];
    $attendance_data = isset($_POST['status']) ? $_POST['status'] : [];

    if ($class_id && $date) {
        try {
            $pdo->beginTransaction();

            foreach ($attendance_data as $enrollment_id => $status) {
                // Check if record exists for this date
                $stmt_check = $pdo->prepare("SELECT id FROM attendance WHERE enrollment_id = ? AND date = ?");
                $stmt_check->execute([$enrollment_id, $date]);
                $existing = $stmt_check->fetch();

                if ($existing) {
                    // Update
                    $stmt_update = $pdo->prepare("UPDATE attendance SET status = ?, time_log = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt_update->execute([$status, $existing['id']]);
                } else {
                    // Insert
                    $stmt_insert = $pdo->prepare("INSERT INTO attendance (enrollment_id, date, status) VALUES (?, ?, ?)");
                    $stmt_insert->execute([$enrollment_id, $date, $status]);
                }
            }

            $pdo->commit();
            $message = "Attendance saved successfully for $date.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error saving attendance: " . $e->getMessage();
        }
    }
}

// Fetch Enrolled Students if a class is selected
$students = [];
if ($selected_class_id) {
    $stmt = $pdo->prepare("
        SELECT
            s.id AS student_id,
            s.student_id AS id_no,
            s.last_name,
            s.first_name,
            e.id AS enrollment_id,
            a.status AS attendance_status
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        LEFT JOIN attendance a ON e.id = a.enrollment_id AND a.date = ?
        WHERE e.class_schedule_id = ?
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute([$attendance_date, $selected_class_id]);
    $students = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Portal - ISCC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-arrow-left-circle me-2"></i>
                <div>
                    <span class="fw-bold">Back to Dashboard</span>
                </div>
            </a>
            <span class="navbar-text text-white">Teacher Portal</span>
        </div>
    </nav>

    <div class="container mt-4">

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Controls -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body bg-white py-3">
                <form method="GET" action="teacher.php" class="row g-3 align-items-end">

                    <!-- Teacher Selector (Simulation) -->
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-bold">Select Teacher</label>
                        <select name="teacher_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>" <?= $selected_teacher_id == $teacher['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($teacher['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Class Selector -->
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-bold">Select Class (Subject)</label>
                        <select name="class_id" class="form-select" onchange="this.form.submit()">
                            <option value="0">-- Select Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" <?= $selected_class_id == $class['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['sub_code']) ?> - <?= htmlspecialchars($class['section']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Selector -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-bold">Date</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($attendance_date) ?>" onchange="this.form.submit()">
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selected_class_id): ?>
            <!-- Class Roster -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-success fw-bold mb-0">
                            <i class="bi bi-people-fill me-2"></i>Class Roster
                        </h5>
                        <p class="text-muted small mb-0">
                            Subject: <strong class="text-dark">
                                <?php
                                    // Find class details for display
                                    $current_class = array_filter($classes, function($c) use ($selected_class_id) { return $c['id'] == $selected_class_id; });
                                    $current_class = reset($current_class);
                                    echo htmlspecialchars($current_class['sub_code'] . ' - ' . $current_class['title']);
                                ?>
                            </strong>
                            | Schedule: <?= htmlspecialchars($current_class['schedule_time']) ?>
                        </p>
                    </div>
                    <span class="badge bg-light text-dark border">
                        Students: <?= count($students) ?>
                    </span>
                </div>

                <div class="card-body">
                    <?php if (count($students) > 0): ?>
                        <form method="POST" action="teacher.php">
                            <input type="hidden" name="class_id" value="<?= $selected_class_id ?>">
                            <input type="hidden" name="attendance_date" value="<?= $attendance_date ?>">

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-3">ID No.</th>
                                            <th>Student Name</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <?php
                                                $status = $student['attendance_status'] ?: 'Present'; // Default to Present
                                                $eid = $student['enrollment_id'];
                                            ?>
                                            <tr>
                                                <td class="ps-3 fw-bold text-secondary"><?= htmlspecialchars($student['id_no']) ?></td>
                                                <td>
                                                    <span class="fw-bold"><?= htmlspecialchars($student['last_name']) ?>, <?= htmlspecialchars($student['first_name']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-inline-flex bg-white rounded border p-1">
                                                        <!-- Present -->
                                                        <label>
                                                            <input type="radio" name="status[<?= $eid ?>]" value="Present" class="status-radio" <?= $status == 'Present' ? 'checked' : '' ?>>
                                                            <span class="status-label present">P</span>
                                                        </label>
                                                        <!-- Absent -->
                                                        <label>
                                                            <input type="radio" name="status[<?= $eid ?>]" value="Absent" class="status-radio" <?= $status == 'Absent' ? 'checked' : '' ?>>
                                                            <span class="status-label absent">A</span>
                                                        </label>
                                                        <!-- Late -->
                                                        <label>
                                                            <input type="radio" name="status[<?= $eid ?>]" value="Late" class="status-radio" <?= $status == 'Late' ? 'checked' : '' ?>>
                                                            <span class="status-label late">L</span>
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-grid d-md-flex justify-content-md-end mt-3">
                                <button type="submit" name="save_attendance" class="btn btn-success px-5">
                                    <i class="bi bi-check-circle-fill me-2"></i>Save Attendance
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                            <p>No students enrolled in this class yet.</p>
                            <a href="registrar.php" class="btn btn-sm btn-outline-primary">Go to Registrar to Enroll</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted fade-in">
                <i class="bi bi-arrow-up-circle fs-1 d-block mb-3 text-success"></i>
                <h5>Select a Class to Begin</h5>
                <p>Please select a subject from the dropdown above to view the roster.</p>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
