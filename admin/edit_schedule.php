<?php
session_start();
include('config.php');

// Check admin login
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$successMsg = $errorMsg = "";

// Get ID
if (!isset($_GET['id'])) {
    header("Location: manage_schedules.php");
    exit;
}
$timetable_id = intval($_GET['id']);

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM timetables WHERE timetable_id = ?");
$stmt->bind_param("i", $timetable_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: manage_schedules.php");
    exit;
}
$schedule = $result->fetch_assoc();
$stmt->close();

// Fetch dropdown data
$classes = $conn->query("SELECT class_id, class_name, section FROM classes ORDER BY class_name ASC")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC")->fetch_all(MYSQLI_ASSOC);
$teachers = $conn->query("SELECT user_id, first_name, last_name FROM users WHERE usertype='teacher' ORDER BY first_name ASC")->fetch_all(MYSQLI_ASSOC);

// Handle Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $class_id = intval($_POST['class_id']);
    $subject_id = intval($_POST['subject_id']);
    $teacher_id = intval($_POST['teacher_id']);
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if (empty($class_id) || empty($subject_id) || empty($teacher_id) || empty($day_of_week) || empty($start_time) || empty($end_time)) {
        $errorMsg = "All fields are required.";
    } else {
        $stmt = $conn->prepare("UPDATE timetables SET class_id=?, subject_id=?, teacher_id=?, day_of_week=?, start_time=?, end_time=? WHERE timetable_id=?");
        $stmt->bind_param("iiisssi", $class_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time, $timetable_id);
        if ($stmt->execute()) {
            $successMsg = "Schedule updated successfully.";
        } else {
            $errorMsg = "Error updating schedule: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Schedule</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('admin_sidebar.php'); ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center"><i class="fas fa-edit me-2"></i>Edit Schedule</h2>

        <?php if ($successMsg): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="card mx-auto shadow" style="max-width: 800px;">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="class_id" class="form-label">Class</label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['class_id'] ?>" <?= ($class['class_id'] == $schedule['class_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['class_name'] . ' - ' . $class['section']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['subject_id'] ?>" <?= ($subject['subject_id'] == $schedule['subject_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="teacher_id" class="form-label">Teacher</label>
                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['user_id'] ?>" <?= ($teacher['user_id'] == $schedule['teacher_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="day_of_week" class="form-label">Day</label>
                            <select class="form-select" id="day_of_week" name="day_of_week" required>
                                <?php
                                $days = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday'];
                                foreach ($days as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= ($schedule['day_of_week'] == $key) ? 'selected' : '' ?>><?= $val ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" value="<?= $schedule['start_time'] ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" value="<?= $schedule['end_time'] ?>" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Schedule</button>
                        <a href="manage_schedules.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
