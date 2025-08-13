<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Filters
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// Fetch attendance records
$sql = "SELECT a.attendance_id, CONCAT(u.first_name, ' ', u.last_name) AS student_name, a.date, a.status
        FROM attendance a
        JOIN users u ON a.student_id = u.user_id
        WHERE a.marked_by = ?";

$params = [$teacher_id];
$types = "i";

if (!empty($filter_date)) {
    $sql .= " AND a.date = ?";
    $params[] = $filter_date;
    $types .= "s";
}

if (!empty($filter_status)) {
    $sql .= " AND a.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql .= " ORDER BY a.date DESC, student_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$attendanceRecords = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $attendanceRecords[] = $row;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('teacher_sidebar.php'); ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center"><i class="fas fa-eye me-2"></i>View Attendance</h2>

        <form method="GET" action="" class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="date" class="form-label">Filter by Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All</option>
                    <option value="Present" <?= ($filter_status === "Present") ? "selected" : "" ?>>Present</option>
                    <option value="Absent" <?= ($filter_status === "Absent") ? "selected" : "" ?>>Absent</option>
                    <option value="Leave" <?= ($filter_status === "Leave") ? "selected" : "" ?>>Leave</option>
                    <option value="Holiday" <?= ($filter_status === "Holiday") ? "selected" : "" ?>>Holiday</option>
                </select>
            </div>
            <div class="col-md-4 align-self-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="view_attendance.php" class="btn btn-secondary"><i class="fas fa-undo me-1"></i>Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Student</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($attendanceRecords)): ?>
                        <?php foreach ($attendanceRecords as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['student_name']) ?></td>
                                <td><?= htmlspecialchars($record['date']) ?></td>
                                <td><?= htmlspecialchars($record['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">No records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <a href="mark_attendance.php" class="btn btn-success"><i class="fas fa-clipboard-check me-1"></i>Mark Attendance</a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
