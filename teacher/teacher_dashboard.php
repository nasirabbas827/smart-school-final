<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$teacher_id = $_SESSION["user_id"];

// --- 1. Fetch Teacher Info (Unchanged) ---
$sql = "SELECT first_name, email, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stmt->bind_result($name, $email, $created_at);
$stmt->fetch();
$stmt->close();

// --- 2. Fetch Classes, Subjects, and Timetable (Unchanged, for context) ---
// Classes assigned to teacher
$sqlClasses = "SELECT c.class_id, c.class_name 
               FROM classes c
               JOIN timetables t ON c.class_id = t.class_id
               WHERE t.teacher_id = ?
               GROUP BY c.class_id, c.class_name
               ORDER BY c.class_name";
$stmt = $conn->prepare($sqlClasses);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$resultClasses = $stmt->get_result();
$classes = $resultClasses->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$totalClasses = count($classes);
$class_ids = array_column($classes, 'class_id'); // Get all class IDs for IN clause later

// Subjects assigned to teacher
$sqlSubjects = "SELECT s.subject_id, s.subject_name 
                 FROM subjects s
                 JOIN timetables t ON s.subject_id = t.subject_id
                 WHERE t.teacher_id = ?
                 GROUP BY s.subject_id, s.subject_name
                 ORDER BY s.subject_name";
$stmt = $conn->prepare($sqlSubjects);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$resultSubjects = $stmt->get_result();
$subjects = $resultSubjects->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$totalSubjects = count($subjects);

// Timetable entries
$sqlTimetable = "SELECT day_of_week, start_time, end_time, c.class_name, s.subject_name
                 FROM timetables t
                 JOIN classes c ON t.class_id = c.class_id
                 JOIN subjects s ON t.subject_id = s.subject_id
                 WHERE t.teacher_id = ?
                 ORDER BY FIELD(day_of_week, 'Mon','Tue','Wed','Thu','Fri','Sat'), start_time";
$stmt = $conn->prepare($sqlTimetable);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$resultTimetable = $stmt->get_result();
$timetable = $resultTimetable->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$totalSessions = count($timetable);


// --- 3. New Data Fetches for Comprehensive Dashboard ---

// A. Count of Assignments created by the teacher
$sqlTotalAssignments = "SELECT COUNT(*) AS total_assignments FROM assignments WHERE teacher_id = ?";
$stmt = $conn->prepare($sqlTotalAssignments);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$totalAssignments = $result['total_assignments'];
$stmt->close();

// B. Count of Pending Assignment Submissions (Submissions without a grade)
$sqlPendingSubmissions = "SELECT COUNT(asu.submission_id) AS total_pending
                          FROM assignment_submissions asu
                          JOIN assignments a ON asu.assignment_id = a.assignment_id
                          WHERE a.teacher_id = ? AND asu.grade IS NULL";
$stmt = $conn->prepare($sqlPendingSubmissions);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$totalPendingSubmissions = $result['total_pending'];
$stmt->close();

// C. Latest Assignments (Top 5)
$sqlLatestAssignments = "SELECT a.title, a.due_date, c.class_name, s.subject_name
                         FROM assignments a
                         JOIN classes c ON a.class_id = c.class_id
                         JOIN subjects s ON a.subject_id = s.subject_id
                         WHERE a.teacher_id = ?
                         ORDER BY a.created_at DESC
                         LIMIT 5";
