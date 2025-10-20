<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Handle Delete Request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delQuery = "DELETE FROM grades WHERE grade_id = ? AND entered_by = ?";
    $stmt = $conn->prepare($delQuery);
    $stmt->bind_param("ii", $delete_id, $teacher_id);
    if ($stmt->execute()) {
        $successMsg = "Grade deleted successfully.";
    } else {
        $errorMsg = "Failed to delete grade.";
    }
    $stmt->close();
}

// Fetch the teacher's class
$classQuery = "
    SELECT class_id, class_name 
    FROM classes 
    WHERE class_id = (SELECT class_id FROM users WHERE user_id = ?)
";
$stmt = $conn->prepare($classQuery);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classResult = $stmt->get_result();
$class = $classResult->fetch_assoc();
$stmt->close();

if (!$class) {
    echo "No class assigned to this teacher.";
    exit();
}

// Fetch grades
$query = "
    SELECT g.grade_id, g.exam_type, g.term, g.marks_obtained, g.max_marks, 
           g.grade, g.comments, g.entered_at,
           u.first_name, u.last_name,
           s.subject_name
    FROM grades g
    JOIN users u ON g.student_id = u.user_id
    JOIN subjects s ON g.subject_id = s.subject_id
    WHERE g.class_id = ? AND g.entered_by = ?
    ORDER BY g.grade_id DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $class['class_id'], $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>View Grades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this grade?")) {
                window.location.href = "view_grades.php?delete_id=" + id;
            }
        }
    </script>
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card">
        <div class="card-body">
            <h2 class="mb-4 text-center">Student Grades List</h2>

            <?php if ($successMsg): ?>
                <div class="alert alert-success text-center"><?= $successMsg ?></div>
            <?php elseif ($errorMsg): ?>
                <div class="alert alert-danger text-center"><?= $errorMsg ?></div>
            <?php endif; ?>

            <div class="mb-3 text-end">
                <a href="add_grade.php" class="btn btn-primary">Add New Grade</a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Exam Type</th>
                            <th>Term</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Comments</th>
                            <th>Entered At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                            <td><?= htmlspecialchars($row['subject_name']) ?></td>
                            <td><?= htmlspecialchars($row['exam_type']) ?></td>
                            <td><?= htmlspecialchars($row['term']) ?></td>
                            <td><?= htmlspecialchars($row['marks_obtained']) ?> / <?= htmlspecialchars($row['max_marks']) ?></td>
                            <td><?= htmlspecialchars($row['grade'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['comments'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['entered_at']) ?></td>
                            <td>
                                <a href="edit_grade.php?id=<?= $row['grade_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <button onclick="confirmDelete(<?= $row['grade_id'] ?>)" class="btn btn-sm btn-danger">Delete</button>
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
