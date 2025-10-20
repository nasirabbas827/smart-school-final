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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Syllabus</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Syllabus - <?= htmlspecialchars($student_name) ?></h2>
    <div class="card">
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
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
