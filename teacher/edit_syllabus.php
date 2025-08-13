<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Get syllabus ID from query
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: view_syllabus.php");
    exit();
}
$syllabus_id = intval($_GET["id"]);

// Fetch classes
$classes = [];
$classQuery = "SELECT class_id, class_name FROM classes ORDER BY class_name ASC";
$classResult = $conn->query($classQuery);
if ($classResult && $classResult->num_rows > 0) {
    while ($row = $classResult->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Fetch subjects
$subjects = [];
$subjectQuery = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC";
$subjectResult = $conn->query($subjectQuery);
if ($subjectResult && $subjectResult->num_rows > 0) {
    while ($row = $subjectResult->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Fetch existing syllabus
$syllabus = null;
$stmt = $conn->prepare("SELECT * FROM syllabus WHERE syllabus_id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $syllabus_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: view_syllabus.php");
    exit();
}
$syllabus = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $class_id = $_POST["class_id"] ?? "";
    $subject_id = $_POST["subject_id"] ?? "";
    $details = $_POST["details"] ?? "";
    $upload_file = $syllabus["upload_file"]; // Keep existing unless replaced

    // Handle file upload
    if (isset($_FILES["upload_file"]) && $_FILES["upload_file"]["error"] == 0) {
        $allowed_ext = ["pdf", "doc", "docx"];
        $file_ext = strtolower(pathinfo($_FILES["upload_file"]["name"], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            $file_name = uniqid("syllabus_") . "." . $file_ext;
            $target_path = "../uploads/syllabus/" . $file_name;

            if (!is_dir("../uploads/syllabus")) {
                mkdir("../uploads/syllabus", 0777, true);
            }

            if (move_uploaded_file($_FILES["upload_file"]["tmp_name"], $target_path)) {
                $upload_file = $file_name;
            } else {
                $errorMsg = "File upload failed.";
            }
        } else {
            $errorMsg = "Invalid file type. Only PDF/DOC/DOCX allowed.";
        }
    }

    if (empty($errorMsg) && !empty($class_id) && !empty($subject_id) && !empty($details)) {
        $stmt = $conn->prepare("UPDATE syllabus SET class_id=?, subject_id=?, details=?, upload_file=? WHERE syllabus_id=? AND teacher_id=?");
        $stmt->bind_param("iissii", $class_id, $subject_id, $details, $upload_file, $syllabus_id, $teacher_id);

        if ($stmt->execute()) {
            $successMsg = "Syllabus updated successfully.";
            // Refresh data
            $syllabus["class_id"] = $class_id;
            $syllabus["subject_id"] = $subject_id;
            $syllabus["details"] = $details;
            $syllabus["upload_file"] = $upload_file;
        } else {
            $errorMsg = "Error updating syllabus.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Syllabus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('teacher_sidebar.php'); ?>

    <div class="container mt-5 mb-5">
        <div class="card mx-auto" style="max-width: 800px;">
            <div class="card-body">
                <h2 class="mb-4 text-center"><i class="fas fa-edit me-2"></i>Edit Syllabus</h2>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
                <?php elseif ($errorMsg): ?>
                    <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="class_id" class="form-label">Class</label>
                            <select class="form-select" id="class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['class_id'] ?>" <?= ($syllabus['class_id'] == $class['class_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['class_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['subject_id'] ?>" <?= ($syllabus['subject_id'] == $subject['subject_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subject['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="details" class="form-label">Syllabus Details</label>
                        <textarea class="form-control" id="details" name="details" rows="4" required><?= htmlspecialchars($syllabus['details']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="upload_file" class="form-label">Upload File (PDF/DOC/DOCX)</label>
                        <input type="file" class="form-control" id="upload_file" name="upload_file">
                        <?php if (!empty($syllabus['upload_file'])): ?>
                            <small>Current file: <a href="../uploads/syllabus/<?= htmlspecialchars($syllabus['upload_file']) ?>" target="_blank">View</a></small>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update Syllabus</button>
                        <a href="view_syllabus.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
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
