<?php
require_once 'db.php';

// Handle API Requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    try {
        if ($action === 'get_subjects') {
            // Get all subjects with their class schedule details (Teacher, Room, Schedule)
            // Grouping by sub_code to match UI expectation (Assuming unique sub_code per section for now, or just listing unique offerings)
            // The UI expects a list of subjects available for enrollment.
            // We'll fetch all unique subjects and pick the first schedule details for display purposes,
            // OR returns all offerings. The UI JS expects a simple array of objects.
            $stmt = $pdo->query("
                SELECT
                    s.sub_code as code,
                    s.title as name,
                    s.units,
                    t.name as teacher,
                    cs.schedule_time as schedule,
                    cs.room,
                    cs.section,
                    cs.id as schedule_id
                FROM class_schedule cs
                JOIN subjects s ON cs.subject_id = s.id
                JOIN teachers t ON cs.teacher_id = t.id
                ORDER BY s.sub_code
            ");
            echo json_encode($stmt->fetchAll());
            exit;

        } elseif ($action === 'get_students') {
            // Get all students with their enrolled subjects
            $students = $pdo->query("SELECT * FROM students ORDER BY last_name, first_name")->fetchAll();

            // For each student, get their enrolled subject codes
            foreach ($students as &$student) {
                $stmt = $pdo->prepare("
                    SELECT s.sub_code
                    FROM enrollments e
                    JOIN class_schedule cs ON e.class_schedule_id = cs.id
                    JOIN subjects s ON cs.subject_id = s.id
                    WHERE e.student_id = ?
                ");
                $stmt->execute([$student['id']]);
                $student['subjects'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Map db fields to UI fields
                $student['id'] = $student['student_id']; // UI uses 'id' string (ISCC-...)
                $student['last'] = $student['last_name'];
                $student['first'] = $student['first_name'];
                // contact, address, section are already matching
            }
            echo json_encode($students);
            exit;

        } elseif ($action === 'enroll_student') {
            $input = json_decode(file_get_contents('php://input'), true);

            $pdo->beginTransaction();

            // Insert Student
            $stmt = $pdo->prepare("INSERT INTO students (student_id, last_name, first_name, contact, email, address, section, course, year_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['id'],
                $input['last'],
                $input['first'],
                $input['contact'],
                $input['email'],
                $input['address'],
                $input['section'],
                'BSIT', // Default based on UI context
                3       // Default based on UI context
            ]);
            $db_id = $pdo->lastInsertId();

            // Enroll in selected subjects
            // input['subjects'] is array of codes (e.g. ['NET 101'])
            // We need to find the class_schedule_id for these codes for the given section
            // Fallback: if specific section schedule not found, pick any matching subject code (demo logic)
            foreach ($input['subjects'] as $code) {
                $stmt_cls = $pdo->prepare("
                    SELECT cs.id FROM class_schedule cs
                    JOIN subjects s ON cs.subject_id = s.id
                    WHERE s.sub_code = ? AND cs.section = ?
                ");
                $stmt_cls->execute([$code, $input['section']]);
                $schedule_id = $stmt_cls->fetchColumn();

                if (!$schedule_id) {
                    // Try to find ANY schedule for this subject if section specific one is missing (fallback)
                    $stmt_cls = $pdo->prepare("
                        SELECT cs.id FROM class_schedule cs
                        JOIN subjects s ON cs.subject_id = s.id
                        WHERE s.sub_code = ? LIMIT 1
                    ");
                    $stmt_cls->execute([$code]);
                    $schedule_id = $stmt_cls->fetchColumn();
                }

                if ($schedule_id) {
                    $stmt_enroll = $pdo->prepare("INSERT INTO enrollments (student_id, class_schedule_id) VALUES (?, ?)");
                    $stmt_enroll->execute([$db_id, $schedule_id]);
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
            exit;

        } elseif ($action === 'delete_student') {
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt->execute([$input['id']]);
            echo json_encode(['success' => true]);
            exit;

        } elseif ($action === 'get_attendance') {
            $stmt = $pdo->query("
                SELECT
                    a.id,
                    a.time_log as time,
                    a.date,
                    s.student_id as sid,
                    CONCAT(s.last_name, ', ', s.first_name) as name,
                    sub.sub_code as code,
                    a.status,
                    a.remarks
                FROM attendance a
                JOIN enrollments e ON a.enrollment_id = e.id
                JOIN students s ON e.student_id = s.id
                JOIN class_schedule cs ON e.class_schedule_id = cs.id
                JOIN subjects sub ON cs.subject_id = sub.id
                ORDER BY a.created_at DESC
            ");
            echo json_encode($stmt->fetchAll());
            exit;

        } elseif ($action === 'log_attendance') {
            $input = json_decode(file_get_contents('php://input'), true);

            // Find Enrollment ID
            $stmt = $pdo->prepare("
                SELECT e.id FROM enrollments e
                JOIN students s ON e.student_id = s.id
                JOIN class_schedule cs ON e.class_schedule_id = cs.id
                JOIN subjects sub ON cs.subject_id = sub.id
                WHERE s.student_id = ? AND sub.sub_code = ?
            ");
            $stmt->execute([$input['sid'], $input['code']]);
            $enrollment_id = $stmt->fetchColumn();

            if ($enrollment_id) {
                // Check duplicate for date/subject/student
                $stmt_check = $pdo->prepare("SELECT id FROM attendance WHERE enrollment_id = ? AND date = ?");
                $stmt_check->execute([$enrollment_id, $input['date']]);

                if ($stmt_check->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Duplicate entry']);
                } else {
                    $stmt_ins = $pdo->prepare("INSERT INTO attendance (enrollment_id, date, time_log, status, remarks) VALUES (?, ?, ?, ?, ?)");
                    $stmt_ins->execute([$enrollment_id, $input['date'], $input['time'], $input['status'], $input['remarks']]);
                    echo json_encode(['success' => true]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Enrollment not found']);
            }
            exit;

        } elseif ($action === 'delete_attendance') {
            $input = json_decode(file_get_contents('php://input'), true);
            // We need the database ID for safe deletion, but the UI passed an index.
            // Ideally UI should store ID. We'll fetch all and assume the UI order matches or update UI to use ID.
            // For now, let's update the GET to return ID and UI to use ID.
            if (isset($input['id'])) {
                $stmt = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
                $stmt->execute([$input['id']]);
                echo json_encode(['success' => true]);
            }
            exit;
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ISCC — Enrollment & Attendance System</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=JetBrains+Mono:wght@300;400;500&family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg:#060d18; --surface:#0f1a2e; --surface2:#16243d; --surface3:#1e304f;
  --border:rgba(255,255,255,.07); --border2:rgba(255,255,255,.12);
  --cyan:#22d3ee; --cyan2:#0891b2; --violet:#a78bfa; --violet2:#7c3aed;
  --green:#34d399; --amber:#fbbf24; --red:#f87171;
  --text:#e8f0fe; --muted:rgba(232,240,254,.45); --dim:rgba(232,240,254,.2);
}
*{margin:0;padding:0;box-sizing:border-box;}
html,body{height:100%;overflow:hidden;}
body{background:var(--bg);color:var(--text);font-family:'Nunito',sans-serif;}

/* ── GRID BG ── */
body::before{content:'';position:fixed;inset:0;
  background:
    radial-gradient(ellipse 80% 60% at 15% 15%, rgba(34,211,238,.07) 0%, transparent 55%),
    radial-gradient(ellipse 70% 60% at 85% 85%, rgba(167,139,250,.07) 0%, transparent 55%),
    radial-gradient(ellipse 50% 40% at 50% 50%, rgba(6,13,24,.4) 0%, transparent 70%),
    repeating-linear-gradient(0deg,transparent,transparent 55px,rgba(255,255,255,.013) 55px,rgba(255,255,255,.013) 56px),
    repeating-linear-gradient(90deg,transparent,transparent 55px,rgba(255,255,255,.013) 55px,rgba(255,255,255,.013) 56px);
  pointer-events:none;z-index:0;}

/* ════════════════════════════════════
   ROLE SELECTOR SCREEN
════════════════════════════════════ */
#screen-role{
  position:fixed;inset:0;z-index:200;
  display:flex;align-items:center;justify-content:center;
  animation:fadeIn .5s ease;
}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes fadeOut{from{opacity:1;}to{opacity:0;}}

.role-wrap{position:relative;z-index:1;width:100%;max-width:700px;padding:24px;text-align:center;}

.role-brand{margin-bottom:36px;animation:slideDown .6s ease;}
.role-logo{width:80px;height:80px;border-radius:24px;
  background:linear-gradient(135deg,var(--cyan),var(--violet));
  display:flex;align-items:center;justify-content:center;
  font-size:40px;margin:0 auto 18px;
  box-shadow:0 0 60px rgba(34,211,238,.2),0 0 120px rgba(167,139,250,.1);}
.role-title{font-family:'Syne',sans-serif;font-size:clamp(28px,5vw,42px);font-weight:800;color:#fff;line-height:1.1;}
.role-title span{
  background:linear-gradient(135deg,var(--cyan),var(--violet));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.role-sub{margin-top:10px;font-size:14px;color:var(--muted);}

.role-cards{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:28px;}
@media(max-width:600px){.role-cards{grid-template-columns:1fr;}}

.role-card{
  background:rgba(15,26,46,.8);backdrop-filter:blur(20px);
  border:1px solid var(--border2);border-radius:22px;
  padding:28px 20px;cursor:pointer;
  transition:all .3s cubic-bezier(.34,1.56,.64,1);
  position:relative;overflow:hidden;
  animation:slideUp .5s ease both;
}
.role-card:nth-child(1){animation-delay:.1s;}
.role-card:nth-child(2){animation-delay:.2s;}
.role-card:nth-child(3){animation-delay:.3s;}

.role-card::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,transparent 40%,rgba(255,255,255,.03));
  opacity:0;transition:opacity .3s;}
.role-card::after{
  content:'';position:absolute;
  top:0;left:0;right:0;height:3px;
  border-radius:22px 22px 0 0;
  transform:scaleX(0);transition:transform .35s ease;}
.role-card:hover{transform:translateY(-8px) scale(1.02);border-color:transparent;}
.role-card:hover::before{opacity:1;}
.role-card:hover::after{transform:scaleX(1);}
.role-card:active{transform:translateY(-4px) scale(.99);}

.rc-registrar::after{background:linear-gradient(90deg,var(--cyan),#67e8f9);}
.rc-registrar:hover{box-shadow:0 20px 60px rgba(34,211,238,.2),0 0 0 1px rgba(34,211,238,.25);}
.rc-teacher::after{background:linear-gradient(90deg,var(--violet),#c4b5fd);}
.rc-teacher:hover{box-shadow:0 20px 60px rgba(167,139,250,.2),0 0 0 1px rgba(167,139,250,.25);}
.rc-admin::after{background:linear-gradient(90deg,var(--amber),#fde68a);}
.rc-admin:hover{box-shadow:0 20px 60px rgba(251,191,36,.2),0 0 0 1px rgba(251,191,36,.25);}

.rc-icon{font-size:44px;margin-bottom:14px;display:block;filter:drop-shadow(0 4px 12px rgba(0,0,0,.3));}
.rc-name{font-family:'Syne',sans-serif;font-size:19px;font-weight:800;color:#fff;margin-bottom:6px;}
.rc-registrar .rc-name{color:var(--cyan);}
.rc-teacher .rc-name{color:var(--violet);}
.rc-admin .rc-name{color:var(--amber);}
.rc-desc{font-size:12px;color:var(--muted);line-height:1.5;margin-bottom:14px;}
.rc-systems{display:flex;flex-direction:column;gap:5px;}
.rc-sys{
  font-family:'JetBrains Mono',monospace;font-size:10px;
  letter-spacing:1px;padding:4px 10px;border-radius:7px;
  display:inline-block;text-align:left;}
.rc-registrar .rc-sys{background:rgba(34,211,238,.1);color:var(--cyan);}
.rc-teacher .rc-sys{background:rgba(167,139,250,.1);color:var(--violet);}
.rc-admin .rc-sys{background:rgba(251,191,36,.1);color:var(--amber);}
.rc-enter{
  margin-top:16px;padding:8px 20px;border-radius:100px;border:none;
  font-family:'Syne',sans-serif;font-size:12px;font-weight:700;
  cursor:pointer;transition:all .2s;letter-spacing:.5px;}
.rc-registrar .rc-enter{background:rgba(34,211,238,.15);color:var(--cyan);}
.rc-registrar:hover .rc-enter{background:var(--cyan);color:#060d18;}
.rc-teacher .rc-enter{background:rgba(167,139,250,.15);color:var(--violet);}
.rc-teacher:hover .rc-enter{background:var(--violet);color:#fff;}
.rc-admin .rc-enter{background:rgba(251,191,36,.15);color:var(--amber);}
.rc-admin:hover .rc-enter{background:var(--amber);color:#060d18;}

.role-footer{font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:2px;color:var(--dim);}

@keyframes slideDown{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}
@keyframes slideUp{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}

/* ════════════════════════════════════
   MAIN APP (hidden until role chosen)
════════════════════════════════════ */
#app{position:fixed;inset:0;z-index:100;display:none;flex-direction:column;overflow:hidden;}
#app.visible{display:flex;}

/* ── TOP NAV ── */
.topnav{
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 24px;
  background:rgba(6,13,24,.9);backdrop-filter:blur(20px);
  border-bottom:1px solid var(--border);
  flex-shrink:0;z-index:50;
}
.nav-left{display:flex;align-items:center;gap:14px;}
.back-btn{
  width:34px;height:34px;border-radius:9px;
  border:1px solid var(--border2);background:rgba(255,255,255,.04);
  color:var(--muted);font-size:16px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:all .2s;}
.back-btn:hover{background:rgba(248,113,113,.1);color:var(--red);border-color:rgba(248,113,113,.3);}
.nav-logo{font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:#fff;}
.nav-logo span{color:var(--cyan);}
.role-chip{
  padding:4px 12px;border-radius:100px;
  font-family:'JetBrains Mono',monospace;font-size:10px;font-weight:500;
  letter-spacing:1.5px;text-transform:uppercase;}
.chip-registrar{background:rgba(34,211,238,.1);color:var(--cyan);border:1px solid rgba(34,211,238,.2);}
.chip-teacher{background:rgba(167,139,250,.1);color:var(--violet);border:1px solid rgba(167,139,250,.2);}
.chip-admin{background:rgba(251,191,36,.1);color:var(--amber);border:1px solid rgba(251,191,36,.2);}

.nav-tabs{display:flex;gap:4px;background:rgba(255,255,255,.04);padding:4px;border-radius:11px;border:1px solid var(--border);}
.nav-tab{
  padding:7px 18px;border-radius:8px;border:none;
  font-family:'Syne',sans-serif;font-size:12px;font-weight:700;
  cursor:pointer;transition:all .22s;color:var(--muted);background:transparent;
  display:flex;align-items:center;gap:5px;}
.nav-tab.ae{background:rgba(34,211,238,.15);color:var(--cyan);border:1px solid rgba(34,211,238,.25);}
.nav-tab.aa{background:rgba(167,139,250,.15);color:var(--violet);border:1px solid rgba(167,139,250,.25);}
.nav-tab:disabled{opacity:.3;cursor:not-allowed;}

.nav-right{display:flex;align-items:center;gap:10px;}
.stat-pill{
  padding:5px 12px;border-radius:100px;
  font-family:'JetBrains Mono',monospace;font-size:10px;
  display:flex;align-items:center;gap:5px;}
.sp-e{background:rgba(34,211,238,.08);color:var(--cyan);border:1px solid rgba(34,211,238,.15);}
.sp-a{background:rgba(167,139,250,.08);color:var(--violet);border:1px solid rgba(167,139,250,.15);}
.sdot{width:5px;height:5px;border-radius:50%;background:currentColor;animation:pulse 2s infinite;}

/* ── PANELS ── */
.panel{display:none;flex:1;overflow-y:auto;padding:24px;animation:panelIn .35s ease;}
.panel.active{display:block;}
@keyframes panelIn{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.4;transform:scale(.7);}}

/* ── COMPONENTS ── */
.sys-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:22px;flex-wrap:wrap;gap:12px;}
.sys-badge{font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:3px;text-transform:uppercase;padding:4px 14px;border-radius:100px;display:inline-block;margin-bottom:8px;}
.sb-e{background:rgba(34,211,238,.1);color:var(--cyan);border:1px solid rgba(34,211,238,.2);}
.sb-a{background:rgba(167,139,250,.1);color:var(--violet);border:1px solid rgba(167,139,250,.2);}
.sys-title{font-family:'Syne',sans-serif;font-size:clamp(20px,3vw,30px);font-weight:800;color:#fff;line-height:1.1;}
.te{color:var(--cyan);}.ta{color:var(--violet);}

.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:22px;}
@media(max-width:700px){.stats-row{grid-template-columns:repeat(2,1fr);}}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:16px 18px;transition:transform .2s;}
.stat-card:hover{transform:translateY(-3px);}
.stat-lbl{font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--dim);margin-bottom:6px;}
.stat-val{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;color:#fff;}
.stat-sub{font-size:11px;color:var(--muted);margin-top:2px;}

.two-col{display:grid;grid-template-columns:1fr 1.5fr;gap:16px;}
@media(max-width:950px){.two-col{grid-template-columns:1fr;}}

.card{background:var(--surface);border:1px solid var(--border);border-radius:18px;overflow:hidden;}
.card-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.card-title{font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;}
.cico{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;}
.ci-e{background:rgba(34,211,238,.1);}
.ci-a{background:rgba(167,139,250,.1);}
.ci-g{background:rgba(52,211,153,.1);}
.card-body{padding:18px 20px;}

.form-group{margin-bottom:13px;}
.form-group label{display:block;font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:6px;}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:9px 13px;background:var(--surface2);border:1px solid var(--border);border-radius:9px;color:var(--text);font-family:'Nunito',sans-serif;font-size:13px;outline:none;transition:border .2s,box-shadow .2s;}
.form-group input:focus,.form-group select:focus{border-color:rgba(34,211,238,.45);box-shadow:0 0 0 3px rgba(34,211,238,.06);}
.ap .form-group input:focus,.ap .form-group select:focus{border-color:rgba(167,139,250,.45);box-shadow:0 0 0 3px rgba(167,139,250,.06);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}

.btn{padding:9px 18px;border:none;border-radius:9px;font-family:'Syne',sans-serif;font-size:12px;font-weight:700;cursor:pointer;transition:all .22s;display:inline-flex;align-items:center;gap:5px;}
.btn-full{width:100%;justify-content:center;}
.btn-e{background:linear-gradient(135deg,var(--cyan),var(--cyan2));color:#060d18;}
.btn-e:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(34,211,238,.3);}
.btn-a{background:linear-gradient(135deg,var(--violet),var(--violet2));color:#fff;}
.btn-a:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(167,139,250,.3);}
.btn-ghost{background:rgba(255,255,255,.05);color:var(--muted);border:1px solid var(--border);}
.btn-ghost:hover{background:rgba(255,255,255,.09);color:var(--text);}
.btn-red{background:rgba(248,113,113,.1);color:var(--red);border:1px solid rgba(248,113,113,.2);}
.btn-red:hover{background:rgba(248,113,113,.2);}
.btn:active{transform:scale(.97);}

.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead tr{border-bottom:1px solid var(--border);}
th{padding:9px 13px;text-align:left;font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--dim);font-weight:400;}
tbody tr{border-bottom:1px solid rgba(255,255,255,.03);transition:background .13s;}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:rgba(255,255,255,.03);}
td{padding:10px 13px;font-size:12px;color:var(--text);vertical-align:middle;}

.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:100px;font-family:'JetBrains Mono',monospace;font-size:9px;font-weight:500;}
.bd{width:4px;height:4px;border-radius:50%;background:currentColor;}
.b-enrolled{background:rgba(34,211,238,.1);color:var(--cyan);border:1px solid rgba(34,211,238,.18);}
.b-present{background:rgba(52,211,153,.1);color:var(--green);border:1px solid rgba(52,211,153,.18);}
.b-absent{background:rgba(248,113,113,.1);color:var(--red);border:1px solid rgba(248,113,113,.18);}
.b-late{background:rgba(251,191,36,.1);color:var(--amber);border:1px solid rgba(251,191,36,.18);}

.search-bar{display:flex;gap:8px;margin-bottom:12px;}
.search-input{flex:1;padding:8px 13px;background:var(--surface2);border:1px solid var(--border);border-radius:9px;color:var(--text);font-family:'Nunito',sans-serif;font-size:13px;outline:none;}
.search-input:focus{border-color:rgba(34,211,238,.35);}
.filter-sel{padding:8px 11px;background:var(--surface2);border:1px solid var(--border);border-radius:9px;color:var(--text);font-family:'JetBrains Mono',monospace;font-size:10px;outline:none;cursor:pointer;}

.empty-state{text-align:center;padding:32px 16px;color:var(--dim);}
.empty-state .ei{font-size:34px;margin-bottom:8px;opacity:.5;}
.sep{height:1px;background:var(--border);margin:16px 0;}

.units-bar{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--surface2);border:1px solid var(--border);border-radius:9px;margin-bottom:12px;}
.units-bar .ul{font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);}
.units-bar .uv{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--cyan);}
.units-bar .um{font-size:11px;color:var(--dim);}

.sub-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-bottom:14px;}
.sub-lbl{display:flex;align-items:flex-start;gap:7px;padding:8px 10px;background:var(--surface2);border:1px solid var(--border);border-radius:9px;cursor:pointer;transition:all .2s;user-select:none;}
.sub-lbl:hover{background:var(--surface3);}
.sub-lbl.checked{background:rgba(34,211,238,.07);border-color:rgba(34,211,238,.28);}
.sub-chk{width:15px;height:15px;border-radius:4px;border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:9px;flex-shrink:0;margin-top:1px;transition:all .2s;color:transparent;}
.sub-lbl.checked .sub-chk{background:var(--cyan);border-color:var(--cyan);color:#060d18;}
.sub-code{font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--cyan);}
.sub-name{font-size:11px;color:var(--text);line-height:1.3;}
.sub-units{font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--dim);}

/* MANUAL INPUT HIGHLIGHT */
.id-input-highlight{border-color:rgba(167,139,250,.5) !important;box-shadow:0 0 0 3px rgba(167,139,250,.1) !important;}
.verified-box{background:rgba(52,211,153,.07);border:1px solid rgba(52,211,153,.2);border-radius:9px;padding:10px 13px;margin-bottom:12px;}
.v-label{font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:1.5px;color:var(--green);margin-bottom:3px;}
.v-name{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#fff;}
.v-info{font-size:11px;color:var(--muted);margin-top:2px;}

.prog-bar{height:4px;background:rgba(255,255,255,.05);border-radius:4px;overflow:hidden;margin-top:4px;}
.prog-fill{height:100%;border-radius:4px;transition:width .4s ease;}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(8px);z-index:300;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--surface);border:1px solid var(--border2);border-radius:20px;padding:26px;width:100%;max-width:500px;animation:modalIn .3s ease;max-height:92vh;overflow-y:auto;}
@keyframes modalIn{from{opacity:0;transform:scale(.94);}to{opacity:1;transform:scale(1);}}
.modal-title{font-family:'Syne',sans-serif;font-size:17px;font-weight:800;color:#fff;margin-bottom:18px;display:flex;align-items:center;gap:8px;}

/* EXIT CONFIRM */
.exit-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);backdrop-filter:blur(12px);z-index:500;align-items:center;justify-content:center;}
.exit-overlay.open{display:flex;animation:fadeIn .2s ease;}
.exit-card{background:var(--surface);border:1px solid rgba(248,113,113,.25);border-radius:22px;padding:32px;max-width:380px;width:90%;text-align:center;animation:modalIn .25s ease;}
.exit-icon{font-size:50px;margin-bottom:16px;}
.exit-title{font-family:'Syne',sans-serif;font-size:20px;font-weight:800;color:#fff;margin-bottom:8px;}
.exit-sub{font-size:13px;color:var(--muted);margin-bottom:24px;line-height:1.6;}
.exit-btns{display:flex;gap:10px;}

/* TOAST */
#toast-wrap{position:fixed;bottom:22px;right:22px;z-index:999;display:flex;flex-direction:column;gap:7px;pointer-events:none;}
.toast{padding:11px 17px;border-radius:11px;backdrop-filter:blur(20px);display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;min-width:240px;pointer-events:all;border:1px solid transparent;animation:toastIn .3s ease,toastOut .3s ease 2.7s both;}
.t-success{background:rgba(52,211,153,.14);color:var(--green);border-color:rgba(52,211,153,.25);}
.t-error{background:rgba(248,113,113,.14);color:var(--red);border-color:rgba(248,113,113,.25);}
.t-info{background:rgba(34,211,238,.14);color:var(--cyan);border-color:rgba(34,211,238,.25);}
@keyframes toastIn{from{opacity:0;transform:translateX(28px);}to{opacity:1;transform:translateX(0);}}
@keyframes toastOut{from{opacity:1;}to{opacity:0;transform:translateX(28px);}}
</style>
</head>
<body>

<!-- ════════════════════════════════════
     ROLE SELECTION SCREEN
════════════════════════════════════ -->
<div id="screen-role">
  <div class="role-wrap">
    <div class="role-brand">
      <div class="role-logo">🎓</div>
      <div class="role-title">ISCC <span>System Portal</span></div>
      <div class="role-sub">Ilocos Sur Community College · 3rd Year BSIT · AY 2025–2026</div>
      <div style="margin-top:10px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--dim);letter-spacing:1px;">SELECT YOUR ROLE TO CONTINUE</div>
    </div>

    <div class="role-cards">
      <!-- REGISTRAR -->
      <div class="role-card rc-registrar" onclick="enterSystem('registrar')">
        <span class="rc-icon">🏢</span>
        <div class="rc-name">Registrar</div>
        <div class="rc-desc">Manage student enrollment, subject assignment, and school records.</div>
        <div class="rc-systems">
          <span class="rc-sys">📋 Enrollment System</span>
          <span class="rc-sys">📊 View Attendance</span>
        </div>
        <button class="rc-enter">Enter System →</button>
      </div>

      <!-- TEACHER -->
      <div class="role-card rc-teacher" onclick="enterSystem('teacher')">
        <span class="rc-icon">👨‍🏫</span>
        <div class="rc-name">Teacher</div>
        <div class="rc-desc">Take attendance, track student presence, and monitor subject rates.</div>
        <div class="rc-systems">
          <span class="rc-sys">✅ Attendance System</span>
          <span class="rc-sys">📈 Subject Reports</span>
        </div>
        <button class="rc-enter">Enter System →</button>
      </div>

      <!-- ADMIN -->
      <div class="role-card rc-admin" onclick="enterSystem('admin')">
        <span class="rc-icon">🛡️</span>
        <div class="rc-name">Admin</div>
        <div class="rc-desc">Full access to both systems, data management, and system settings.</div>
        <div class="rc-systems">
          <span class="rc-sys">📋 Enrollment System</span>
          <span class="rc-sys">✅ Attendance System</span>
        </div>
        <button class="rc-enter">Enter System →</button>
      </div>
    </div>

    <div class="role-footer">ISCC · v1.0.0 · Click your role to enter the system</div>
  </div>
</div>

<!-- ════════════════════════════════════
     MAIN APP
════════════════════════════════════ -->
<div id="app">

  <!-- TOP NAV -->
  <nav class="topnav">
    <div class="nav-left">
      <button class="back-btn" onclick="confirmExit()" title="Exit to role selection">←</button>
      <div class="nav-logo">ISC<span>C</span></div>
      <div class="role-chip" id="nav-chip">—</div>
    </div>

    <div class="nav-tabs" id="nav-tabs">
      <button class="nav-tab" id="tab-enroll" onclick="showPanel('enroll')">📋 Enrollment</button>
      <button class="nav-tab" id="tab-attend" onclick="showPanel('attend')">✅ Attendance</button>
    </div>

    <div class="nav-right">
      <div class="stat-pill sp-e"><span class="sdot"></span><span id="nav-enrolled">0</span> Enrolled</div>
      <div class="stat-pill sp-a"><span class="sdot"></span><span id="nav-present">0</span> Present</div>
    </div>
  </nav>

  <!-- ═══ ENROLLMENT PANEL ═══ -->
  <div class="panel" id="panel-enroll">

    <div class="sys-header">
      <div>
        <div class="sys-badge sb-e">System 1 · Enrollment</div>
        <div class="sys-title">Student <span class="te">Enrollment</span> System</div>
      </div>
      <button class="btn btn-e" onclick="openModal('modal-enroll')">✦ Enroll New Student</button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-lbl">Total Students</div><div class="stat-val" id="e-total">0</div><div class="stat-sub">Registered</div></div>
      <div class="stat-card"><div class="stat-lbl">Subjects Available</div><div class="stat-val" id="stat-subj-count">0</div><div class="stat-sub">This semester</div></div>
      <div class="stat-card"><div class="stat-lbl">Max Load</div><div class="stat-val">24</div><div class="stat-sub">Units per student</div></div>
      <div class="stat-card"><div class="stat-lbl">Year Level</div><div class="stat-val" style="font-size:18px;margin-top:4px;">3rd BSIT</div><div class="stat-sub">AY 2025–2026</div></div>
    </div>

    <div class="two-col">
      <!-- Subject List -->
      <div class="card">
        <div class="card-head">
          <div class="card-title"><div class="cico ci-e">📚</div>Subjects This Term</div>
        </div>
        <div style="max-height:480px;overflow-y:auto;" id="subj-list-display">
          <!-- filled by JS -->
        </div>
      </div>

      <!-- Students Table -->
      <div class="card">
        <div class="card-head">
          <div class="card-title"><div class="cico ci-e">🧑‍🎓</div>Enrolled Students</div>
          <span style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--muted);" id="e-count-badge">0 records</span>
        </div>
        <div class="card-body" style="padding:14px 18px 0;">
          <div class="search-bar">
            <input class="search-input" id="e-search" placeholder="🔍 Search by name or ID…" oninput="filterStudents()">
            <select class="filter-sel" id="e-filter" onchange="filterStudents()">
              <option value="">All Blocks</option>
              <option value="3A">Block 3A</option>
              <option value="3B">Block 3B</option>
              <option value="3C">Block 3C</option>
            </select>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Name</th><th>Block</th><th>Subjects</th><th>Units</th><th>Status</th><th></th></tr></thead>
            <tbody id="e-tbody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ ATTENDANCE PANEL ═══ -->
  <div class="panel ap" id="panel-attend">

    <div class="sys-header">
      <div>
        <div class="sys-badge sb-a">System 2 · Attendance</div>
        <div class="sys-title">Student <span class="ta">Attendance</span> System</div>
      </div>
      <button class="btn btn-a" onclick="openModal('modal-history')">📊 Student History</button>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:22px;">
      <div class="stat-card"><div class="stat-lbl">Present</div><div class="stat-val" style="color:var(--green);" id="a-present">0</div><div class="stat-sub">All time</div></div>
      <div class="stat-card"><div class="stat-lbl">Absent</div><div class="stat-val" style="color:var(--red);" id="a-absent">0</div><div class="stat-sub">All time</div></div>
      <div class="stat-card"><div class="stat-lbl">Late</div><div class="stat-val" style="color:var(--amber);" id="a-late">0</div><div class="stat-sub">All time</div></div>
      <div class="stat-card"><div class="stat-lbl">Rate</div><div class="stat-val" style="color:var(--violet);" id="a-rate">0%</div><div class="stat-sub">Attendance</div></div>
    </div>

    <div class="two-col">
      <!-- Left: Manual Attendance Form -->
      <div style="display:flex;flex-direction:column;gap:14px;">
        <div class="card">
          <div class="card-head">
            <div class="card-title"><div class="cico ci-a">✏️</div>Log Attendance</div>
            <span style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--muted);background:rgba(167,139,250,.08);padding:3px 9px;border-radius:100px;border:1px solid rgba(167,139,250,.15);">MANUAL ENTRY</span>
          </div>
          <div class="card-body">

            <!-- Step 1 -->
            <div style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--violet);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
              <div style="width:18px;height:18px;border-radius:50%;background:rgba(167,139,250,.15);border:1px solid rgba(167,139,250,.3);display:flex;align-items:center;justify-content:center;font-size:9px;flex-shrink:0;">1</div>
              Select Subject &amp; Teacher
            </div>
            <div class="form-group">
              <label>Subject</label>
              <select id="a-subject" onchange="onSubjectChange()">
                <option value="">— Select Subject —</option>
              </select>
            </div>
            <div id="a-subj-info" style="display:none;background:rgba(167,139,250,.06);border:1px solid rgba(167,139,250,.15);border-radius:9px;padding:10px 13px;margin-bottom:14px;font-size:11px;color:var(--muted);line-height:1.7;"></div>

            <!-- Step 2 -->
            <div style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--violet);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
              <div style="width:18px;height:18px;border-radius:50%;background:rgba(167,139,250,.15);border:1px solid rgba(167,139,250,.3);display:flex;align-items:center;justify-content:center;font-size:9px;flex-shrink:0;">2</div>
              Enter Student ID
            </div>
            <div class="form-group" style="margin-bottom:8px;">
              <label>Student ID</label>
              <input type="text" id="a-sid" placeholder="e.g. ISCC-2024-001"
                style="font-family:'JetBrains Mono',monospace;font-size:14px;letter-spacing:1px;"
                oninput="clearVerified()"
                onkeydown="if(event.key==='Enter') verifyStudent()">
            </div>
            <button class="btn btn-ghost btn-full" style="margin-bottom:12px;" onclick="verifyStudent()">🔍 Verify Student</button>

            <div id="a-verified" style="display:none;" class="verified-box">
              <div class="v-label">✓ VERIFIED &amp; ENROLLED IN SUBJECT</div>
              <div class="v-name" id="a-v-name"></div>
              <div class="v-info" id="a-v-info"></div>
            </div>
            <input type="hidden" id="a-dbid">

            <!-- Step 3 -->
            <div style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--violet);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
              <div style="width:18px;height:18px;border-radius:50%;background:rgba(167,139,250,.15);border:1px solid rgba(167,139,250,.3);display:flex;align-items:center;justify-content:center;font-size:9px;flex-shrink:0;">3</div>
              Set Status &amp; Date
            </div>
            <div class="form-row" style="margin-bottom:12px;">
              <div class="form-group" style="margin-bottom:0;">
                <label>Status</label>
                <select id="a-status">
                  <option value="Present">✅ Present</option>
                  <option value="Late">⏰ Late</option>
                  <option value="Absent">❌ Absent</option>
                </select>
              </div>
              <div class="form-group" style="margin-bottom:0;">
                <label>Date</label>
                <input type="date" id="a-date">
              </div>
            </div>
            <div class="form-group">
              <label>Remarks (Optional)</label>
              <input type="text" id="a-remarks" placeholder="e.g. Excused absence, sick leave…">
            </div>

            <button class="btn btn-a btn-full" style="font-size:14px;padding:12px;" onclick="logAttendance()">✦ Submit Attendance</button>
          </div>
        </div>

        <!-- Subject Rates -->
        <div class="card">
          <div class="card-head"><div class="card-title"><div class="cico ci-a">📈</div>Subject Rates</div></div>
          <div class="card-body" id="subj-rates"><div class="empty-state"><div class="ei">📊</div><p>No data yet</p></div></div>
        </div>
      </div>

      <!-- Right: Log Table -->
      <div class="card">
        <div class="card-head">
          <div class="card-title"><div class="cico ci-a">📜</div>Attendance Log</div>
          <span style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--muted);" id="a-count-badge">0 records</span>
        </div>
        <div class="card-body" style="padding:14px 18px 0;">
          <div class="search-bar">
            <input class="search-input" id="a-search" placeholder="🔍 Search…" oninput="filterLogs()">
            <select class="filter-sel" id="a-filter" onchange="filterLogs()">
              <option value="">All</option>
              <option value="Present">Present</option>
              <option value="Late">Late</option>
              <option value="Absent">Absent</option>
            </select>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Time</th><th>ID</th><th>Name</th><th>Subject</th><th>Status</th><th></th></tr></thead>
            <tbody id="a-tbody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ MODAL: ENROLL STUDENT ═══ -->
<div class="modal-overlay" id="modal-enroll">
  <div class="modal" style="max-width:580px;">
    <div class="modal-title">📝 Enroll New Student</div>
    <div class="form-row">
      <div class="form-group"><label>Student ID *</label><input type="text" id="f-sid" placeholder="ISCC-2024-XXX"></div>
      <div class="form-group"><label>Section *</label>
        <select id="f-section">
          <option value="3A">Block 3A</option>
          <option value="3B">Block 3B</option>
          <option value="3C">Block 3C</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Last Name *</label><input type="text" id="f-last" placeholder="Dolores"></div>
      <div class="form-group"><label>First Name *</label><input type="text" id="f-first" placeholder="Jay Jr."></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Contact</label><input type="text" id="f-contact" placeholder="09XX-XXX-XXXX"></div>
      <div class="form-group"><label>Email</label><input type="email" id="f-email" placeholder="student@email.com"></div>
    </div>
    <div class="form-group"><label>Address</label><input type="text" id="f-address" placeholder="Brgy., Municipality, Ilocos Sur"></div>
    <div class="sep"></div>
    <div style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;">Select Subjects (Max 24 Units)</div>
    <div class="units-bar">
      <span class="ul">Units Selected</span>
      <div><span class="uv" id="f-units">0</span><span class="um"> / 24</span></div>
    </div>
    <div class="sub-grid" id="f-subjects"></div>
    <div style="display:flex;gap:10px;">
      <button class="btn btn-ghost btn-full" onclick="closeModal('modal-enroll')">Cancel</button>
      <button class="btn btn-e btn-full" onclick="submitEnroll()">✦ Submit Enrollment</button>
    </div>
  </div>
</div>

<!-- ═══ MODAL: VIEW STUDENT ═══ -->
<div class="modal-overlay" id="modal-view">
  <div class="modal" style="max-width:540px;">
    <div class="modal-title">🧑‍🎓 Student Details</div>
    <div id="view-content"></div>
    <div style="margin-top:16px;"><button class="btn btn-ghost btn-full" onclick="closeModal('modal-view')">Close</button></div>
  </div>
</div>

<!-- ═══ MODAL: HISTORY ═══ -->
<div class="modal-overlay" id="modal-history">
  <div class="modal" style="max-width:600px;">
    <div class="modal-title">📊 Student Attendance History</div>
    <div class="form-row" style="margin-bottom:12px;">
      <div class="form-group" style="margin-bottom:0;"><label>Student ID</label>
        <input type="text" id="hist-sid" placeholder="ISCC-2024-XXX" style="font-family:'JetBrains Mono',monospace;">
      </div>
      <div style="display:flex;align-items:flex-end;">
        <button class="btn btn-a btn-full" onclick="loadHistory()">Search</button>
      </div>
    </div>
    <div id="hist-result"></div>
    <div style="margin-top:14px;"><button class="btn btn-ghost btn-full" onclick="closeModal('modal-history')">Close</button></div>
  </div>
</div>

<!-- ═══ EXIT CONFIRM OVERLAY ═══ -->
<div class="exit-overlay" id="exit-overlay">
  <div class="exit-card">
    <div class="exit-icon">⚠️</div>
    <div class="exit-title">Leave the System?</div>
    <div class="exit-sub">You are currently inside the system.<br>Are you sure you want to go back to the role selection screen?</div>
    <div class="exit-btns">
      <button class="btn btn-ghost btn-full" onclick="closeExit()">Stay Here</button>
      <button class="btn btn-red btn-full" onclick="doExit()">Yes, Exit</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div id="toast-wrap"></div>

<script>
// ════════════════════════════════════════
// DATA & STATE
// ════════════════════════════════════════
let SUBJECTS = [];
let STUDENTS = [];
let ATTENDANCE = [];

let currentRole = null;
let selUnits = 0;
let selSubs = new Set();

// ════════════════════════════════════════
// API HELPERS
// ════════════════════════════════════════
async function fetchData(action) {
    const res = await fetch(`index.php?action=${action}`);
    return res.json();
}

async function postData(action, data) {
    const res = await fetch(`index.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return res.json();
}

// ════════════════════════════════════════
// INITIALIZATION
// ════════════════════════════════════════
async function loadAllData() {
    try {
        SUBJECTS = await fetchData('get_subjects');
        STUDENTS = await fetchData('get_students');
        ATTENDANCE = await fetchData('get_attendance');
    } catch (e) {
        console.error("Failed to load data", e);
        showToast("⚠️ Connection Error: Failed to load data", "error");
    }
}

// ════════════════════════════════════════
// ROLE ENTRY
// ════════════════════════════════════════
async function enterSystem(role) {
  currentRole = role;

  // Load data from DB before showing UI
  await loadAllData();

  document.getElementById('screen-role').style.animation = 'fadeOut .4s ease forwards';
  setTimeout(() => {
    document.getElementById('screen-role').style.display = 'none';
    const app = document.getElementById('app');
    app.classList.add('visible');
    setupNavForRole(role);
    showPanel(role === 'teacher' ? 'attend' : 'enroll');
    initSubjectDisplay();
    renderEnrolledTable();
    renderAttendanceTable();
    renderSubjectRates();
    updateStats();
    // Set today's date
    document.getElementById('a-date').value = new Date().toISOString().split('T')[0];
    // Populate subject dropdown
    populateSubjectDropdown();
  }, 380);

  // Block browser back/forward
  window.history.pushState({locked: true}, '', window.location.href);
}

function setupNavForRole(role) {
  const chip = document.getElementById('nav-chip');
  chip.textContent = role.charAt(0).toUpperCase() + role.slice(1);
  chip.className = 'role-chip chip-' + role;

  const tabE = document.getElementById('tab-enroll');
  const tabA = document.getElementById('tab-attend');

  if (role === 'teacher') {
    tabE.disabled = true;
    tabE.style.display = 'none';
    tabA.disabled = false;
  } else if (role === 'registrar') {
    tabA.disabled = false; // registrar can view attendance
    tabE.disabled = false;
  } else { // admin
    tabE.disabled = false;
    tabA.disabled = false;
  }
}

// Block browser popstate (back button)
window.addEventListener('popstate', (e) => {
  if (currentRole) {
    window.history.pushState({locked: true}, '', window.location.href);
    confirmExit();
  }
});

// Block page unload
window.addEventListener('beforeunload', (e) => {
  if (currentRole) {
    e.preventDefault();
    e.returnValue = '';
  }
});

// ════════════════════════════════════════
// NAVIGATION
// ════════════════════════════════════════
function showPanel(name) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + name).classList.add('active');
  document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('ae','aa'));
  const tab = document.getElementById('tab-' + name);
  if (tab) tab.classList.add(name === 'enroll' ? 'ae' : 'aa');
}

// ════════════════════════════════════════
// EXIT CONFIRM
// ════════════════════════════════════════
function confirmExit() {
  document.getElementById('exit-overlay').classList.add('open');
}
function closeExit() {
  document.getElementById('exit-overlay').classList.remove('open');
}
function doExit() {
  currentRole = null;
  closeExit();
  const app = document.getElementById('app');
  app.style.animation = 'fadeOut .35s ease forwards';
  setTimeout(() => {
    app.classList.remove('visible');
    app.style.animation = '';
    const role = document.getElementById('screen-role');
    role.style.display = '';
    role.style.animation = 'fadeIn .4s ease';
  }, 320);
}

// ════════════════════════════════════════
// MODALS
// ════════════════════════════════════════
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m =>
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); }));

// ════════════════════════════════════════
// ENROLLMENT SYSTEM
// ════════════════════════════════════════
function initSubjectDisplay() {
  const list = document.getElementById('subj-list-display');
  // Using a Set to avoid duplicates if multiple sections have same subject code
  const uniqueSubjects = [];
  const seenCodes = new Set();
  SUBJECTS.forEach(s => {
      if (!seenCodes.has(s.code)) {
          uniqueSubjects.push(s);
          seenCodes.add(s.code);
      }
  });

  document.getElementById('stat-subj-count').textContent = uniqueSubjects.length;

  list.innerHTML = uniqueSubjects.map(s => `
    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:10px;">
      <div style="background:rgba(34,211,238,.08);border:1px solid rgba(34,211,238,.14);border-radius:7px;padding:3px 9px;font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--cyan);white-space:nowrap;flex-shrink:0;">${s.code}</div>
      <div>
        <div style="font-size:13px;font-weight:600;">${s.name}</div>
        <div style="font-size:11px;color:var(--muted);margin-top:2px;">${s.units} units · ${s.teacher}</div>
        <div style="font-size:10px;color:var(--dim);margin-top:1px;">🕒 ${s.schedule} · 📍 ${s.room}</div>
      </div>
    </div>`).join('');

  // Build subject grid for modal
  const grid = document.getElementById('f-subjects');
  grid.innerHTML = uniqueSubjects.map(s => `
    <div class="sub-lbl" data-units="${s.units}" data-code="${s.code}" onclick="toggleSub(this)">
      <div class="sub-chk">✓</div>
      <div>
        <div class="sub-code">${s.code}</div>
        <div class="sub-name">${s.name}</div>
        <div class="sub-units">${s.units} units · ${s.teacher}</div>
      </div>
    </div>`).join('');
}

function toggleSub(lbl) {
  const units = parseInt(lbl.dataset.units);
  const code  = lbl.dataset.code;
  if (selSubs.has(code)) {
    selSubs.delete(code); selUnits -= units;
    lbl.classList.remove('checked');
  } else {
    if (selUnits + units > 24) { showToast('❌ Max 24 units!', 'error'); return; }
    selSubs.add(code); selUnits += units;
    lbl.classList.add('checked');
  }
  document.getElementById('f-units').textContent = selUnits;
}

async function submitEnroll() {
  const sid     = document.getElementById('f-sid').value.trim();
  const last    = document.getElementById('f-last').value.trim();
  const first   = document.getElementById('f-first').value.trim();
  const contact = document.getElementById('f-contact').value.trim();
  const email   = document.getElementById('f-email').value.trim();
  const address = document.getElementById('f-address').value.trim();
  const section = document.getElementById('f-section').value;

  if (!sid || !last || !first) { showToast('⚠️ ID, Last Name & First Name required', 'error'); return; }
  if (selSubs.size === 0)      { showToast('⚠️ Select at least one subject', 'error'); return; }
  if (STUDENTS.find(s => s.id === sid)) { showToast(`⚠️ ID "${sid}" already exists`, 'error'); return; }

  const studentData = { id:sid, last, first, contact, email, address, section, subjects:[...selSubs] };

  try {
      const res = await postData('enroll_student', studentData);
      if (res.success) {
          await loadAllData(); // Refresh local data

          // Reset form
          ['f-sid','f-last','f-first','f-contact','f-email','f-address'].forEach(id => document.getElementById(id).value = '');
          selSubs.clear(); selUnits = 0;
          document.getElementById('f-units').textContent = '0';
          document.querySelectorAll('.sub-lbl').forEach(l => l.classList.remove('checked'));
          closeModal('modal-enroll');

          renderEnrolledTable();
          updateStats();
          showToast(`✅ ${first} ${last} enrolled!`, 'success');
      } else {
          showToast('❌ Error saving to database', 'error');
      }
  } catch (e) {
      showToast('❌ Server error', 'error');
  }
}

function renderEnrolledTable() {
  const q = (document.getElementById('e-search')?.value || '').toLowerCase();
  const s = document.getElementById('e-filter')?.value || '';
  const data = STUDENTS.filter(st =>
    `${st.id} ${st.last} ${st.first}`.toLowerCase().includes(q) && (!s || st.section === s));

  const tbody = document.getElementById('e-tbody');
  document.getElementById('e-count-badge').textContent = data.length + ' records';

  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><div class="ei">📋</div><p>No students found</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML = data.map(st => {
    // Calculate units dynamically based on subject array
    const units = st.subjects.reduce((a,c) => {
        const sub = SUBJECTS.find(x=>x.code===c);
        return a + (sub ? sub.units : 0);
    }, 0);

    return `<tr>
      <td><span style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--cyan);">${st.id}</span></td>
      <td><strong>${st.last}</strong>, ${st.first}</td>
      <td><span style="font-family:'JetBrains Mono',monospace;font-size:10px;background:rgba(255,255,255,.05);padding:2px 7px;border-radius:5px;">${st.section}</span></td>
      <td><span style="font-family:'Syne',sans-serif;font-weight:700;">${st.subjects.length}</span></td>
      <td><span style="font-family:'Syne',sans-serif;font-weight:700;color:var(--cyan);">${units}</span></td>
      <td><span class="badge b-enrolled"><span class="bd"></span>Enrolled</span></td>
      <td>
        <button class="btn btn-ghost" style="padding:4px 9px;font-size:10px;" onclick="viewStudent('${st.id}')">View</button>
        <button class="btn btn-red"   style="padding:4px 9px;font-size:10px;margin-left:4px;" onclick="deleteStudent('${st.id}')">Del</button>
      </td>
    </tr>`;
  }).join('');
}

function viewStudent(id) {
  const st = STUDENTS.find(s => s.id === id);
  if (!st) return;
  const units = st.subjects.reduce((a,c) => {
      const sub = SUBJECTS.find(x=>x.code===c);
      return a + (sub ? sub.units : 0);
  }, 0);

  document.getElementById('view-content').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
      <div><div style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--dim);letter-spacing:1.5px;text-transform:uppercase;">STUDENT ID</div><div style="font-family:'JetBrains Mono',monospace;font-size:14px;color:var(--cyan);">${st.id}</div></div>
      <div><div style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--dim);letter-spacing:1.5px;text-transform:uppercase;">NAME</div><div style="font-size:14px;font-weight:700;">${st.last}, ${st.first}</div></div>
      <div><div style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--dim);letter-spacing:1.5px;text-transform:uppercase;">SECTION</div><div>${st.section}</div></div>
      <div><div style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--dim);letter-spacing:1.5px;text-transform:uppercase;">CONTACT</div><div>${st.contact||'—'}</div></div>
      <div style="grid-column:1/-1;"><div style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--dim);letter-spacing:1.5px;text-transform:uppercase;">ADDRESS</div><div>${st.address||'—'}</div></div>
      <div><div style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--dim);letter-spacing:1.5px;text-transform:uppercase;">TOTAL UNITS</div><div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:var(--cyan);">${units}</div></div>
    </div>
    <div style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:10px;">Enrolled Subjects</div>
    <div class="table-wrap"><table><thead><tr><th>Code</th><th>Title</th><th>Units</th><th>Teacher</th><th>Schedule</th></tr></thead>
    <tbody>${st.subjects.map(c => {
        const sub=SUBJECTS.find(x=>x.code===c);
        if(!sub) return '';
        return `<tr>
      <td><span style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--cyan);">${c}</span></td>
      <td style="font-size:12px;">${sub.name}</td>
      <td style="font-family:'JetBrains Mono',monospace;font-size:11px;">${sub.units}</td>
      <td style="font-size:11px;color:var(--muted);">${sub.teacher}</td>
      <td style="font-size:10px;color:var(--dim);">${sub.schedule}</td>
    </tr>`; }).join('')}
    </tbody></table></div>`;
  openModal('modal-view');
}

