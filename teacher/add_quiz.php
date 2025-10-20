<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Fetch assigned classes
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

// Fetch subjects from assigned classes
$subjects = [];
$subjectQuery = "
    SELECT s.subject_id, s.subject_name 
    FROM subjects s
    WHERE s.class_id = (
        SELECT class_id FROM users WHERE user_id = ?
    )
";
$stmt = $conn->prepare($subjectQuery);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$subjectResult = $stmt->get_result();
while ($row = $subjectResult->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $class_id = $_POST["class_id"] ?? "";
    $subject_id = $_POST["subject_id"] ?? "";
    $question = $_POST["question"] ?? "";
    $option_one = $_POST["option_one"] ?? "";
    $option_two = $_POST["option_two"] ?? "";
    $option_three = $_POST["option_three"] ?? "";
    $option_four = $_POST["option_four"] ?? "";
    $correct_option = $_POST["correct_option"] ?? "";
    $total_marks = $_POST["total_marks"] ?? 0;

    if (!empty($class_id) && !empty($subject_id) && !empty($question) && !empty($option_one) && !empty($option_two) && !empty($option_three) && !empty($option_four) && !empty($correct_option)) {
        $stmt = $conn->prepare("
            INSERT INTO quizzes 
            (question, option_one, option_two, option_three, option_four, correct_option, subject_id, class_id, created_by, total_marks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssiidi", $question, $option_one, $option_two, $option_three, $option_four, $correct_option, $subject_id, $class_id, $teacher_id, $total_marks);

        if ($stmt->execute()) {
            $successMsg = "Quiz added successfully.";
        } else {
            $errorMsg = "Error adding quiz.";
        }
        $stmt->close();
    } else {
        $errorMsg = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Quiz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 900px;">
        <div class="card-body">
            <h2 class="mb-4 text-center">Add Quiz</h2>

            <?php if ($successMsg): ?>
                <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
            <?php elseif ($errorMsg): ?>
                <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Class</label>
                        <select class="form-select" name="class_id" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subject</label>
                        <select class="form-select" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Question</label>
                    <textarea class="form-control" name="question" rows="3" required></textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Option One</label>
                        <input type="text" class="form-control" name="option_one" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Option Two</label>
                        <input type="text" class="form-control" name="option_two" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Option Three</label>
                        <input type="text" class="form-control" name="option_three" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Option Four</label>
                        <input type="text" class="form-control" name="option_four" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Correct Option</label>
                        <select class="form-select" name="correct_option" required>
                            <option value="">Select Correct Option</option>
                            <option value="option_one">Option One</option>
                            <option value="option_two">Option Two</option>
                            <option value="option_three">Option Three</option>
                            <option value="option_four">Option Four</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Marks</label>
                        <input type="number" step="0.01" class="form-control" name="total_marks" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Save Quiz</button>
                    <a href="view_quiz.php" class="btn btn-secondary">View Quizzes</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>

<?php mysqli_close($conn); ?>