$stmt = $conn->prepare($sqlLatestAssignments);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$resultLatestAssignments = $stmt->get_result();
$latestAssignments = $resultLatestAssignments->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// D. Pending Leave Applications (for the teacher's classes)
$pendingLeave = [];
if (!empty($class_ids)) {
    $placeholders = implode(',', array_fill(0, count($class_ids), '?'));
    $sqlPendingLeave = "SELECT la.start_date, la.end_date, la.reason, u.first_name, c.class_name
                        FROM leave_applications la
                        JOIN users u ON la.student_id = u.user_id
                        JOIN classes c ON la.class_id = c.class_id
                        WHERE la.class_id IN ($placeholders) AND la.status = 'Pending'
                        ORDER BY la.applied_at ASC
                        LIMIT 5";
    $stmt = $conn->prepare($sqlPendingLeave);
    $types = str_repeat('i', count($class_ids));
    $stmt->bind_param($types, ...$class_ids);
    $stmt->execute();
    $resultPendingLeave = $stmt->get_result();
    $pendingLeave = $resultPendingLeave->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// E. Unanswered Feedback (Assuming teachers handle feedback)
$sqlUnansweredFeedback = "SELECT COUNT(*) AS total_feedback FROM feedback WHERE reply IS NULL";
$result = $conn->query($sqlUnansweredFeedback)->fetch_assoc();
$totalUnansweredFeedback = $result['total_feedback'];

// F. Latest Announcements
$sqlLatestAnnouncements = "SELECT title, created_at FROM announcements ORDER BY created_at DESC LIMIT 3";
$resultLatestAnnouncements = $conn->query($sqlLatestAnnouncements);
$latestAnnouncements = $resultLatestAnnouncements->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Teacher Dashboard - Comprehensive</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin-left: 280px; }
        .container { padding: 2rem 1rem; }
        h2, h4 { color: #2c3e50; margin-bottom: 1.5rem; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem; }
        .card { border-radius: 12px; transition: all 0.3s; border: none; }
        .card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        .stat-card .card-header { border-radius: 12px 12px 0 0; font-weight: bold; }
        .list-group-item { border-radius: 8px; margin-bottom: 5px; }
        .list-group-item:last-child { margin-bottom: 0; }
        footer { background: #ecf0f1; padding: 2rem 1rem; text-align: center; margin-top: 2rem; }
        /* Style for the sidebar - necessary for the layout */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1000;
            width: 280px;
            background-color: #34495e; /* Darker background */
            color: #ecf0f1;
            padding-top: 1rem;
        }
        .sidebar-brand {
            padding: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: #ecf0f1;
        }
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 1rem 0;
        }
        .sidebar .nav-link {
            color: #bdc3c7;
            border-radius: 8px;
            transition: background-color 0.2s, color 0.2s;
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #2c3e50; /* Slightly lighter dark for hover/active */
            color: #fff;
        }
        .sidebar .nav-link.active {
            font-weight: bold;
            background-color: #3498db; /* Primary color for active link */
        }
        .section-header {
            font-size: 0.8rem;
            color: #95a5a6;
            padding: 0.5rem 0.5rem 0.5rem 1rem;
            font-weight: bold;
            margin-top: 1rem;
        }
        @media (max-width: 992px) { 
            body { margin-left: 0; } 
            .sidebar { display: none; } /* Hide fixed sidebar on mobile */
        }
    </style>
