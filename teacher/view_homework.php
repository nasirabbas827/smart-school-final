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
    $stmt = $conn->prepare("DELETE FROM homework WHERE homework_id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $delete_id, $teacher_id);

    if ($stmt->execute()) {
        $successMsg = "Homework deleted successfully.";
    } else {
        $errorMsg = "Error deleting homework.";
    }
    $stmt->close();
}

// Fetch homework records for the teacher
$query = "
    SELECT h.homework_id, c.class_name, s.subject_name, h.title, h.description, h.file_path, h.due_date, h.created_at
    FROM homework h
    JOIN classes c ON h.class_id = c.class_id
    JOIN subjects s ON h.subject_id = s.subject_id
    WHERE h.teacher_id = ?
    ORDER BY h.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$homework_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Homework</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this homework?")) {
                window.location.href = "view_homework.php?delete_id=" + id;
            }
        }
    </script>
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('teacher_sidebar.php'); ?>

    <div class="container mt-5 mb-5">
        <div class="card mx-auto" style="max-width: 1000px;">
            <div class="card-body">
                <h2 class="mb-4 text-center">View Homework</h2>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
                <?php elseif ($errorMsg): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>

                <?php if (count($homework_list) > 0): ?>
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>File</th>
                                <th>Due Date</th>
                                <th>Date Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($homework_list as $index => $row): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($row['class_name']) ?></td>
                                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                    <td>
                                        <?php if (!empty($row['file_path'])): ?>
                                            <a href="../uploads/homework/<?= htmlspecialchars($row['file_path']) ?>" target="_blank">Download</a>
                                        <?php else: ?>
                                            No File
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['due_date']) ?></td>
                                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                                    <td>
                                        <a href="edit_homework.php?id=<?= $row['homework_id'] ?>" class="btn btn-warning btn-sm">
                                            Edit
                                        </a>
                                        <button onclick="confirmDelete(<?= $row['homework_id'] ?>)" class="btn btn-danger btn-sm">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">No homework records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
