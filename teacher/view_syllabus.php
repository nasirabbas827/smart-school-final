<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM syllabus WHERE syllabus_id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $delete_id, $teacher_id);

    if ($stmt->execute()) {
        $successMsg = "Syllabus deleted successfully.";
    } else {
        $errorMsg = "Error deleting syllabus.";
    }
    $stmt->close();
}

// Fetch syllabus records for the teacher
$query = "
    SELECT s.syllabus_id, c.class_name, sub.subject_name, s.details, s.upload_file, s.created_at
    FROM syllabus s
    JOIN classes c ON s.class_id = c.class_id
    JOIN subjects sub ON s.subject_id = sub.subject_id
    WHERE s.teacher_id = ?
    ORDER BY s.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$syllabus_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Syllabus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this syllabus?")) {
                window.location.href = "view_syllabus.php?delete_id=" + id;
            }
        }
    </script>
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('teacher_sidebar.php'); ?>

    <div class="container mt-5 mb-5">
        <div class="card mx-auto" style="max-width: 1000px;">
            <div class="card-body">
                <h2 class="mb-4 text-center"><i class="fas fa-book me-2"></i>View Syllabus</h2>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
                <?php elseif ($errorMsg): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>

                <?php if (count($syllabus_list) > 0): ?>
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Details</th>
                                <th>File</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($syllabus_list as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($row['class_name']) ?></td>
                                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['details'])) ?></td>
                                    <td>
                                        <?php if (!empty($row['upload_file'])): ?>
                                            <a href="../uploads/syllabus/<?= htmlspecialchars($row['upload_file']) ?>" target="_blank">Download</a>
                                        <?php else: ?>
                                            No File
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                    <td>
                                        <a href="edit_syllabus.php?id=<?= $row['syllabus_id'] ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?= $row['syllabus_id'] ?>)" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No syllabus records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
