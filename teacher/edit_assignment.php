<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$teacher_id = $_SESSION["user_id"];
$assignment_id = $_GET['id'] ?? 0;
$successMsg = $errorMsg = "";

// Fetch the assignment
$stmt = $conn->prepare("
    SELECT * FROM assignments 
    WHERE assignment_id = ? AND created_by = ?
");
$stmt->bind_param("ii", $assignment_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$assignment = $result->fetch_assoc();
$stmt->close();

if (!$assignment) {
    die("Assignment not found or unauthorized.");
}

// Fetch classes
$classes = [];
$classQuery = "
    SELECT c.class_id, c.class_name 
    FROM classes c
    JOIN users u ON u.class_id = c.class_id
    WHERE u.user_id = ?
";
$stmt = $conn->prepare($classQuery);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classResult = $stmt->get_result();
while ($row = $classResult->fetch_assoc()) {
    $classes[] = $row;
}
$stmt->close();

// Fetch subjects
$subjects = [];
$subjectQuery = "
    SELECT subject_id, subject_name
    FROM subjects
    WHERE class_id = ?
";
$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param("i", $assignment['class_id']);
$stmt->execute();
$subjectResult = $stmt->get_result();
while ($row = $subjectResult->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $class_id = $_POST["class_id"];
    $subject_id = $_POST["subject_id"];
    $title = $_POST["title"];
    $description = $_POST["description"];
    $due_date = $_POST["due_date"];
    $file_path = $assignment['file_path'];

    if (isset($_FILES["file_path"]) && $_FILES["file_path"]["error"] == 0) {
        $allowed_ext = ["pdf", "doc", "docx", "ppt", "pptx"];
        $file_ext = strtolower(pathinfo($_FILES["file_path"]["name"], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            $file_name = uniqid("assignment_") . "." . $file_ext;
            $target_path = "../uploads/assignments/" . $file_name;

            if (!is_dir("../uploads/assignments")) {
                mkdir("../uploads/assignments", 0777, true);
            }

            if (move_uploaded_file($_FILES["file_path"]["tmp_name"], $target_path)) {
                $file_path = $file_name;
            } else {
                $errorMsg = "File upload failed.";
            }
        } else {
            $errorMsg = "Invalid file type.";
        }
    }

    if (empty($errorMsg)) {
        $stmt = $conn->prepare("
            UPDATE assignments
            SET class_id = ?, subject_id = ?, title = ?, description = ?, 
                due_date = ?, file_path = ?
            WHERE assignment_id = ? AND created_by = ?
        ");
        $stmt->bind_param(
            "iissssii",
            $class_id, $subject_id, $title, $description,
            $due_date, $file_path, $assignment_id, $teacher_id
        );
        if ($stmt->execute()) {
            $successMsg = "Assignment updated successfully.";
        } else {
            $errorMsg = "Update failed.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 800px;">
        <div class="card-body">
            <h2 class="mb-4 text-center">Edit Assignment</h2>

            <?php if ($successMsg): ?>
                <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
            <?php elseif ($errorMsg): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Class</label>
                        <select class="form-select" name="class_id" required>
                            <option value="">Select</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['class_id'] ?>" <?= $class['class_id'] == $assignment['class_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Subject</label>
                        <select class="form-select" name="subject_id" required>
                            <option value="">Select</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['subject_id'] ?>" <?= $subject['subject_id'] == $assignment['subject_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($assignment['title']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="4" required><?= htmlspecialchars($assignment['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" class="form-control" name="due_date" value="<?= $assignment['due_date'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Upload New File (Optional)</label>
                    <input type="file" class="form-control" name="file_path">
                    <?php if ($assignment['file_path']): ?>
                        <small>Current: <?= htmlspecialchars($assignment['file_path']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="view_assignments.php" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
