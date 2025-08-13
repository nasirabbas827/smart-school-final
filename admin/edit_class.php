<?php
session_start();
include('config.php');

// Check admin login
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$class_id = intval($_GET['class_id'] ?? 0);

// Fetch class data
$stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ?");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
$class = $result->fetch_assoc();
$stmt->close();

if (!$class) {
    echo "<script>alert('Class not found.'); window.location='manage_classes.php';</script>";
    exit;
}

$successMsg = $errorMsg = "";

// Handle update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $class_name = trim($_POST['class_name']);
    $section = trim($_POST['section']);

    if (empty($class_name) || empty($section)) {
        $errorMsg = "All fields are required.";
    } else {
        $stmt = $conn->prepare("UPDATE classes SET class_name = ?, section = ? WHERE class_id = ?");
        $stmt->bind_param("ssi", $class_name, $section, $class_id);

        if ($stmt->execute()) {
            $successMsg = "Class updated successfully.";
        } else {
            $errorMsg = "Error updating class: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Class</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('admin_sidebar.php'); ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center"><i class="fas fa-edit me-2"></i>Edit Class</h2>

        <?php if ($successMsg): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="card mx-auto shadow" style="max-width: 500px;">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="class_name" class="form-label">Class Name</label>
                        <input type="text" class="form-control" id="class_name" name="class_name"
                               value="<?= htmlspecialchars($class['class_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="section" class="form-label">Section</label>
                        <input type="text" class="form-control" id="section" name="section"
                               value="<?= htmlspecialchars($class['section']) ?>" required>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Update Class</button>
                        <a href="manage_classes.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