async function deleteStudent(id) {
  const st = STUDENTS.find(s => s.id === id);
  if (!confirm(`Delete ${st.first} ${st.last}? All their records will be removed.`)) return;

  try {
      const res = await postData('delete_student', { id });
      if (res.success) {
          await loadAllData();
          renderEnrolledTable(); updateStats();
          showToast('🗑️ Student deleted', 'success');
      } else {
          showToast('❌ Error deleting student', 'error');
      }
  } catch (e) {
      showToast('❌ Server error', 'error');
  }
}

function filterStudents() { renderEnrolledTable(); }

// ════════════════════════════════════════
// ATTENDANCE SYSTEM
// ════════════════════════════════════════
function populateSubjectDropdown() {
  const sel = document.getElementById('a-subject');
  const uniqueSubjects = [];
  const seenCodes = new Set();
  SUBJECTS.forEach(s => {
      if (!seenCodes.has(s.code)) {
          uniqueSubjects.push(s);
          seenCodes.add(s.code);
      }
  });

  sel.innerHTML = '<option value="">— Select Subject —</option>' +
    uniqueSubjects.map(s => `<option value="${s.code}">${s.code} — ${s.name}</option>`).join('');
}

function onSubjectChange() {
  const code = document.getElementById('a-subject').value;
  const info = document.getElementById('a-subj-info');
  if (!code) { info.style.display='none'; return; }
  const s = SUBJECTS.find(x => x.code === code);
  info.innerHTML = `
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
      <span>👩‍🏫 <strong style="color:var(--text);">${s.teacher}</strong></span>
      <span>🕒 ${s.schedule}</span>
      <span>📍 ${s.room}</span>
    </div>`;
  info.style.display = 'block';
  clearVerified();
}

