<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

function jsonResponse($data) {
    echo json_encode($data);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Public Actions
if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$password) {
        jsonResponse(['success' => false, 'message' => 'Username and password required']);
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['department'] = $user['department'];
        jsonResponse([
            'success' => true,
            'role' => $user['role'],
            'name' => $user['name'],
            'department' => $user['department']
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Invalid credentials']);
    }
}

if ($action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true]);
}

if ($action === 'check_session') {
    if (isset($_SESSION['user_id'])) {
        jsonResponse([
            'logged_in' => true,
            'role' => $_SESSION['role'],
            'name' => $_SESSION['name'],
            'department' => $_SESSION['department']
        ]);
    } else {
        jsonResponse(['logged_in' => false]);
    }
}

// Auth Middleware
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized']);
}

// --- Registrar Actions ---

if ($action === 'create_teacher') {
    if ($_SESSION['role'] !== 'registrar') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized']);
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $department = $_POST['department'] ?? '';

    if (!$username || !$password || !$name || !$department) {
        jsonResponse(['success' => false, 'message' => 'All fields are required']);
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, department) VALUES (?, ?, 'teacher', ?, ?)");
        $stmt->execute([$username, $hashed_password, $name, $department]);
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        // Handle unique constraint violation
        if ($e->getCode() == 23000) {
            jsonResponse(['success' => false, 'message' => 'Username already exists']);
        }
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

if ($action === 'list_teachers') {
    if ($_SESSION['role'] !== 'registrar') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized']);
    }

    $stmt = $pdo->query("SELECT id, username, name, department, created_at FROM users WHERE role = 'teacher' ORDER BY name");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'teachers' => $teachers]);
}

if ($action === 'create_subject') {
    if ($_SESSION['role'] !== 'registrar') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized']);
    }

    $code = $_POST['code'] ?? '';
    $description = $_POST['description'] ?? '';

    if (!$code || !$description) {
        jsonResponse(['success' => false, 'message' => 'Code and description are required']);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO subjects (code, description) VALUES (?, ?)");
        $stmt->execute([$code, $description]);
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            jsonResponse(['success' => false, 'message' => 'Subject code already exists']);
        }
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

if ($action === 'list_subjects') {
    $stmt = $pdo->query("SELECT * FROM subjects ORDER BY code");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'subjects' => $subjects]);
}

if ($action === 'create_class') {
    if ($_SESSION['role'] !== 'registrar') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized']);
    }

    $subject_id = $_POST['subject_id'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? '';
    $section = $_POST['section'] ?? '';
    $room = $_POST['room'] ?? '';
    $academic_year = $_POST['academic_year'] ?? '2023-2024';
    $semester = $_POST['semester'] ?? '1st';

    if (!$subject_id || !$teacher_id || !$section || !$room) {
        jsonResponse(['success' => false, 'message' => 'All fields are required']);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO classes (subject_id, teacher_id, section, room, academic_year, semester) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$subject_id, $teacher_id, $section, $room, $academic_year, $semester]);
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

if ($action === 'list_classes') {
    $sql = "
        SELECT c.*, s.code, s.description, u.name as teacher_name
        FROM classes c
        JOIN subjects s ON c.subject_id = s.id
        JOIN users u ON c.teacher_id = u.id
        ORDER BY s.code, c.section
    ";
    $stmt = $pdo->query($sql);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'classes' => $classes]);
}

if ($action === 'create_student') {
    if ($_SESSION['role'] !== 'registrar') jsonResponse(['success' => false, 'message' => 'Unauthorized']);

    $school_id = $_POST['school_id'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $course = $_POST['course'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $address = $_POST['address'] ?? '';

    if (!$school_id || !$last_name || !$first_name || !$birthdate || !$course || !$year_level) {
        jsonResponse(['success' => false, 'message' => 'Required fields missing']);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO students (school_id, last_name, first_name, middle_name, birthdate, course, year_level, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$school_id, $last_name, $first_name, $middle_name, $birthdate, $course, $year_level, $address]);
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            jsonResponse(['success' => false, 'message' => 'School ID already exists']);
        }
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

if ($action === 'list_students') {
    $stmt = $pdo->query("SELECT * FROM students ORDER BY last_name, first_name");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'students' => $students]);
}

