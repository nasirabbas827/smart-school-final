<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Student") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$student_id = $_SESSION["user_id"];

// Fetch student info
$stmt = $conn->prepare("SELECT first_name, last_name, class_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$student_name = $student['first_name'] . " " . $student['last_name'];
$student_class_id = $student['class_id'];
$stmt->close();

// Fetch subjects for the student class
$subjects = [];
$subStmt = $conn->prepare("SELECT subject_id, subject_name FROM subjects WHERE class_id = ?");
$subStmt->bind_param("i", $student_class_id);
$subStmt->execute();
$subResult = $subStmt->get_result();
while ($row = $subResult->fetch_assoc()) {
    $subjects[] = $row;
}
$subStmt->close();

// Selected subject
$selected_subject_id = $_POST['subject_id'] ?? 0;

// Fetch quizzes if subject selected
$quizzes = [];
if ($selected_subject_id) {
    $qStmt = $conn->prepare("SELECT * FROM quizzes WHERE class_id = ? AND subject_id = ?");
    $qStmt->bind_param("ii", $student_class_id, $selected_subject_id);
    $qStmt->execute();
    $qResult = $qStmt->get_result();
    while ($row = $qResult->fetch_assoc()) {
        $quizzes[] = $row;
    }
    $qStmt->close();
}

// Handle quiz attempt
$feedbacks = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attempt_quiz_id'])) {
    $attempt_quiz_id = $_POST['attempt_quiz_id'];
    $selected_option = $_POST['option'] ?? '';

    // Fetch quiz data
    $stmt = $conn->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
    $stmt->bind_param("i", $attempt_quiz_id);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($quiz) {
        $correct_column = $quiz['correct_option']; // e.g., option_one
        $correct_answer = $quiz[$correct_column];

        if ($selected_option === $correct_column) {
            $feedbacks[$quiz['quiz_id']] = "Correct! You scored {$quiz['total_marks']} marks.";
        } else {
            $feedbacks[$quiz['quiz_id']] = "Incorrect. Correct answer: $correct_answer. You scored 0 marks.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attempt Quiz</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Attempt Quiz - <?= htmlspecialchars($student_name) ?></h2>

    <!-- Select Subject -->
    <form method="POST" class="mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <label class="form-label">Select Subject</label>
                <select name="subject_id" class="form-select" required>
                    <option value="">-- Choose Subject --</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= $sub['subject_id'] ?>" <?= ($sub['subject_id'] == $selected_subject_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub['subject_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary mt-4">Load Quizzes</button>
            </div>
        </div>
    </form>

    <!-- List Quizzes -->
    <?php if ($selected_subject_id && $quizzes): ?>
        <?php foreach ($quizzes as $quiz): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <?= htmlspecialchars($quiz['question']) ?>
                </div>
                <div class="card-body">
                    <?php if (!isset($feedbacks[$quiz['quiz_id']])): ?>
<form method="POST">
    <input type="hidden" name="subject_id" value="<?= $selected_subject_id ?>">
    <input type="hidden" name="attempt_quiz_id" value="<?= $quiz['quiz_id'] ?>">
    <?php
        $options = ['option_one', 'option_two', 'option_three', 'option_four'];
        foreach ($options as $opt):
    ?>
        <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="option" id="<?= $quiz['quiz_id'].'_'.$opt ?>" value="<?= $opt ?>" required>
            <label class="form-check-label" for="<?= $quiz['quiz_id'].'_'.$opt ?>">
                <?= htmlspecialchars($quiz[$opt]) ?>
            </label>
        </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-success mt-2">Submit Answer</button>
</form>

                    <?php else: ?>
                        <div class="alert <?= strpos($feedbacks[$quiz['quiz_id']], 'Correct') !== false ? 'alert-success' : 'alert-danger' ?>">
                            <?= $feedbacks[$quiz['quiz_id']] ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php elseif($selected_subject_id): ?>
        <div class="alert alert-warning">No quizzes found for this subject.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
