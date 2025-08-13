<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Student") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$student_id = $_SESSION["user_id"];

// Fetch student info
$stmt = $conn->prepare("SELECT first_name, last_name, class_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $student_name = $student['first_name'] . " " . $student['last_name'];
    $class_id = $student['class_id'];
} else {
    $student_name = "Student";
    $class_id = 0;
}
$stmt->close();

// Fetch attendance
$attendance = [];
$attStmt = $conn->prepare("SELECT date, status FROM attendance WHERE student_id = ? ORDER BY date DESC");
$attStmt->bind_param("i", $student_id);
$attStmt->execute();
$attResult = $attStmt->get_result();
while ($row = $attResult->fetch_assoc()) {
    $attendance[] = $row;
}
$attStmt->close();

// Fetch timetable
$timetable = [];
$ttStmt = $conn->prepare("SELECT t.day_of_week, t.start_time, t.end_time, s.subject_name, u.first_name AS teacher_first, u.last_name AS teacher_last 
                          FROM timetables t 
                          JOIN subjects s ON t.subject_id = s.subject_id
                          JOIN users u ON t.teacher_id = u.user_id
                          WHERE t.class_id = ? ORDER BY FIELD(t.day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat'), t.start_time ASC");
$ttStmt->bind_param("i", $class_id);
$ttStmt->execute();
$ttResult = $ttStmt->get_result();
while ($row = $ttResult->fetch_assoc()) {
    $timetable[] = $row;
}
$ttStmt->close();

// Fetch syllabus
$syllabus = [];
$syllStmt = $conn->prepare("SELECT s.details, s.upload_file, sub.subject_name 
                            FROM syllabus s
                            JOIN subjects sub ON s.subject_id = sub.subject_id
                            WHERE s.class_id = ?");
$syllStmt->bind_param("i", $class_id);
$syllStmt->execute();
$syllResult = $syllStmt->get_result();
while ($row = $syllResult->fetch_assoc()) {
    $syllabus[] = $row;
}
$syllStmt->close();

// Fetch announcements
$announcements = [];
$annStmt = $conn->prepare("SELECT title, description, created_at FROM announcements ORDER BY created_at DESC");
$annStmt->execute();
$annResult = $annStmt->get_result();
while ($row = $annResult->fetch_assoc()) {
    $announcements[] = $row;
}
$annStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Welcome, <?= htmlspecialchars($student_name) ?></h2>

    <!-- Attendance -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Attendance Records</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($attendance): ?>
                        <?php foreach ($attendance as $att): ?>
                            <tr>
                                <td><?= htmlspecialchars($att['date']) ?></td>
                                <td><?= htmlspecialchars($att['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center">No attendance records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Timetable -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Timetable</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Day</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($timetable): ?>
                        <?php foreach ($timetable as $tt): ?>
                            <tr>
                                <td><?= htmlspecialchars($tt['day_of_week']) ?></td>
                                <td><?= htmlspecialchars($tt['start_time']) ?></td>
                                <td><?= htmlspecialchars($tt['end_time']) ?></td>
                                <td><?= htmlspecialchars($tt['subject_name']) ?></td>
                                <td><?= htmlspecialchars($tt['teacher_first'] . " " . $tt['teacher_last']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No timetable available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Syllabus -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-white">Syllabus</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Subject</th>
                        <th>Details</th>
                        <th>File</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($syllabus): ?>
                        <?php foreach ($syllabus as $syll): ?>
                            <tr>
                                <td><?= htmlspecialchars($syll['subject_name']) ?></td>
                                <td><?= htmlspecialchars($syll['details']) ?></td>
                                <td>
                                    <?php if ($syll['upload_file']): ?>
                                        <a href="../uploads/syllabus/<?= htmlspecialchars($syll['upload_file']) ?>" target="_blank">Download</a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center">No syllabus available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Announcements -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">Announcements</div>
        <div class="card-body">
            <?php if ($announcements): ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="mb-3">
                        <h5><?= htmlspecialchars($ann['title']) ?></h5>
                        <p><?= nl2br(htmlspecialchars($ann['description'])) ?></p>
                        <small class="text-muted"><?= htmlspecialchars($ann['created_at']) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">No announcements found.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
