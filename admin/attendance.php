<?php
session_start();
include('config.php');

// Check admin login
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}


$successMsg = $errorMsg = "";

// Filters
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_class = isset($_GET['class_id']) ? $_GET['class_id'] : '';

// Fetch all classes for filter dropdown
$classes = [];
$classResult = $conn->query("SELECT class_id, class_name FROM classes ORDER BY class_name ASC");
if ($classResult && $classResult->num_rows > 0) {
    while ($row = $classResult->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Fetch attendance records
$sql = "SELECT a.attendance_id, CONCAT(u.first_name, ' ', u.last_name) AS student_name, 
        c.class_name, a.date, a.status
        FROM attendance a
        JOIN users u ON a.student_id = u.user_id
        JOIN classes c ON u.class_id = c.class_id
        WHERE 1=1";

$params = [];
$types = "";

// Apply filters
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

if (!empty($filter_class)) {
    $sql .= " AND c.class_id = ?";
    $params[] = $filter_class;
    $types .= "i";
}

$sql .= " ORDER BY a.date DESC, c.class_name ASC, student_name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
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
    <title>Admin - View Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('admin_sidebar.php'); ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center"><i class="fas fa-eye me-2"></i>View Attendance (All Classes)</h2>

        <form method="GET" action="" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="date" class="form-label">Filter by Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All</option>
                    <option value="Present" <?= ($filter_status === "Present") ? "selected" : "" ?>>Present</option>
                    <option value="Absent" <?= ($filter_status === "Absent") ? "selected" : "" ?>>Absent</option>
                    <option value="Leave" <?= ($filter_status === "Leave") ? "selected" : "" ?>>Leave</option>
                    <option value="Holiday" <?= ($filter_status === "Holiday") ? "selected" : "" ?>>Holiday</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="class_id" class="form-label">Filter by Class</label>
                <select class="form-select" id="class_id" name="class_id">
                    <option value="">All</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?= $cls['class_id'] ?>" <?= ($filter_class == $cls['class_id']) ? "selected" : "" ?>>
                            <?= htmlspecialchars($cls['class_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="attendance.php" class="btn btn-secondary"><i class="fas fa-undo me-1"></i>Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($attendanceRecords)): ?>
                        <?php foreach ($attendanceRecords as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['student_name']) ?></td>
                                <td><?= htmlspecialchars($record['class_name']) ?></td>
                                <td><?= htmlspecialchars($record['date']) ?></td>
                                <td><?= htmlspecialchars($record['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

       
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
