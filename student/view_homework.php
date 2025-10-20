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

// Fetch Homework
$homework = [];
$hwStmt = $conn->prepare("SELECT h.title, h.description, h.file_path, h.due_date, s.subject_name, u.first_name AS teacher_first, u.last_name AS teacher_last 
                          FROM homework h
                          JOIN subjects s ON h.subject_id = s.subject_id
                          JOIN users u ON h.teacher_id = u.user_id
                          WHERE h.class_id = ? ORDER BY h.due_date DESC");
$hwStmt->bind_param("i", $class_id);
$hwStmt->execute();
$hwResult = $hwStmt->get_result();
while ($row = $hwResult->fetch_assoc()) {
    $homework[] = $row;
}
$hwStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Homework</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Homework - <?= htmlspecialchars($student_name) ?></h2>
    <div class="card">
        <div class="card-header bg-primary text-white">Homework List</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Subject</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>File</th>
                        <th>Due Date</th>
                        <th>Teacher</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($homework): ?>
                        <?php foreach ($homework as $hw): ?>
                            <tr>
                                <td><?= htmlspecialchars($hw['subject_name']) ?></td>
                                <td><?= htmlspecialchars($hw['title']) ?></td>
                                <td><?= htmlspecialchars($hw['description']) ?></td>
                                <td>
                                    <?php if ($hw['file_path']): ?>
                                        <a href="../uploads/homework/<?= htmlspecialchars($hw['file_path']) ?>" target="_blank">Download</a>
                                    <?php else: ?>N/A<?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($hw['due_date']) ?></td>
                                <td><?= htmlspecialchars($hw['teacher_first'] . " " . $hw['teacher_last']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No homework available.</td></tr>
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
