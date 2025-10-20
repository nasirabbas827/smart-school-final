<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Student") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$student_id = $_SESSION["user_id"];

// Fetch student info
$stmt = $conn->prepare("SELECT u.first_name, u.last_name, u.class_id, c.class_name 
                        FROM users u
                        JOIN classes c ON u.class_id = c.class_id
                        WHERE u.user_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $student_name = $student['first_name'] . " " . $student['last_name'];
    $class_id = $student['class_id'];
    $class_name = $student['class_name'];
} else {
    $student_name = "Student";
    $class_id = 0;
    $class_name = "N/A";
}
$stmt->close();

// --- NEW DATA FETCHES FOR COMPREHENSIVE DASHBOARD ---

// 1. Assignment Stats
$assignmentStats = [
    'total_assigned' => 0,
    'total_submitted' => 0,
    'pending_submission' => 0
];
$sqlAssignmentStats = "SELECT 
    (SELECT COUNT(assignment_id) FROM assignments WHERE class_id = ?) AS total_assigned,
    (SELECT COUNT(submission_id) FROM assignment_submissions WHERE student_id = ?) AS total_submitted";
$stmt = $conn->prepare($sqlAssignmentStats);
$stmt->bind_param("ii", $class_id, $student_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$assignmentStats['total_assigned'] = $result['total_assigned'];
$assignmentStats['total_submitted'] = $result['total_submitted'];
$assignmentStats['pending_submission'] = $assignmentStats['total_assigned'] - $assignmentStats['total_submitted'];
$stmt->close();

// 2. Upcoming Assignments/Homework (Top 5 for "To Do" list)
$toDoList = [];
$sqlToDo = "
(SELECT 
    'Assignment' AS type, 
    a.title, 
    a.due_date, 
    s.subject_name
FROM assignments a
JOIN subjects s ON a.subject_id = s.subject_id
WHERE a.class_id = ? 
    AND a.due_date >= CURDATE()
    AND a.assignment_id NOT IN (SELECT assignment_id FROM assignment_submissions WHERE student_id = ?)
)
UNION ALL
(SELECT 
    'Homework' AS type, 
    h.title, 
    h.due_date, 
    s.subject_name
FROM homework h
JOIN subjects s ON h.subject_id = s.subject_id
WHERE h.class_id = ? 
    AND h.due_date >= CURDATE()
)
ORDER BY due_date ASC
LIMIT 5";
$stmt = $conn->prepare($sqlToDo);
$stmt->bind_param("iii", $class_id, $student_id, $class_id);
$stmt->execute();
$resultToDo = $stmt->get_result();
$toDoList = $resultToDo->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 3. Current Timetable (Today's sessions only)
$today = date('D'); // e.g., 'Mon', 'Tue'
$todaySessions = [];
$sqlTodayTimetable = "SELECT t.start_time, s.subject_name, u.first_name AS teacher_first
                      FROM timetables t 
                      JOIN subjects s ON t.subject_id = s.subject_id
                      JOIN users u ON t.teacher_id = u.user_id
                      WHERE t.class_id = ? AND t.day_of_week = ?
                      ORDER BY t.start_time ASC";
$stmt = $conn->prepare($sqlTodayTimetable);
$stmt->bind_param("is", $class_id, $today);
$stmt->execute();
$resultTodayTimetable = $stmt->get_result();
$todaySessions = $resultTodayTimetable->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 4. Pending Fee Vouchers Count
$totalPendingFees = 0;
$sqlPendingFees = "SELECT COUNT(*) AS total_pending FROM fee_vouchers WHERE student_id = ? AND status = 'Pending'";
$stmt = $conn->prepare($sqlPendingFees);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$totalPendingFees = $result['total_pending'];
$stmt->close();

// 5. Total Absences
$totalAbsences = 0;
$sqlTotalAbsences = "SELECT COUNT(*) AS total_absent FROM attendance WHERE student_id = ? AND status = 'Absent'";
$stmt = $conn->prepare($sqlTotalAbsences);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$totalAbsences = $result['total_absent'];
$stmt->close();

// 6. Latest Announcements (Top 3)
$announcements = [];
$annStmt = $conn->prepare("SELECT title, description, created_at FROM announcements ORDER BY created_at DESC LIMIT 3");
$annStmt->execute();
$annResult = $annStmt->get_result();
while ($row = $annResult->fetch_assoc()) {
    $announcements[] = $row;
}
$annStmt->close();

// --- END NEW DATA FETCHES ---

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />
<style>
    body { background-color: #f0f4f8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin-left: 280px; }
    .container { padding: 2rem 1rem; }
    h2, h4 { color: #2c3e50; margin-bottom: 1rem; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem; }
    .stat-card { border-radius: 12px; transition: transform 0.3s, box-shadow 0.3s; border: none; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); }
    .list-group-item { border-radius: 8px; margin-bottom: 5px; }
    .bg-custom-blue { background-color: #3498db !important; }
    .bg-custom-green { background-color: #2ecc71 !important; }
    .bg-custom-yellow { background-color: #f39c12 !important; }
    .bg-custom-red { background-color: #e74c3c !important; }
    @media (max-width: 992px) { body { margin-left: 0; } }
</style>
</head>
<body>
<?php include('student_sidebar.php'); ?>

<div class="container my-4">
    <div class="p-4 mb-4 bg-white rounded shadow-sm border-start border-5 border-primary">
        <h2 class="mb-0 border-0 p-0"><i class="fas fa-user-graduate me-2 text-primary"></i>Hello, <?= htmlspecialchars($student_name) ?>!</h2>
        <p class="text-muted lead mb-0">You are currently in **Class: <?= htmlspecialchars($class_name) ?>**.</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card text-center bg-custom-blue text-white shadow">
                <div class="card-body">
                    <i class="fas fa-clipboard-list fa-3x mb-2"></i>
                    <h5 class="card-title">Total Assignments</h5>
                    <p class="display-4 m-0"><?= $assignmentStats['total_assigned'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card text-center bg-custom-green text-white shadow">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-3x mb-2"></i>
                    <h5 class="card-title">Pending Tasks (A/H)</h5>
                    <p class="display-4 m-0"><?= count($toDoList) ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card text-center bg-custom-yellow text-white shadow">
                <div class="card-body">
                    <i class="fas fa-money-bill-wave fa-3x mb-2"></i>
                    <h5 class="card-title">Pending Fee Vouchers</h5>
                    <p class="display-4 m-0"><?= $totalPendingFees ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card text-center bg-custom-red text-white shadow">
                <div class="card-body">
                    <i class="fas fa-calendar-times fa-3x mb-2"></i>
                    <h5 class="card-title">Total Absences</h5>
                    <p class="display-4 m-0"><?= $totalAbsences ?></p>
                </div>
            </div>
        </div>
    </div>

    ---

    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <h4><i class="fas fa-sun me-2"></i>Today's Timetable (<?= htmlspecialchars(date('D, M d')) ?>)</h4>
            <div class="card shadow-sm p-3">
                <ul class="list-group list-group-flush">
                    <?php if ($todaySessions): ?>
                        <?php foreach ($todaySessions as $session): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-primary"><?= htmlspecialchars($session['subject_name']) ?></strong>
                                    <br><small class="text-muted">Teacher: <?= htmlspecialchars($session['teacher_first']) ?></small>
                                </div>
                                <span class="badge bg-secondary"><?= htmlspecialchars(date("g:i a", strtotime($session['start_time']))) ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted text-center">No classes scheduled for today (<?= htmlspecialchars($today) ?>).</li>
                    <?php endif; ?>
                </ul>
                <a href="view_timetable.php" class="btn btn-outline-primary btn-sm mt-3">View Full Timetable</a>
            </div>
        </div>

        <div class="col-lg-6">
            <h4><i class="fas fa-tasks me-2"></i>Assignments & Homework Due</h4>
            <div class="card shadow-sm p-3">
                <ul class="list-group list-group-flush">
                    <?php if ($toDoList): ?>
                        <?php foreach ($toDoList as $task): ?>
                            <?php 
                                $badge_color = ($task['type'] == 'Assignment') ? 'danger' : 'warning';
                                $link_file = ($task['type'] == 'Assignment') ? 'view_assignments.php' : 'view_homework.php';
                            ?>
                            <a href="<?= $link_file ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($task['title']) ?></strong>
                                    <br><small class="text-muted">Subject: <?= htmlspecialchars($task['subject_name']) ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?= $badge_color ?>"><?= htmlspecialchars($task['type']) ?></span>
                                    <br><small>Due: <?= htmlspecialchars($task['due_date']) ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted text-center list-group-item-success">🎉 All caught up! No tasks due soon.</li>
                    <?php endif; ?>
                </ul>
                <a href="view_assignments.php" class="btn btn-outline-secondary btn-sm mt-3">View All Tasks</a>
            </div>
        </div>
    </div>

    ---

    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <h4><i class="fas fa-bullhorn me-2"></i>Latest School Announcements</h4>
            <div class="card shadow-sm p-3">
                <ul class="list-group list-group-flush">
                    <?php if ($announcements): ?>
                        <?php foreach ($announcements as $ann): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?= htmlspecialchars($ann['title']) ?></strong>
                                    <p class="mb-0 text-truncate" style="max-width: 90%;"><small><?= nl2br(htmlspecialchars($ann['description'])) ?></small></p>
                                </div>
                                <small class="text-muted text-nowrap"><?= htmlspecialchars(date('M d', strtotime($ann['created_at']))) ?></small>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-muted text-center">No recent announcements.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="col-lg-6">
            <h4><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
            <div class="list-group">
                <a href="student_fee_portal.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-warning">
                    <i class="fas fa-money-bill-wave me-2"></i> Pay/View Fees
                    <span class="badge bg-warning text-dark rounded-pill"><?= $totalPendingFees ?> Pending</span>
                </a>
                <a href="view_grades.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-info">
                    <i class="fas fa-chart-line me-2"></i> View Latest Grades
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="student_leave.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-danger">
                    <i class="fas fa-calendar-times me-2"></i> Apply for Leave
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="student_feedback.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-primary">
                    <i class="fas fa-comment-dots me-2"></i> Send Feedback
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>