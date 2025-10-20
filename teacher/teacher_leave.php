<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Update leave status
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['leave_id'])) {
    $leave_id = intval($_POST['leave_id']);
    $status = $_POST['status'];
    if (in_array($status, ['Approved','Rejected'])) {
        $stmt = $conn->prepare("UPDATE leave_applications SET status=?, reviewed_by=? WHERE leave_id=?");
        $stmt->bind_param("sii", $status, $teacher_id, $leave_id);
        if ($stmt->execute()) $successMsg = "Leave status updated successfully.";
        else $errorMsg = "Failed to update status.";
        $stmt->close();
    }
}

// Fetch leave applications for teacher's classes
$leaves = [];
$query = "
    SELECT l.*, u.first_name, u.last_name, c.class_name
    FROM leave_applications l
    JOIN users u ON l.student_id = u.user_id
    JOIN classes c ON l.class_id = c.class_id
    WHERE c.class_id IN (SELECT class_id FROM users WHERE user_id=?)
    ORDER BY l.applied_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
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
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Student Leave Applications</h2>

    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-info text-white">Leave List</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Reason</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Action</th>
                        <th>Applied At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($leaves): ?>
                        <?php foreach ($leaves as $lv): ?>
                            <tr>
                                <td><?= htmlspecialchars($lv['first_name'] . ' ' . $lv['last_name']) ?></td>
                                <td><?= htmlspecialchars($lv['class_name']) ?></td>
                                <td><?= htmlspecialchars($lv['reason']) ?></td>
                                <td><?= htmlspecialchars($lv['start_date']) ?></td>
                                <td><?= htmlspecialchars($lv['end_date']) ?></td>
                                <td><?= htmlspecialchars($lv['status']) ?></td>
                                <td>
                                    <form method="POST" class="d-flex">
                                        <input type="hidden" name="leave_id" value="<?= $lv['leave_id'] ?>">
                                        <select name="status" class="form-select form-select-sm me-2">
                                            <option value="Pending" <?= $lv['status']=='Pending'?'selected':'' ?>>Pending</option>
                                            <option value="Approved" <?= $lv['status']=='Approved'?'selected':'' ?>>Approved</option>
                                            <option value="Rejected" <?= $lv['status']=='Rejected'?'selected':'' ?>>Rejected</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-success">Update</button>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars($lv['applied_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">No leave applications available.</td></tr>
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
