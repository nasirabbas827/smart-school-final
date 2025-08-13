<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Fetch only students
$students = [];
$studentQuery = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS name 
                 FROM users 
                 WHERE usertype = 'Student' 
                 ORDER BY first_name ASC";
$studentResult = $conn->query($studentQuery);
if ($studentResult && $studentResult->num_rows > 0) {
    while ($row = $studentResult->fetch_assoc()) {
        $students[] = $row;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["attendance"])) {
    $date = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status, marked_by) VALUES (?, ?, ?, ?)");
    foreach ($_POST["attendance"] as $student_id => $status) {
        // Check if already marked
        $check = $conn->prepare("SELECT attendance_id FROM attendance WHERE student_id = ? AND date = ?");
        $check->bind_param("is", $student_id, $date);
        $check->execute();
        $check->store_result();

        if ($check->num_rows == 0) {
            $stmt->bind_param("issi", $student_id, $date, $status, $teacher_id);
            $stmt->execute();
        }
        $check->close();
    }
    $stmt->close();

    $successMsg = "Attendance marked successfully for $date.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('teacher_sidebar.php'); ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center"><i class="fas fa-clipboard-check me-2"></i>Mark Attendance</h2>

        <?php if ($successMsg): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Student</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Leave</th>
                            <th>Holiday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><input type="radio" name="attendance[<?= $student['user_id'] ?>]" value="Present" required></td>
                                <td><input type="radio" name="attendance[<?= $student['user_id'] ?>]" value="Absent"></td>
                                <td><input type="radio" name="attendance[<?= $student['user_id'] ?>]" value="Leave"></td>
                                <td><input type="radio" name="attendance[<?= $student['user_id'] ?>]" value="Holiday"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Attendance</button>
                <a href="view_attendance.php" class="btn btn-secondary"><i class="fas fa-eye me-1"></i>View Attendance</a>
            </div>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
