<?php
session_start();
include('config.php');

// Check admin login
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$successMsg = $errorMsg = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $class_name = trim($_POST['class_name']);
    $section = trim($_POST['section']);

    if (empty($class_name) || empty($section)) {
        $errorMsg = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO classes (class_name, section) VALUES (?, ?)");
        $stmt->bind_param("ss", $class_name, $section);

        if ($stmt->execute()) {
            $successMsg = "Class added successfully.";
        } else {
            $errorMsg = "Error adding class: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Class</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('admin_sidebar.php'); ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center"><i class="fas fa-plus-circle me-2"></i>Add New Class</h2>

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
                        <input type="text" class="form-control" id="class_name" name="class_name" placeholder="e.g., Grade 10" required>
                    </div>
                    <div class="mb-3">
                        <label for="section" class="form-label">Section</label>
                        <input type="text" class="form-control" id="section" name="section" placeholder="e.g., A" required>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Class</button>
                        <a href="manage_classes.php" class="btn btn-secondary"><i class="fas fa-eye me-1"></i>View Classes</a>
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
