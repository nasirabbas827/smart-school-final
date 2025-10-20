<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Student") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$student_id = $_SESSION["user_id"];

// Get student class
$stmt = $conn->prepare("SELECT first_name, last_name, class_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_name = $student['first_name'] . " " . $student['last_name'];
$class_id = $student['class_id'];
$stmt->close();

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Timetable</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Timetable - <?= htmlspecialchars($student_name) ?></h2>
    <div class="card">
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
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
