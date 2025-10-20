<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Student") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$student_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $message = trim($_POST['message'] ?? '');
    if ($message === '') {
        $errorMsg = "Feedback message cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (student_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $student_id, $message);
        if ($stmt->execute()) $successMsg = "Feedback submitted successfully.";
        else $errorMsg = "Failed to submit feedback.";
        $stmt->close();
    }
}

// Fetch feedbacks
$feedbacks = [];
$stmt = $conn->prepare("SELECT message, reply, created_at FROM feedback WHERE student_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $feedbacks[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Feedback</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Your Feedback</h2>

    <!-- Success/Error messages -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <!-- Feedback form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Submit New Feedback</div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <textarea name="message" class="form-control" rows="4" placeholder="Write your feedback here..." required></textarea>
                </div>
                <button type="submit" class="btn btn-success">Submit Feedback</button>
            </form>
        </div>
    </div>

    <!-- Feedback list -->
    <div class="card">
        <div class="card-header bg-info text-white">Your Feedback History</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Message</th>
                        <th>Reply</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($feedbacks): ?>
                        <?php foreach ($feedbacks as $fb): ?>
                            <tr>
                                <td><?= nl2br(htmlspecialchars($fb['message'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($fb['reply'] ?? 'No reply yet')) ?></td>
                                <td><?= htmlspecialchars($fb['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center">No feedback submitted yet.</td></tr>
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