function clearVerified() {
  document.getElementById('a-verified').style.display = 'none';
  document.getElementById('a-dbid').value = '';
}

function verifyStudent() {
  const sid  = document.getElementById('a-sid').value.trim();
  const code = document.getElementById('a-subject').value;
  if (!sid)  { showToast('⚠️ Enter a Student ID', 'error'); return; }
  if (!code) { showToast('⚠️ Select a subject first', 'error'); return; }

  const st = STUDENTS.find(s => s.id === sid);
  if (!st)                          { clearVerified(); showToast('❌ Student ID not found', 'error'); return; }
  if (!st.subjects.includes(code))  { clearVerified(); showToast(`❌ ${st.first} is NOT enrolled in ${code}`, 'error'); return; }

  document.getElementById('a-v-name').textContent = st.last + ', ' + st.first;
  document.getElementById('a-v-info').textContent = 'Block ' + st.section + ' · ' + st.id;
  document.getElementById('a-verified').style.display = 'block';
  document.getElementById('a-dbid').value = sid;
  showToast('✅ Student verified!', 'success');
}

async function logAttendance() {
  const dbid   = document.getElementById('a-dbid').value;
  const code   = document.getElementById('a-subject').value;
  const status = document.getElementById('a-status').value;
  const date   = document.getElementById('a-date').value;
  const rem    = document.getElementById('a-remarks').value.trim();

  if (!dbid) { showToast('⚠️ Verify the student ID first', 'error'); return; }
  if (!code) { showToast('⚠️ Select a subject', 'error'); return; }

  const dup = ATTENDANCE.find(a => a.sid === dbid && a.code === code && a.date === date);
  if (dup) { showToast('⚠️ Already logged today for this subject', 'error'); return; }

  const st  = STUDENTS.find(s => s.id === dbid);
  const now = new Date().toLocaleTimeString('en-PH', {hour:'2-digit', minute:'2-digit'});

  const logData = { sid:dbid, code, status, date, remarks:rem, time:now };

  try {
      const res = await postData('log_attendance', logData);
      if (res.success) {
          await loadAllData();

          clearVerified();
          document.getElementById('a-sid').value = '';
          document.getElementById('a-remarks').value = '';

          renderAttendanceTable();
          renderSubjectRates();
          updateStats();

          const icon = status==='Present'?'✅':status==='Late'?'⏰':'❌';
          showToast(`${icon} ${st.first} marked ${status} in ${code}`, 'success');
      } else {
          showToast('❌ ' + (res.message || 'Error logging attendance'), 'error');
      }
  } catch (e) {
      showToast('❌ Server error', 'error');
  }
}

