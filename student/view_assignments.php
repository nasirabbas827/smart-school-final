<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Student") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$student_id = $_SESSION["user_id"];

// Get student class
$stmt = $conn->prepare("SELECT first_name, last_name, class_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_name = $student['first_name'] . " " . $student['last_name'];
$class_id = $student['class_id'];
$stmt->close();

// Handle submission
$successMsg = $errorMsg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['assignment_id'])) {
    $assignment_id = $_POST['assignment_id'];

    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] == 0) {
        $allowed_ext = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
        $file_ext = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            $file_name = uniqid("submission_") . "." . $file_ext;
            $target_path = "../uploads/assignment_submissions/" . $file_name;

            if (!is_dir("../uploads/assignment_submissions")) {
                mkdir("../uploads/assignment_submissions", 0777, true);
            }

            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $target_path)) {
                $stmt = $conn->prepare("INSERT INTO assignment_submissions (assignment_id, student_id, file_path) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $assignment_id, $student_id, $file_name);
                if ($stmt->execute()) {
                    $successMsg = "Assignment submitted successfully.";
                } else {
                    $errorMsg = "Failed to submit assignment.";
                }
                $stmt->close();
            } else {
                $errorMsg = "File upload failed.";
            }
        } else {
            $errorMsg = "Invalid file type.";
        }
    } else {
        $errorMsg = "Please select a file to submit.";
    }
}

// Fetch Assignments
$assignments = [];
$asStmt = $conn->prepare("SELECT a.assignment_id, a.title, a.description, a.file_path, a.due_date, s.subject_name, u.first_name AS teacher_first, u.last_name AS teacher_last 
                          FROM assignments a
                          JOIN subjects s ON a.subject_id = s.subject_id
                          JOIN users u ON a.teacher_id = u.user_id
                          WHERE a.class_id = ? ORDER BY a.due_date DESC");
$asStmt->bind_param("i", $class_id);
$asStmt->execute();
$asResult = $asStmt->get_result();
while ($row = $asResult->fetch_assoc()) {
    // Check submission
    $subStmt = $conn->prepare("SELECT submission_id, file_path, grade, feedback FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
    $subStmt->bind_param("ii", $row['assignment_id'], $student_id);
    $subStmt->execute();
    $subResult = $subStmt->get_result();
    if ($subResult->num_rows > 0) {
        $row['submission'] = $subResult->fetch_assoc();
    } else {
        $row['submission'] = null;
    }
    $subStmt->close();

    $assignments[] = $row;
}
$asStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assignments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Assignments - <?= htmlspecialchars($student_name) ?></h2>

    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-success text-white">Assignments List</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Subject</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>File</th>
                        <th>Due Date</th>
                        <th>Teacher</th>
                        <th>Submission</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assignments): ?>
                        <?php foreach ($assignments as $as): ?>
                            <tr>
                                <td><?= htmlspecialchars($as['subject_name']) ?></td>
                                <td><?= htmlspecialchars($as['title']) ?></td>
                                <td><?= htmlspecialchars($as['description']) ?></td>
                                <td>
                                    <?php if ($as['file_path']): ?>
                                        <a href="../uploads/assignments/<?= htmlspecialchars($as['file_path']) ?>" target="_blank">Download</a>
                                    <?php else: ?>N/A<?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($as['due_date']) ?></td>
                                <td><?= htmlspecialchars($as['teacher_first'] . " " . $as['teacher_last']) ?></td>
                                <td>
                                    <?php
                                    $today = date('Y-m-d');
                                    if ($as['submission']): 
                                        echo "Grade: " . htmlspecialchars($as['submission']['grade'] ?? '-') . "<br>";
                                        echo "Feedback: " . htmlspecialchars($as['submission']['feedback'] ?? '-');
                                    elseif ($as['due_date'] >= $today): ?>
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="assignment_id" value="<?= $as['assignment_id'] ?>">
                                            <input type="file" name="submission_file" required>
                                            <button type="submit" class="btn btn-sm btn-primary mt-1">Submit</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-danger">Past Due</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">No assignments available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
