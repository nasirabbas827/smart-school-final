<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Check assignment_id
$assignment_id = intval($_GET['assignment_id'] ?? 0);
if (!$assignment_id) {
    die("Invalid Assignment.");
}

// Handle grading
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submission_id'])) {
    $submission_id = intval($_POST['submission_id']);
    $grade = $_POST['grade'] ?? "";
    $feedback = $_POST['feedback'] ?? "";

    $stmt = $conn->prepare("UPDATE assignment_submissions SET grade=?, feedback=? WHERE submission_id=?");
    $stmt->bind_param("ssi", $grade, $feedback, $submission_id);
    if ($stmt->execute()) $successMsg = "Grade & feedback updated successfully.";
    else $errorMsg = "Failed to update.";
    $stmt->close();
}

// Fetch Assignment Info
$stmt = $conn->prepare("SELECT title FROM assignments WHERE assignment_id=? AND created_by=?");
$stmt->bind_param("ii", $assignment_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Assignment not found or unauthorized.");
$assignment = $result->fetch_assoc();
$stmt->close();

// Fetch Submissions
$subs = [];
$subStmt = $conn->prepare("
    SELECT s.submission_id, s.file_path, s.submitted_at, s.grade, s.feedback,
           u.first_name, u.last_name
    FROM assignment_submissions s
    JOIN users u ON s.student_id = u.user_id
    WHERE s.assignment_id=?
");
$subStmt->bind_param("i", $assignment_id);
$subStmt->execute();
$res = $subStmt->get_result();
while ($row = $res->fetch_assoc()) $subs[] = $row;
$subStmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>View Submissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Submissions for: <?= htmlspecialchars($assignment['title']) ?></h2>

    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Student</th>
                    <th>File</th>
                    <th>Submitted At</th>
                    <th>Grade</th>
                    <th>Feedback</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($subs): ?>
                    <?php foreach ($subs as $sub): ?>
                        <tr>
                            <td><?= htmlspecialchars($sub['first_name'] . " " . $sub['last_name']) ?></td>
                            <td><a href="../uploads/assignment_submissions/<?= htmlspecialchars($sub['file_path']) ?>" target="_blank">Download</a></td>
                            <td><?= htmlspecialchars($sub['submitted_at']) ?></td>
                            <form method="POST">
                                <input type="hidden" name="submission_id" value="<?= $sub['submission_id'] ?>">
                                <td><input type="text" name="grade" class="form-control" value="<?= htmlspecialchars($sub['grade']) ?>"></td>
                                <td><input type="text" name="feedback" class="form-control" value="<?= htmlspecialchars($sub['feedback']) ?>"></td>
                                <td><button type="submit" class="btn btn-sm btn-success">Update</button></td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No submissions yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
