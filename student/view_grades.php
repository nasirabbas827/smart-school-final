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

// Fetch Grades
$grades = [];
$grStmt = $conn->prepare("SELECT g.exam_type, g.term, g.marks_obtained, g.max_marks, g.grade, g.comments, s.subject_name 
                          FROM grades g
                          JOIN subjects s ON g.subject_id = s.subject_id
                          WHERE g.student_id = ? AND g.class_id = ? ORDER BY g.entered_at DESC");
$grStmt->bind_param("ii", $student_id, $class_id);
$grStmt->execute();
$grResult = $grStmt->get_result();
while ($row = $grResult->fetch_assoc()) {
    $grades[] = $row;
}
$grStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Grades</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Grades - <?= htmlspecialchars($student_name) ?></h2>
    <div class="card">
        <div class="card-header bg-warning text-white">Grades</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Subject</th>
                        <th>Exam Type</th>
                        <th>Term</th>
                        <th>Marks Obtained</th>
                        <th>Max Marks</th>
                        <th>Grade</th>
                        <th>Comments</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($grades): ?>
                        <?php foreach ($grades as $gr): ?>
                            <tr>
                                <td><?= htmlspecialchars($gr['subject_name']) ?></td>
                                <td><?= htmlspecialchars($gr['exam_type']) ?></td>
                                <td><?= htmlspecialchars($gr['term']) ?></td>
                                <td><?= htmlspecialchars($gr['marks_obtained']) ?></td>
                                <td><?= htmlspecialchars($gr['max_marks']) ?></td>
                                <td><?= htmlspecialchars($gr['grade']) ?></td>
                                <td><?= htmlspecialchars($gr['comments']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No grades available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
