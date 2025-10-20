<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Student") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$student_id = $_SESSION["user_id"];

// Fetch student info
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_name = $student['first_name'] . " " . $student['last_name'];
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Records</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Attendance Records - <?= htmlspecialchars($student_name) ?></h2>
    <div class="card">
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
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
