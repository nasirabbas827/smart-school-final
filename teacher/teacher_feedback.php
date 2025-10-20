<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');
$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Handle reply submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['feedback_id'])) {
    $feedback_id = intval($_POST['feedback_id']);
    $reply = trim($_POST['reply'] ?? '');

    if ($reply === '') {
        $errorMsg = "Reply cannot be empty.";
    } else {
        $stmt = $conn->prepare("UPDATE feedback SET reply=? WHERE feedback_id=?");
        $stmt->bind_param("si", $reply, $feedback_id);
        if ($stmt->execute()) $successMsg = "Reply submitted successfully.";
        else $errorMsg = "Failed to submit reply.";
        $stmt->close();
    }
}

// Fetch all feedback with student info
$feedbacks = [];
$query = "
    SELECT f.feedback_id, f.message, f.reply, f.created_at,
           u.first_name, u.last_name
    FROM feedback f
    JOIN users u ON f.student_id = u.user_id
    ORDER BY f.created_at DESC
";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) $feedbacks[] = $row;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Feedbacks</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Student Feedbacks</h2>

    <!-- Success/Error messages -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-info text-white">Feedback List</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Student</th>
                        <th>Message</th>
                        <th>Reply</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($feedbacks): ?>
                        <?php foreach ($feedbacks as $fb): ?>
                            <tr>
                                <td><?= htmlspecialchars($fb['first_name'] . ' ' . $fb['last_name']) ?></td>
                                <td><?= nl2br(htmlspecialchars($fb['message'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($fb['reply'] ?? 'No reply yet')) ?></td>
                                <td><?= htmlspecialchars($fb['created_at']) ?></td>
                                <td>
                                    <form method="POST" class="d-flex flex-column">
                                        <input type="hidden" name="feedback_id" value="<?= $fb['feedback_id'] ?>" />
                                        <textarea name="reply" class="form-control mb-2" rows="2" placeholder="Add/Update reply"><?= htmlspecialchars($fb['reply'] ?? '') ?></textarea>
                                        <button type="submit" class="btn btn-sm btn-success">Submit Reply</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No feedback available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
