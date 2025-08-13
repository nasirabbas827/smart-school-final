<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

include('../config.php');

$teacher_id = $_SESSION["user_id"];

$successMsg = $errorMsg = "";

// Handle Create / Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $announcement_id = isset($_POST['announcement_id']) ? intval($_POST['announcement_id']) : 0;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (empty($title) || empty($description)) {
        $errorMsg = "All fields are required.";
    } else {
        if ($announcement_id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE announcements SET title=?, description=? WHERE announcement_id=?");
            $stmt->bind_param("ssi", $title, $description, $announcement_id);
            if ($stmt->execute()) {
                $successMsg = "Announcement updated successfully.";
            } else {
                $errorMsg = "Error updating: " . $conn->error;
            }
            $stmt->close();
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO announcements (title, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $title, $description);
            if ($stmt->execute()) {
                $successMsg = "Announcement added successfully.";
            } else {
                $errorMsg = "Error adding: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM announcements WHERE announcement_id=$id");
    header("Location: announcements.php");
    exit;
}

// Fetch announcements
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Announcements</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('teacher_sidebar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4 text-center"><i class="fas fa-bullhorn me-2"></i>Manage Announcements</h2>

    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="mb-3 text-end">
        <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus me-1"></i>Add Announcement</button>
    </div>

    <table class="table table-bordered table-striped bg-white shadow">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($announcements as $a): ?>
                <tr>
                    <td><?= $a['announcement_id'] ?></td>
                    <td><?= htmlspecialchars($a['title']) ?></td>
                    <td><?= htmlspecialchars($a['description']) ?></td>
                    <td><?= $a['created_at'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="openModal(<?= $a['announcement_id'] ?>, '<?= htmlspecialchars($a['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['description'], ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                        <a href="?delete=<?= $a['announcement_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this announcement?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add/Edit Announcement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="announcement_id" id="announcement_id">
                <div class="mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" name="title" id="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openModal(id = '', title = '', description = '') {
    document.getElementById('announcement_id').value = id;
    document.getElementById('title').value = title;
    document.getElementById('description').value = description;
    new bootstrap.Modal(document.getElementById('announcementModal')).show();
}
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
