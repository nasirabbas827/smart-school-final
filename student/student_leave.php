<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Student") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$student_id = $_SESSION["user_id"];

// Get student info and class
$stmt = $conn->prepare("SELECT first_name, last_name, class_id FROM users WHERE user_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_name = $student['first_name'] . " " . $student['last_name'];
$class_id = $student['class_id'];
$stmt->close();

$successMsg = $errorMsg = "";

// Handle leave submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $reason = trim($_POST['reason'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    if ($reason && $start_date && $end_date) {
        $stmt = $conn->prepare("INSERT INTO leave_applications (student_id, class_id, reason, start_date, end_date) VALUES (?,?,?,?,?)");
        $stmt->bind_param("iisss", $student_id, $class_id, $reason, $start_date, $end_date);
        if ($stmt->execute()) $successMsg = "Leave application submitted successfully.";
        else $errorMsg = "Failed to submit leave.";
        $stmt->close();
    } else {
        $errorMsg = "All fields are required.";
    }
}

// Fetch student leave applications
$leaves = [];
$stmt = $conn->prepare("SELECT * FROM leave_applications WHERE student_id=? ORDER BY applied_at DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $leaves[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leave Applications</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Leave Applications - <?= htmlspecialchars($student_name) ?></h2>

    <!-- Success/Error messages -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <!-- Submit Leave Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Apply for Leave</div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label>Reason</label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Submit Leave</button>
            </form>
        </div>
    </div>

    <!-- View Leaves -->
    <div class="card">
        <div class="card-header bg-info text-white">My Leave Applications</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Reason</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Reviewed By</th>
                        <th>Applied At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($leaves): ?>
                        <?php foreach ($leaves as $lv): ?>
                            <tr>
                                <td><?= htmlspecialchars($lv['reason']) ?></td>
                                <td><?= htmlspecialchars($lv['start_date']) ?></td>
                                <td><?= htmlspecialchars($lv['end_date']) ?></td>
                                <td><?= htmlspecialchars($lv['status']) ?></td>
                                <td><?= $lv['reviewed_by'] ? htmlspecialchars($lv['reviewed_by']) : 'Pending' ?></td>
                                <td><?= htmlspecialchars($lv['applied_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No leave applications submitted.</td></tr>
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
