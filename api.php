<?php
require 'db.php';

// Helper function to send JSON response
function sendResponse($data) {
    echo json_encode($data);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if (!$action) {
    sendResponse(['error' => 'No action specified']);
}

try {
    switch ($action) {
        // AUTHENTICATION
        case 'login':
            $username = $input['username'];
            $password = $input['password'];
            $role = $input['role'];

            $table = '';
            if ($role === 'Registrar') $table = 'registrars';
            elseif ($role === 'Teacher') $table = 'teachers';
            elseif ($role === 'Student') $table = 'students';
            else sendResponse(['error' => 'Invalid role']);

            $stmt = $pdo->prepare("SELECT * FROM $table WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();

            if ($user) {
                // Add role to user object for frontend consistency
                $user['role'] = $role;
                // If student, ensure ID is passed clearly as studentId if needed, though ID is primary key
                if ($role === 'Student') $user['studentId'] = $user['id'];
                if ($role === 'Teacher') $user['teacherId'] = $user['id'];

                sendResponse(['success' => true, 'user' => $user]);
            } else {
                sendResponse(['success' => false, 'message' => 'Invalid credentials']);
            }
            break;

        // REGISTRAR: OVERVIEW
        case 'get_overview_stats':
            $stats = [];
            $stats['teachers'] = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
            $stats['active_teachers'] = $pdo->query("SELECT COUNT(*) FROM teachers WHERE status='Active'")->fetchColumn();
            $stats['loads'] = $pdo->query("SELECT COUNT(*) FROM class_schedule")->fetchColumn();
            $stats['students'] = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            $stats['subjects'] = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
            // Rooms are hardcoded in frontend constant, but we could store them. For now, just return counts.
            sendResponse($stats);
            break;

        // REGISTRAR: TEACHERS
        case 'get_teachers':
            $stmt = $pdo->query("SELECT * FROM teachers");
            sendResponse($stmt->fetchAll());
            break;

        case 'add_teacher':
            $sql = "INSERT INTO teachers (id, name, username, password, email, dept, status, avatar, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['id'], $input['name'], $input['username'], $input['password'],
                $input['email'], $input['dept'], $input['status'], $input['avatar'], $input['created_at']
            ]);
            sendResponse(['success' => true]);
            break;

        case 'update_teacher':
            $sql = "UPDATE teachers SET name=?, username=?, password=?, email=?, dept=?, status=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['name'], $input['username'], $input['password'],
                $input['email'], $input['dept'], $input['status'], $input['id']
            ]);
            sendResponse(['success' => true]);
            break;

        case 'toggle_teacher_status':
            $stmt = $pdo->prepare("UPDATE teachers SET status = CASE WHEN status = 'Active' THEN 'Inactive' ELSE 'Active' END WHERE id = ?");
            $stmt->execute([$input['id']]);
            sendResponse(['success' => true]);
            break;

        // REGISTRAR: LOADS & SUBJECTS
        case 'get_subjects':
            $stmt = $pdo->query("SELECT * FROM subjects");
            sendResponse($stmt->fetchAll());
            break;

        case 'get_loads':
            $stmt = $pdo->query("SELECT * FROM class_schedule");
            sendResponse($stmt->fetchAll());
            break;

        case 'assign_load':
            $sql = "INSERT INTO class_schedule (id, teacher_id, subject_code, course, year, section, sem, sy) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['id'], $input['teacher_id'], $input['subject_code'],
                $input['course'], $input['year'], $input['section'], $input['sem'], $input['sy']
            ]);
            sendResponse(['success' => true]);
            break;

        case 'delete_load':
            $stmt = $pdo->prepare("DELETE FROM class_schedule WHERE id = ?");
            $stmt->execute([$input['id']]);
            sendResponse(['success' => true]);
            break;

        case 'update_load_schedule':
            $sql = "UPDATE class_schedule SET room=?, day=?, start_time=?, end_time=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$input['room'], $input['day'], $input['start'], $input['end'], $input['id']]);
            sendResponse(['success' => true]);
            break;

        // REGISTRAR: STUDENTS
        case 'get_students':
            $stmt = $pdo->query("SELECT * FROM students");
            sendResponse($stmt->fetchAll());
            break;

        case 'register_student':
            $sql = "INSERT INTO students (id, surname, given, middle, course, year, section, contact, email, address, dob, sex, civil, father, mother, status, username, password, avatar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            // Auto-generate username/password logic matching React code logic if needed, but passing from frontend is easier
            $stmt->execute([
                $input['id'], $input['surname'], $input['given'], $input['middle'],
                $input['course'], $input['year'], $input['section'], $input['contact'],
                $input['email'], $input['address'], $input['dob'], $input['sex'],
                $input['civil'], $input['father'], $input['mother'], $input['status'],
                $input['id'], 'stu2024', substr($input['given'], 0, 1) . substr($input['surname'], 0, 1) // Simple avatar
            ]);
            sendResponse(['success' => true]);
            break;

        // ATTENDANCE & REPORTS
        case 'get_attendance_logs':
            $stmt = $pdo->query("SELECT * FROM attendance");
            sendResponse($stmt->fetchAll());
            break;

        case 'save_attendance':
            // input['logs'] is an array of objects
            $logs = $input['logs'];
            $sql = "INSERT INTO attendance (id, student_id, load_id, date, time_in, status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            foreach ($logs as $log) {
                // Check if exists to avoid duplicates if re-saving?
                // React code generates new IDs every save, so we assume new entries or handled by ID.
                // But simplified logic: just insert.
                $stmt->execute([
                    $log['id'], $log['studentId'], $log['loadId'],
                    $log['date'], $log['timeIn'], $log['status']
                ]);
            }
            sendResponse(['success' => true]);
            break;

        // TEACHER SPECIFIC
        case 'get_my_loads':
            // Teacher ID passed in input
            $stmt = $pdo->prepare("SELECT * FROM class_schedule WHERE teacher_id = ?");
            $stmt->execute([$input['teacher_id']]);
            sendResponse($stmt->fetchAll());
            break;

        // STUDENT SPECIFIC
        case 'get_student_profile':
            $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$input['id']]);
            sendResponse($stmt->fetch());
            break;

        case 'get_student_schedule':
             // Get loads for student's section
             // First get student section
             $stuStmt = $pdo->prepare("SELECT section FROM students WHERE id = ?");
             $stuStmt->execute([$input['student_id']]);
             $student = $stuStmt->fetch();
             if ($student) {
                 $stmt = $pdo->prepare("SELECT * FROM class_schedule WHERE section = ?");
                 $stmt->execute([$student['section']]);
                 sendResponse($stmt->fetchAll());
             } else {
                 sendResponse([]);
             }
             break;

        case 'get_student_attendance':
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ?");
            $stmt->execute([$input['student_id']]);
            sendResponse($stmt->fetchAll());
            break;

        default:
            sendResponse(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    sendResponse(['error' => $e->getMessage()]);
}
?>
