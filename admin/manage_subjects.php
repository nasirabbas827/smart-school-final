<?php
session_start();
include('config.php');

// Check admin login
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$successMsg = $errorMsg = "";

// Handle delete
if (isset($_GET['delete'])) {
    $subject_id = intval($_GET['delete']);
    if ($subject_id > 0) {
        $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
        $stmt->bind_param("i", $subject_id);
        if ($stmt->execute()) {
            $successMsg = "Subject deleted successfully.";
        } else {
            $errorMsg = "Error deleting subject: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch all subjects with class name & section
$subjects = [];
$result = $conn->query("
    SELECT s.subject_id, s.subject_name, c.class_name, c.section
    FROM subjects s
    JOIN classes c ON s.class_id = c.class_id
    ORDER BY c.class_name ASC, s.subject_name ASC
");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('admin_sidebar.php'); ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center"><i class="fas fa-book me-2"></i>Manage Subjects</h2>

        <?php if ($successMsg): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="card-title">Subjects List</h5>
                    <a href="add_subject.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Subject</a>
                </div>

                <?php if (count($subjects) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Subject Name</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $index => $subject): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                                        <td><?= htmlspecialchars($subject['class_name']) ?></td>
                                        <td><?= htmlspecialchars($subject['section']) ?></td>
                                        <td>
                                            <a href="edit_subject.php?id=<?= $subject['subject_id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="manage_subjects.php?delete=<?= $subject['subject_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this subject?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No subjects found. <a href="add_subject.php">Add one now</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
