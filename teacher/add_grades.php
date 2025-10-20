<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

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

// Fetch students of that class
$students = [];
if ($class) {
    $studentQuery = "
        SELECT user_id, first_name, last_name 
        FROM users 
        WHERE usertype = 'Student' AND class_id = ?
    ";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("i", $class['class_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

// Fetch subjects taught in that class
$subjects = [];
$subjectQuery = "SELECT subject_id, subject_name FROM subjects WHERE class_id = ?";
$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param("i", $class['class_id']);
$stmt->execute();
$subjectResult = $stmt->get_result();
while ($row = $subjectResult->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Insert grade
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = $_POST["student_id"];
    $subject_id = $_POST["subject_id"];
    $exam_type = $_POST["exam_type"];
    $term = $_POST["term"];
    $marks_obtained = $_POST["marks_obtained"];
    $max_marks = $_POST["max_marks"];
    $grade = $_POST["grade"] ?? null;
    $comments = $_POST["comments"] ?? null;
    $class_id = $class['class_id'];

    $stmt = $conn->prepare("
        INSERT INTO grades 
        (student_id, subject_id, class_id, exam_type, term, marks_obtained, max_marks, grade, comments, entered_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiissddssi", $student_id, $subject_id, $class_id, $exam_type, $term, $marks_obtained, $max_marks, $grade, $comments, $teacher_id);

    if ($stmt->execute()) {
        $successMsg = "Grade added successfully.";
    } else {
        $errorMsg = "Failed to add grade.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Grade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5">
    <div class="card mx-auto" style="max-width:900px;">
        <div class="card-body">
            <h2 class="mb-4 text-center">Add Student Grade</h2>

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
                                <option value="<?= $stu['user_id'] ?>">
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
                                <option value="<?= $sub['subject_id'] ?>">
                                    <?= htmlspecialchars($sub['subject_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Exam Type</label>
                        <input type="text" name="exam_type" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label>Term</label>
                        <input type="text" name="term" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Marks Obtained</label>
                        <input type="number" step="0.01" name="marks_obtained" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label>Max Marks</label>
                        <input type="number" step="0.01" name="max_marks" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Grade (optional)</label>
                    <input type="text" name="grade" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Comments (optional)</label>
                    <textarea name="comments" class="form-control" rows="3"></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-success">Save Grade</button>
                    <a href="view_grades.php" class="btn btn-secondary">View Grades</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