</head>
<body>
<nav class="sidebar d-flex flex-column flex-shrink-0">
    <div class="sidebar-brand d-flex align-items-center justify-content-center mb-0">
        <i class="fas fa-chalkboard-teacher me-2"></i>
        <span>Teacher Portal</span>
    </div>
    
    <div class="p-3">
        <hr class="sidebar-divider">
        
        <ul class="nav nav-pills flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link active" href="teacher_dashboard.php" aria-current="page">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
        </ul>

        <div class="section-header">
            <i class="fas fa-clipboard-list me-1"></i>MANAGEMENT
        </div>
        <ul class="nav nav-pills flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link" href="mark_attendance.php">
                    <i class="fas fa-calendar-check me-2"></i> Mark Attendance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="announcements.php">
                    <i class="fas fa-bullhorn me-2"></i> Announcements
                </a>
            </li>
        </ul>

        <div class="section-header">
            <i class="fas fa-book-open me-1"></i>ACADEMIC
        </div>
        <ul class="nav nav-pills flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link" href="add_syllabus.php">
                    <i class="fas fa-file-alt me-2"></i> Manage Syllabus
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="add_homework.php">
                    <i class="fas fa-tasks me-2"></i> Manage Homework
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="add_assignment.php">
                    <i class="fas fa-clipboard-list me-2"></i> Manage Assignments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="add_grades.php">
                    <i class="fas fa-chart-line me-2"></i> Report & Grades
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="add_quiz.php">
                    <i class="fas fa-question-circle me-2"></i> Create Quiz
                </a>
            </li>
        </ul>

        <div class="section-header">
            <i class="fas fa-comments me-1"></i>COMMUNICATION
        </div>
        <ul class="nav nav-pills flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link" href="teacher_feedback.php">
                    <i class="fas fa-comment-dots me-2"></i> Student Feedback
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="teacher_leave.php">
                    <i class="fas fa-calendar-times me-2"></i> Leave Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="class_messages.php">
                    <i class="fas fa-comments me-2"></i> Class Messages
                </a>
            </li>
        </ul>

        <hr class="sidebar-divider">
        
        <div class="mt-auto">
            <a class="nav-link" href="../logout.php"> <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container my-4">
    <div class="p-4 mb-4 bg-white rounded shadow-sm border-start border-5 border-primary">
        <h2 class="mb-0 border-0 p-0"><i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Welcome back, <?= htmlspecialchars($name) ?>!</h2>
        <p class="text-muted lead">Your comprehensive overview of classes, assignments, and pending tasks.</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card text-center bg-primary text-white shadow">
                <div class="card-body">
                    <i class="fas fa-school fa-3x mb-2"></i>
                    <h5 class="card-title">Classes Assigned</h5>
                    <p class="display-4 m-0"><?= $totalClasses ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card text-center bg-info text-white shadow">
                <div class="card-body">
                    <i class="fas fa-file-alt fa-3x mb-2"></i>
                    <h5 class="card-title">Total Assignments</h5>
                    <p class="display-4 m-0"><?= $totalAssignments ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card text-center bg-warning text-dark shadow">
                <div class="card-body">
                    <i class="fas fa-check-double fa-3x mb-2"></i>
                    <h5 class="card-title">Pending Grading</h5>
                    <p class="display-4 m-0"><?= $totalPendingSubmissions ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card stat-card text-center bg-danger text-white shadow">
                <div class="card-body">
                    <i class="fas fa-envelope-open-text fa-3x mb-2"></i>
                    <h5 class="card-title">Pending Leave/Feedback</h5>
                    <p class="display-4 m-0"><?= count($pendingLeave) + $totalUnansweredFeedback ?></p>
                </div>
            </div>
        </div>
    </div>

    ---

    <div class="row g-4 mb-5">
        <div class="col-lg-7">
            <h4><i class="fas fa-calendar-alt me-2"></i>Upcoming Sessions Today/Timetable</h4>
            <div class="card shadow-sm p-3">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Class</th>
                            <th>Subject</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($timetable, 0, 8) as $row): // Limiting to top 8 ?>
                            <tr>
                                <td><?= htmlspecialchars($row['day_of_week']) ?></td>
                                <td><?= htmlspecialchars(date("g:i a", strtotime($row['start_time']))) ?> - <?= htmlspecialchars(date("g:i a", strtotime($row['end_time']))) ?></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($row['class_name']) ?></span></td>
                                <td><?= htmlspecialchars($row['subject_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($timetable)): ?>
                            <tr><td colspan="4" class="text-muted text-center">No sessions scheduled.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-5">
            <h4><i class="fas fa-exclamation-circle me-2"></i>Quick Action Center</h4>
            <div class="list-group">
                <a href="add_assignment.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-warning">
                    Pending Grading
                    <span class="badge bg-warning text-dark rounded-pill"><?= $totalPendingSubmissions ?></span>
                </a>
                <a href="teacher_leave.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-danger">
                    New Leave Applications
                    <span class="badge bg-danger rounded-pill"><?= count($pendingLeave) ?></span>
                </a>
                <a href="teacher_feedback.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-info">
                    Unanswered Student Feedback
                    <span class="badge bg-info rounded-pill"><?= $totalUnansweredFeedback ?></span>
                </a>
                <a href="mark_attendance.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center list-group-item-success">
                    Mark Attendance Today
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    ---

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <h4><i class="fas fa-upload me-2"></i>Recently Posted Assignments</h4>
            <div class="card shadow-sm p-3">
                <ul class="list-group list-group-flush">
                    <?php foreach ($latestAssignments as $assignment): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($assignment['title']) ?></strong>
                                <br><small class="text-muted">Class: <?= htmlspecialchars($assignment['class_name']) ?> | Subject: <?= htmlspecialchars($assignment['subject_name']) ?></small>
                            </div>
                            <span class="badge bg-secondary">Due: <?= htmlspecialchars($assignment['due_date']) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($latestAssignments)): ?>
                        <li class="list-group-item text-muted text-center">No recent assignments posted.</li>
                    <?php endif; ?>
                </ul>
                <a href="add_assignment.php" class="btn btn-outline-secondary btn-sm mt-3">Manage Assignments</a>
            </div>
        </div>

        <div class="col-md-6">
            <h4><i class="fas fa-plane-departure me-2"></i>Leave Applications to Review</h4>
            <div class="card shadow-sm p-3">
                <ul class="list-group list-group-flush">
                    <?php foreach ($pendingLeave as $leave): ?>
                        <li class="list-group-item list-group-item-danger d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($leave['first_name']) ?></strong> from <?= htmlspecialchars($leave['class_name']) ?>
                                <br><small>Reason: <?= substr(htmlspecialchars($leave['reason']), 0, 40) ?>...</small>
                            </div>
                            <span class="badge bg-danger">Dates: <?= htmlspecialchars($leave['start_date']) ?> to <?= htmlspecialchars($leave['end_date']) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($pendingLeave)): ?>
                        <li class="list-group-item text-muted text-center list-group-item-success">No pending leave applications.</li>
                    <?php endif; ?>
                </ul>
                <a href="teacher_leave.php" class="btn btn-outline-danger btn-sm mt-3">Review All Leaves</a>
            </div>
        </div>
    </div>

    ---

    <div class="row g-4 mb-5">
        <div class="col-12">
            <h4><i class="fas fa-bullhorn me-2"></i>Latest School Announcements</h4>
            <div class="card shadow-sm p-3">
                <ul class="list-group list-group-flush">
                    <?php foreach ($latestAnnouncements as $announcement): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                            <small class="text-muted"><?= htmlspecialchars(date('M d, Y', strtotime($announcement['created_at']))) ?></small>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($latestAnnouncements)): ?>
                        <li class="list-group-item text-muted text-center">No recent announcements.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    ---
    
    <div class="row">
        <div class="col-lg-6">
            <h4><i class="fas fa-user-circle me-2"></i>Your Profile Details</h4>
            <table class="table table-bordered bg-white shadow-sm">
                <tbody>
                    <tr><th><i class="fas fa-id-badge me-1 text-primary"></i>Name</th><td><?= htmlspecialchars($name) ?></td></tr>
                    <tr><th><i class="fas fa-envelope me-1 text-primary"></i>Email</th><td><?= htmlspecialchars($email) ?></td></tr>
                    <tr><th><i class="fas fa-calendar-alt me-1 text-primary"></i>Joined</th><td><?= htmlspecialchars($created_at) ?></td></tr>
                </tbody>
            </table>
        </div>
        <div class="col-lg-6">
            <h4><i class="fas fa-list me-2"></i>Your Assigned Subjects (<?= $totalSubjects ?>)</h4>
            <ul class="list-group bg-white shadow-sm">
                <?php foreach ($subjects as $subject): ?>
                    <li class="list-group-item"><i class="fas fa-book-open me-2 text-success"></i><?= htmlspecialchars($subject['subject_name']) ?></li>
                <?php endforeach; ?>
                <?php if (empty($subjects)): ?>
                    <li class="list-group-item text-muted">No subjects assigned.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

</div>

<footer class="bg-light text-center border-top">
    <p class="mt-2">© 2025 School Management System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>