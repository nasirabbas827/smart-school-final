<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$teacher_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// ✅ Fetch only the class assigned to the teacher
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
if ($classResult && $classResult->num_rows > 0) {
    while ($row = $classResult->fetch_assoc()) {
        $classes[] = $row;
    }
}
$stmt->close();

// ✅ Fetch only subjects that belong to the teacher’s class
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
if ($subjectResult && $subjectResult->num_rows > 0) {
    while ($row = $subjectResult->fetch_assoc()) {
        $subjects[] = $row;
    }
}
$stmt->close();

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $class_id = $_POST["class_id"] ?? "";
    $subject_id = $_POST["subject_id"] ?? "";
    $details = $_POST["details"] ?? "";
    $upload_file = "";

    // File upload
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
        $stmt = $conn->prepare("INSERT INTO syllabus (class_id, subject_id, teacher_id, details, upload_file) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $class_id, $subject_id, $teacher_id, $details, $upload_file);

        if ($stmt->execute()) {
            $successMsg = "Syllabus added successfully.";
        } else {
            $errorMsg = "Error adding syllabus.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Syllabus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('teacher_sidebar.php'); ?>

    <div class="container mt-5 mb-5">
        <div class="card mx-auto" style="max-width: 800px;">
            <div class="card-body">
                <h2 class="mb-4 text-center"><i class="fas fa-book me-2"></i>Add Syllabus</h2>

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
                                    <option value="<?= $class['class_id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-select" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="details" class="form-label">Syllabus Details</label>
                        <textarea class="form-control" id="details" name="details" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="upload_file" class="form-label">Upload File (PDF/DOC/DOCX)</label>
                        <input type="file" class="form-control" id="upload_file" name="upload_file">
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Syllabus</button>
                        <a href="view_syllabus.php" class="btn btn-secondary"><i class="fas fa-eye me-1"></i>View Syllabus</a>
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
