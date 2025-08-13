<?php
session_start();
include('config.php');

// Check admin login
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$successMsg = $errorMsg = "";

// Get subject ID from query string
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_subjects.php");
    exit;
}

$subject_id = intval($_GET['id']);

// Fetch subject details
$stmt = $conn->prepare("SELECT subject_id, subject_name, class_id FROM subjects WHERE subject_id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_subjects.php");
    exit;
}

$subject = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $subject_name = trim($_POST['subject_name']);
    $class_id = intval($_POST['class_id']);

    if (empty($subject_name) || empty($class_id)) {
        $errorMsg = "All fields are required.";
    } else {
        $updateStmt = $conn->prepare("UPDATE subjects SET subject_name = ?, class_id = ? WHERE subject_id = ?");
        $updateStmt->bind_param("sii", $subject_name, $class_id, $subject_id);

        if ($updateStmt->execute()) {
            $successMsg = "Subject updated successfully.";
            // Refresh subject data after update
            $subject['subject_name'] = $subject_name;
            $subject['class_id'] = $class_id;
        } else {
            $errorMsg = "Error updating subject: " . $conn->error;
        }
        $updateStmt->close();
    }
}

// Fetch classes for dropdown
$classes = [];
$result = $conn->query("SELECT class_id, class_name, section FROM classes ORDER BY class_name ASC");
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Subject</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('admin_sidebar.php'); ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center"><i class="fas fa-edit me-2"></i>Edit Subject</h2>

        <?php if ($successMsg): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="card mx-auto shadow" style="max-width: 500px;">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="subject_name" class="form-label">Subject Name</label>
                        <input type="text" class="form-control" id="subject_name" name="subject_name" 
                               value="<?= htmlspecialchars($subject['subject_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="class_id" class="form-label">Select Class</label>
                        <select class="form-select" id="class_id" name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['class_id'] ?>" 
                                    <?= ($class['class_id'] == $subject['class_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name'] . ' - ' . $class['section']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Subject</button>
                        <a href="manage_subjects.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
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
