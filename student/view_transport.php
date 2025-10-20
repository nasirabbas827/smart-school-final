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
$student = $stmt->get_result()->fetch_assoc();
$student_name = $student['first_name'] . " " . $student['last_name'];
$class_id = $student['class_id'];
$stmt->close();

// Fetch transport info for student's class
$transports = [];
$tStmt = $conn->prepare("SELECT * FROM transport WHERE class_id = ?");
$tStmt->bind_param("i", $class_id);
$tStmt->execute();
$tResult = $tStmt->get_result();
while ($row = $tResult->fetch_assoc()) {
    $transports[] = $row;
}
$tStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Class Transport Details</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Transport Details - <?= htmlspecialchars($student_name) ?></h2>

    <div class="card">
        <div class="card-header bg-primary text-white">Your Class Transport</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Bus Number</th>
                        <th>Driver Name</th>
                        <th>Driver Phone</th>
                        <th>Route Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transports): ?>
                        <?php foreach ($transports as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['bus_number']) ?></td>
                                <td><?= htmlspecialchars($t['driver_name']) ?></td>
                                <td><?= htmlspecialchars($t['driver_phone']) ?></td>
                                <td><?= nl2br(htmlspecialchars($t['route_details'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No transport details available for your class.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
