<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Delete Assignment
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $check = $conn->prepare("SELECT file_path FROM assignments WHERE assignment_id = ? AND created_by = ?");
    $check->bind_param("ii", $delete_id, $teacher_id);
    $check->execute();
    $resultCheck = $check->get_result();
    $assignment = $resultCheck->fetch_assoc();
    $check->close();

    if ($assignment) {
        if (!empty($assignment['file_path'])) {
            $file = "../uploads/assignments/" . $assignment['file_path'];
            if (file_exists($file)) unlink($file);
        }
        $stmtDel = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ? AND created_by = ?");
        $stmtDel->bind_param("ii", $delete_id, $teacher_id);
        if ($stmtDel->execute()) $successMsg = "Assignment deleted successfully.";
        else $errorMsg = "Failed to delete assignment.";
        $stmtDel->close();
    } else {
        $errorMsg = "Unauthorized or invalid delete request.";
    }
}

// Fetch Assignments
$query = "
    SELECT a.assignment_id, a.title, a.description, a.due_date, a.file_path,
           c.class_name, s.subject_name
    FROM assignments a
    JOIN classes c ON a.class_id = c.class_id
    JOIN subjects s ON a.subject_id = s.subject_id
    WHERE a.created_by = ?
    ORDER BY a.assignment_id DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>View Assignments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card">
        <div class="card-body">
            <h2 class="mb-4 text-center">Assignments List</h2>

            <?php if ($successMsg): ?>
                <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
            <?php elseif ($errorMsg): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <div class="mb-3 text-end">
                <a href="add_assignment.php" class="btn btn-primary">Add New Assignment</a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Title</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Due Date</th>
                            <th>File</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= htmlspecialchars($row['class_name']) ?></td>
                                <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                <td><?= htmlspecialchars($row['due_date']) ?></td>
                                <td>
                                    <?php if ($row['file_path']): ?>
                                        <a href="../uploads/assignments/<?= htmlspecialchars($row['file_path']) ?>" target="_blank">View</a>
                                    <?php else: ?>N/A<?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_assignment.php?id=<?= $row['assignment_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="view_assignments.php?delete=<?= $row['assignment_id'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this assignment?');">Delete</a>
                                    <a href="view_submissions.php?assignment_id=<?= $row['assignment_id'] ?>" class="btn btn-sm btn-info">View Submissions</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
