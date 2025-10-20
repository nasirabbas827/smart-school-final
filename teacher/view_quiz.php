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
    $stmt = $conn->prepare("DELETE FROM quizzes WHERE quiz_id = ? AND created_by = ?");
    $stmt->bind_param("ii", $delete_id, $teacher_id);

    if ($stmt->execute()) {
        $successMsg = "Quiz deleted successfully.";
    } else {
        $errorMsg = "Error deleting quiz.";
    }
    $stmt->close();
}

// Fetch quizzes created by this teacher
$query = "
    SELECT q.quiz_id, q.question, q.option_one, q.option_two, q.option_three, q.option_four, q.correct_option,
           q.total_marks, c.class_name, s.subject_name, q.created_at
    FROM quizzes q
    JOIN classes c ON q.class_id = c.class_id
    JOIN subjects s ON q.subject_id = s.subject_id
    WHERE q.created_by = ?
    ORDER BY q.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$quizzes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Quizzes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this quiz?")) {
                window.location.href = "view_quiz.php?delete_id=" + id;
            }
        }
    </script>
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 1000px;">
        <div class="card-body">
            <h2 class="mb-4 text-center">View Quizzes</h2>

            <?php if ($successMsg): ?>
                <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
            <?php elseif ($errorMsg): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <?php if (count($quizzes) > 0): ?>
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Question</th>
                            <th>Correct Option</th>
                            <th>Total Marks</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $index => $q): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($q['class_name']) ?></td>
                                <td><?= htmlspecialchars($q['subject_name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($q['question'])) ?></td>
                                <td><?= htmlspecialchars($q[$q['correct_option']]) ?></td>
                                <td><?= htmlspecialchars($q['total_marks']) ?></td>
                                <td><?= htmlspecialchars($q['created_at']) ?></td>
                                <td>
                                    <a href="edit_quiz.php?id=<?= $q['quiz_id'] ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete(<?= $q['quiz_id'] ?>)" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center">No quizzes found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
