<?php
session_start();
if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["usertype"], ["Student","Teacher"])) {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$user_id = $_SESSION["user_id"];
$role = $_SESSION["usertype"];
$successMsg = $errorMsg = "";

// Get class_id
if($role === "Student") {
    $stmt = $conn->prepare("SELECT class_id, first_name, last_name FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $class_id = $student['class_id'];
    $user_name = $student['first_name'] . " " . $student['last_name'];
    $stmt->close();
} else { // Teacher
    $stmt = $conn->prepare("SELECT class_id, first_name, last_name FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $teacher = $stmt->get_result()->fetch_assoc();
    $class_id = $teacher['class_id'];
    $user_name = "Teacher " . $teacher['first_name'] . " " . $teacher['last_name'];
    $stmt->close();
}

// Handle new message submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty(trim($_POST['message']))) {
    $message = trim($_POST['message']);
    $stmt = $conn->prepare("INSERT INTO class_messages (class_id, sender_id, sender_role, message) VALUES (?,?,?,?)");
    $stmt->bind_param("iiss", $class_id, $user_id, $role, $message);
    if ($stmt->execute()) $successMsg = "Message sent successfully.";
    else $errorMsg = "Failed to send message.";
    $stmt->close();
}

// Fetch messages
$messages = [];
$stmt = $conn->prepare("SELECT m.*, u.first_name, u.last_name 
                        FROM class_messages m 
                        JOIN users u ON m.sender_id=u.user_id 
                        WHERE m.class_id=? 
                        ORDER BY m.sent_at ASC");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) $messages[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Class Messages</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
.message-box { max-height: 400px; overflow-y: auto; padding: 10px; border: 1px solid #ccc; background: #f9f9f9; }
.message-sent { text-align: right; background: #d1e7dd; padding: 5px 10px; border-radius: 10px; margin: 5px 0; }
.message-received { text-align: left; background: #fff3cd; padding: 5px 10px; border-radius: 10px; margin: 5px 0; }
.sender { font-weight: bold; }
.time { font-size: 0.8rem; color: gray; }
</style>
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php
if($role === "Student") include('student_sidebar.php'); 
else include('teacher_sidebar.php'); 
?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">Class Messages - <?= htmlspecialchars($user_name) ?></h2>

    <?php if($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body message-box">
            <?php if($messages): ?>
                <?php foreach($messages as $msg): ?>
                    <?php 
                        $isSender = ($msg['sender_id'] == $user_id);
                        $className = $isSender ? 'message-sent' : 'message-received';
                        $senderName = $isSender ? 'You' : ($msg['sender_role']=='Teacher'?'Teacher ':'') . $msg['first_name'] . ' ' . $msg['last_name'];
                    ?>
                    <div class="<?= $className ?>">
                        <div class="sender"><?= htmlspecialchars($senderName) ?></div>
                        <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                        <div class="time"><?= htmlspecialchars($msg['sent_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">No messages yet. Start the conversation!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Send Message Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <textarea name="message" class="form-control" rows="3" placeholder="Type your message..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
