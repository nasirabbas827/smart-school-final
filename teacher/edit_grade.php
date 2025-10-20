<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Validate grade id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_grades.php");
    exit();
}
$grade_id = intval($_GET['id']);

// Fetch grade data
$query = "
    SELECT * FROM grades 
    WHERE grade_id = ? AND entered_by = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $grade_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$grade = $result->fetch_assoc();
$stmt->close();

if (!$grade) {
    header("Location: view_grades.php");
    exit();
}

// Fetch class of this teacher
$classQuery = "
    SELECT class_id 
    FROM classes 
    WHERE class_id = (SELECT class_id FROM users WHERE user_id = ?)
";
$stmt = $conn->prepare($classQuery);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classResult = $stmt->get_result();
$class = $classResult->fetch_assoc();
$stmt->close();

// Fetch students of the class
$students = [];
$studentQuery = "
    SELECT user_id, first_name, last_name 
    FROM users 
    WHERE usertype = 'Student' AND class_id = ?
";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $class['class_id']);
$stmt->execute();
$res1 = $stmt->get_result();
while ($row = $res1->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// Fetch subjects of that class
$subjects = [];
$subjectQuery = "
    SELECT subject_id, subject_name 
    FROM subjects 
    WHERE class_id = ?
";
$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param("i", $class['class_id']);
$stmt->execute();
$res2 = $stmt->get_result();
while ($row = $res2->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Update grade form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST["student_id"];
    $subject_id = $_POST["subject_id"];
    $exam_type = $_POST["exam_type"];
    $term = $_POST["term"];
    $marks_obtained = $_POST["marks_obtained"];
    $max_marks = $_POST["max_marks"];
    $grade_val = $_POST["grade"] ?? null;
    $comments = $_POST["comments"] ?? null;

    $stmt = $conn->prepare("
        UPDATE grades 
        SET student_id = ?, subject_id = ?, exam_type = ?, term = ?, 
            marks_obtained = ?, max_marks = ?, grade = ?, comments = ?
        WHERE grade_id = ? AND entered_by = ?
    ");
    $stmt->bind_param("iissddssii",
        $student_id, $subject_id, $exam_type, $term,
        $marks_obtained, $max_marks, $grade_val, $comments,
        $grade_id, $teacher_id
    );

    if ($stmt->execute()) {
        $successMsg = "Grade updated successfully.";
    } else {
        $errorMsg = "Failed to update grade.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Grade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto" style="max-width:900px;">
        <div class="card-body">
            <h2 class="mb-4 text-center">Edit Student Grade</h2>

            <?php if ($successMsg): ?>
                <div class="alert alert-success text-center"><?= $successMsg ?></div>
            <?php elseif ($errorMsg): ?>
                <div class="alert alert-danger text-center"><?= $errorMsg ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Student</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $stu): ?>
                                <option value="<?= $stu['user_id'] ?>"
                                    <?= ($stu['user_id'] == $grade['student_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Subject</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['subject_id'] ?>"
                                    <?= ($sub['subject_id'] == $grade['subject_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sub['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Exam Type</label>
                        <input type="text" name="exam_type" class="form-control" required
                               value="<?= htmlspecialchars($grade['exam_type']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label>Term</label>
                        <input type="text" name="term" class="form-control" required
                               value="<?= htmlspecialchars($grade['term']) ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Marks Obtained</label>
                        <input type="number" step="0.01" name="marks_obtained" class="form-control" required
                               value="<?= htmlspecialchars($grade['marks_obtained']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label>Max Marks</label>
                        <input type="number" step="0.01" name="max_marks" class="form-control" required
                               value="<?= htmlspecialchars($grade['max_marks']) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label>Grade (optional)</label>
                    <input type="text" name="grade" class="form-control"
                           value="<?= htmlspecialchars($grade['grade'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Comments (optional)</label>
                    <textarea name="comments" class="form-control" rows="3"><?= htmlspecialchars($grade['comments'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-success">Update Grade</button>
                    <a href="view_grades.php" class="btn btn-secondary">Back to Grades</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