function renderAttendanceTable() {
  const q = (document.getElementById('a-search')?.value || '').toLowerCase();
  const f = document.getElementById('a-filter')?.value || '';
  const data = ATTENDANCE.filter(r =>
    `${r.sid} ${r.name} ${r.code}`.toLowerCase().includes(q) && (!f || r.status === f));

  const tbody = document.getElementById('a-tbody');
  document.getElementById('a-count-badge').textContent = data.length + ' records';

  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><div class="ei">✅</div><p>No records found</p></div></td></tr>`;
    return;
  }
  const bc = s => s==='Present'?'b-present':s==='Absent'?'b-absent':'b-late';
  tbody.innerHTML = data.map((r,i) => `<tr>
    <td style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--dim);">${r.time}</td>
    <td><span style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--violet);">${r.sid}</span></td>
    <td><strong>${r.name}</strong></td>
    <td><span style="background:rgba(167,139,250,.1);color:var(--violet);border:1px solid rgba(167,139,250,.2);padding:2px 7px;border-radius:5px;font-family:'JetBrains Mono',monospace;font-size:9px;">${r.code}</span></td>
    <td><span class="badge ${bc(r.status)}"><span class="bd"></span>${r.status}</span></td>
    <td><button class="btn btn-red" style="padding:3px 8px;font-size:10px;" onclick="deleteLog(${r.id})">Del</button></td>
  </tr>`).join('');
}

async function deleteLog(id) {
  if (!confirm('Delete this attendance record?')) return;

  try {
      const res = await postData('delete_attendance', { id });
      if (res.success) {
          await loadAllData();
          renderAttendanceTable(); renderSubjectRates(); updateStats();
          showToast('🗑️ Record deleted', 'success');
      } else {
          showToast('❌ Error deleting record', 'error');
      }
  } catch (e) {
      showToast('❌ Server error', 'error');
  }
}

function filterLogs() { renderAttendanceTable(); }

function renderSubjectRates() {
  const container = document.getElementById('subj-rates');
  const codes = [...new Set(ATTENDANCE.map(r => r.code))];
  if (!codes.length) {
    container.innerHTML = '<div class="empty-state"><div class="ei">📈</div><p>No data yet</p></div>';
    return;
  }
  container.innerHTML = codes.map(code => {
    const recs = ATTENDANCE.filter(r => r.code === code);
    const pres = recs.filter(r => r.status === 'Present').length;
    const rate = Math.round((pres / recs.length) * 100);
    const col  = rate>=80?'var(--green)':rate>=60?'var(--amber)':'var(--red)';
    return `<div style="margin-bottom:13px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
        <span style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--violet);">${code}</span>
        <span style="font-family:'Syne',sans-serif;font-size:12px;font-weight:700;color:${col};">${rate}%</span>
      </div>
      <div class="prog-bar"><div class="prog-fill" style="width:${rate}%;background:${col};"></div></div>
      <div style="font-size:10px;color:var(--dim);margin-top:3px;">${pres}/${recs.length} present</div>
    </div>`;
  }).join('');
}

function loadHistory() {
  const sid = document.getElementById('hist-sid').value.trim();
  if (!sid) { showToast('⚠️ Enter a student ID', 'error'); return; }
  const recs = ATTENDANCE.filter(r => r.sid === sid);
  const result = document.getElementById('hist-result');
  if (!recs.length) {
    result.innerHTML = '<div class="empty-state"><div class="ei">📋</div><p>No records for ' + sid + '</p></div>';
    return;
  }
  const bc = s => s==='Present'?'b-present':s==='Absent'?'b-absent':'b-late';
  result.innerHTML = `<div class="table-wrap"><table>
    <thead><tr><th>Date</th><th>Time</th><th>Subject</th><th>Status</th><th>Remarks</th></tr></thead>
    <tbody>${recs.map(r=>`<tr>
      <td style="font-family:'JetBrains Mono',monospace;font-size:10px;">${r.date}</td>
      <td style="font-family:'JetBrains Mono',monospace;font-size:10px;">${r.time}</td>
      <td><span style="background:rgba(167,139,250,.1);color:var(--violet);border:1px solid rgba(167,139,250,.2);padding:2px 7px;border-radius:5px;font-family:'JetBrains Mono',monospace;font-size:9px;">${r.code}</span></td>
      <td><span class="badge ${bc(r.status)}"><span class="bd"></span>${r.status}</span></td>
      <td style="font-size:11px;color:var(--muted);">${r.remarks||'—'}</td>
    </tr>`).join('')}</tbody></table></div>`;
}

// ════════════════════════════════════════
// STATS
// ════════════════════════════════════════
function updateStats() {
  const total  = STUDENTS.length;
  const pres   = ATTENDANCE.filter(r=>r.status==='Present').length;
  const abs    = ATTENDANCE.filter(r=>r.status==='Absent').length;
  const late   = ATTENDANCE.filter(r=>r.status==='Late').length;
  const total2 = ATTENDANCE.length;
  const rate   = total2 > 0 ? Math.round((pres/total2)*100) : 0;

  document.getElementById('e-total').textContent   = total;
  document.getElementById('a-present').textContent = pres;
  document.getElementById('a-absent').textContent  = abs;
  document.getElementById('a-late').textContent    = late;
  document.getElementById('a-rate').textContent    = rate + '%';
  document.getElementById('nav-enrolled').textContent = total;
  document.getElementById('nav-present').textContent  = pres;
}

// ════════════════════════════════════════
// TOAST
// ════════════════════════════════════════
function showToast(msg, type='info') {
  const w = document.getElementById('toast-wrap');
  const t = document.createElement('div');
  t.className = `toast t-${type}`;
  t.innerHTML = msg;
  w.appendChild(t);
  setTimeout(() => t.remove(), 3100);
}
</script>
</body>
</html>