if ($action === 'enroll_student') {
    if ($_SESSION['role'] !== 'registrar') jsonResponse(['success' => false, 'message' => 'Unauthorized']);

    $student_id = $_POST['student_id'] ?? '';
    $class_id = $_POST['class_id'] ?? '';

    if (!$student_id || !$class_id) {
        jsonResponse(['success' => false, 'message' => 'Student and Class required']);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, class_id) VALUES (?, ?)");
        $stmt->execute([$student_id, $class_id]);
        jsonResponse(['success' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            jsonResponse(['success' => false, 'message' => 'Student is already enrolled in this class']);
        }
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

if ($action === 'get_student_enrollments') {
    if ($_SESSION['role'] !== 'registrar') jsonResponse(['success' => false, 'message' => 'Unauthorized']);

    $student_id = $_POST['student_id'] ?? '';

    $sql = "
        SELECT e.id, c.section, s.code, s.description
        FROM enrollments e
        JOIN classes c ON e.class_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        WHERE e.student_id = ?
        ORDER BY s.code
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'enrollments' => $enrollments]);
}

// --- Teacher Actions ---

if ($action === 'get_teacher_load') {
    if ($_SESSION['role'] !== 'teacher') jsonResponse(['success' => false, 'message' => 'Unauthorized']);

    $teacher_id = $_SESSION['user_id'];

    $sql = "
        SELECT c.*, s.code, s.description
        FROM classes c
        JOIN subjects s ON c.subject_id = s.id
        WHERE c.teacher_id = ?
        ORDER BY s.code
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$teacher_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'classes' => $classes]);
}

if ($action === 'get_class_roster') {
    $class_id = $_POST['class_id'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');

    // Verify ownership if teacher
    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$class_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized access to class']);
        }
    }

    $sql = "
        SELECT s.id, s.school_id, s.last_name, s.first_name, e.id as enrollment_id,
               a.status as attendance_status
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        LEFT JOIN attendance a ON e.id = a.enrollment_id AND a.date = ?
        WHERE e.class_id = ?
        ORDER BY s.last_name, s.first_name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date, $class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'students' => $students]);
}

if ($action === 'save_attendance') {
    if ($_SESSION['role'] !== 'teacher') jsonResponse(['success' => false, 'message' => 'Unauthorized']);

    $class_id = $_POST['class_id'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    $attendance_data = json_decode($_POST['attendance_data'], true);

    if (!$class_id || !$attendance_data) {
        jsonResponse(['success' => false, 'message' => 'Missing data']);
    }

    $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$class_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized access to class']);
    }

    $pdo->beginTransaction();
    try {
        $checkStmt = $pdo->prepare("SELECT id FROM attendance WHERE enrollment_id = ? AND date = ?");
        $updateStmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE id = ?");
        $insertStmt = $pdo->prepare("INSERT INTO attendance (enrollment_id, date, status) VALUES (?, ?, ?)");

        foreach ($attendance_data as $record) {
            $enrollment_id = $record['enrollment_id'];
            $status = $record['status'];

            $checkStmt->execute([$enrollment_id, $date]);
            $existing = $checkStmt->fetch(PDO::FETCH_COLUMN);

            if ($existing) {
                $updateStmt->execute([$status, $existing]);
            } else {
                $insertStmt->execute([$enrollment_id, $date, $status]);
            }
        }

        $pdo->commit();
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

// --- Reporting Actions ---

if ($action === 'get_class_attendance_report') {
    $class_id = $_POST['class_id'] ?? '';

    if ($_SESSION['role'] === 'teacher') {
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$class_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) jsonResponse(['success' => false, 'message' => 'Unauthorized']);
    } elseif ($_SESSION['role'] !== 'registrar') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized']);
    }

    $sql = "
        SELECT s.id, s.school_id, s.last_name, s.first_name,
               COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
               COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent_count,
               COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late_count,
               COUNT(a.id) as total_days
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        LEFT JOIN attendance a ON e.id = a.enrollment_id
        WHERE e.class_id = ?
        GROUP BY s.id
        ORDER BY s.last_name, s.first_name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class_id]);
    $report = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'report' => $report]);
}

if ($action === 'get_student_attendance_summary') {
    if ($_SESSION['role'] !== 'registrar') jsonResponse(['success' => false, 'message' => 'Unauthorized']);

    $student_id = $_POST['student_id'] ?? '';

    $sql = "
        SELECT c.id as class_id, s.code, s.description, c.section,
               COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
               COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent_count,
               COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late_count,
               COUNT(a.id) as total_days
        FROM enrollments e
        JOIN classes c ON e.class_id = c.id
        JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN attendance a ON e.id = a.enrollment_id
        WHERE e.student_id = ?
        GROUP BY c.id
        ORDER BY s.code
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['success' => true, 'summary' => $summary]);
}

jsonResponse(['success' => false, 'message' => 'Invalid action']);
?>