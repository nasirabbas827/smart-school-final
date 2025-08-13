<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$teacher_id = $_SESSION["user_id"];

// Fetch teacher info
$sql = "SELECT first_name, email, created_at FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$stmt->bind_result($name, $email, $created_at);
$stmt->fetch();
$stmt->close();

// Fetch classes assigned to teacher
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

// Fetch subjects assigned to teacher
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

// Fetch timetable entries
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

$totalClasses = count($classes);
$totalSubjects = count($subjects);
$totalSessions = count($timetable);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Teacher Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />
    <style>
        body { background-color: #e6effc; font-family: Arial, sans-serif; margin-left: 280px; }
        .container { padding: 2rem 1rem; }
        h2, h4 { color: #333; margin-bottom: 1.5rem; }
        .card { border-radius: 8px; transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        footer { background: #f8f9fa; padding: 2rem 1rem; text-align: center; margin-top: 2rem; }
        @media (max-width: 992px) { body { margin-left: 0; } }
    </style>
</head>
<body>
<?php include('teacher_sidebar.php'); ?>

<div class="container my-4">
    <h2><i class="fas fa-user-circle me-2"></i>Your Profile</h2>
    <table class="table table-bordered w-100 w-md-50 mb-5">
        <tbody>
            <tr><th><i class="fas fa-id-badge me-1"></i>Name</th><td><?= htmlspecialchars($name) ?></td></tr>
            <tr><th><i class="fas fa-envelope me-1"></i>Email</th><td><?= htmlspecialchars($email) ?></td></tr>
            <tr><th><i class="fas fa-calendar-alt me-1"></i>Joined</th><td><?= htmlspecialchars($created_at) ?></td></tr>
        </tbody>
    </table>

    <!-- Summary cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title m-0"><i class="fas fa-school me-2"></i>Classes</h5>
                </div>
                <div class="card-body">
                    <p class="display-6 text-primary"><?= $totalClasses ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title m-0"><i class="fas fa-book me-2"></i>Subjects</h5>
                </div>
                <div class="card-body">
                    <p class="display-6 text-warning"><?= $totalSubjects ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title m-0"><i class="fas fa-calendar-check me-2"></i>Sessions</h5>
                </div>
                <div class="card-body">
                    <p class="display-6 text-success"><?= $totalSessions ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Classes preview -->
    <h4>Your Classes</h4>
    <div class="row row-cols-1 row-cols-md-3 g-3 mb-5">
        <?php foreach ($classes as $class): ?>
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title m-0"><i class="fas fa-school me-2"></i> <?= htmlspecialchars($class['class_name']) ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><small class="text-muted">Class ID: <?= $class['class_id'] ?></small></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($classes)): ?>
            <p class="text-muted">No classes assigned.</p>
        <?php endif; ?>
    </div>

    <!-- Subjects preview -->
    <h4>Your Subjects</h4>
    <div class="row row-cols-1 row-cols-md-3 g-3 mb-5">
        <?php foreach ($subjects as $subject): ?>
            <div class="col">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title m-0"><i class="fas fa-book me-2"></i> <?= htmlspecialchars($subject['subject_name']) ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><small class="text-muted">Subject ID: <?= $subject['subject_id'] ?></small></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($subjects)): ?>
            <p class="text-muted">No subjects assigned.</p>
        <?php endif; ?>
    </div>

    <!-- Timetable preview -->
    <h4>Your Timetable</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Day</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Start</th>
                <th>End</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($timetable as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['day_of_week']) ?></td>
                    <td><?= htmlspecialchars($row['class_name']) ?></td>
                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                    <td><?= htmlspecialchars($row['start_time']) ?></td>
                    <td><?= htmlspecialchars($row['end_time']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($timetable)): ?>
                <tr><td colspan="5" class="text-muted">No sessions scheduled.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<footer class="bg-light text-center">
    <p class="mt-2">© 2025 School Management System</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
