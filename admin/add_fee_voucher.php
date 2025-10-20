<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$successMsg = $errorMsg = "";

// Fetch all students for dropdown
$students = [];
$studentStmt = $conn->prepare("SELECT user_id, first_name, last_name, class_id FROM users WHERE usertype='Student' ORDER BY first_name, last_name");
$studentStmt->execute();
$result = $studentStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$studentStmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);
    $month_year = trim($_POST['month_year']);
    $student_id = intval($_POST['student_id']);
    $amount = floatval($_POST['amount']);
    $due_date = $_POST['due_date'];

    if (empty($description) || empty($month_year) || empty($student_id) || empty($amount) || empty($due_date)) {
        $errorMsg = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO fee_vouchers (description, month_year, student_id, amount, due_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssids", $description, $month_year, $student_id, $amount, $due_date);
        if ($stmt->execute()) {
            $successMsg = "Fee voucher generated successfully.";
        } else {
            $errorMsg = "Error generating voucher: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Fee Voucher</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('admin_sidebar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4 text-center">Add Fee Voucher</h2>

    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="card mx-auto shadow" style="max-width:600px;">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $stu): ?>
                            <option value="<?= $stu['user_id'] ?>">
                                <?= htmlspecialchars($stu['first_name'] . " " . $stu['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Month/Year</label>
                    <input type="text" name="month_year" class="form-control" placeholder="e.g., October 2025" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" name="amount" class="form-control" step="0.01" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" required>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Generate Voucher</button>
                    <a href="admin_fee_management.php" class="btn btn-secondary">View Vouchers</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
