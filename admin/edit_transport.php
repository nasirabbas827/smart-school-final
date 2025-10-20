<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$successMsg = $errorMsg = "";
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    header("Location: view_transport.php");
    exit;
}

// Fetch transport data
$stmt = $conn->prepare("SELECT * FROM transport WHERE transport_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$transport = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch classes
$classes = [];
$classStmt = $conn->prepare("SELECT class_id, class_name, section FROM classes ORDER BY class_name ASC");
$classStmt->execute();
$classResult = $classStmt->get_result();
while ($row = $classResult->fetch_assoc()) {
    $classes[] = $row;
}
$classStmt->close();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'];
    $bus_number = trim($_POST['bus_number']);
    $driver_name = trim($_POST['driver_name']);
    $driver_phone = trim($_POST['driver_phone']);
    $route_details = trim($_POST['route_details']);

    if (empty($class_id) || empty($bus_number) || empty($driver_name) || empty($driver_phone) || empty($route_details)) {
        $errorMsg = "All fields are required.";
    } else {
        $stmt = $conn->prepare("UPDATE transport SET class_id=?, bus_number=?, driver_name=?, driver_phone=?, route_details=? WHERE transport_id=?");
        $stmt->bind_param("issssi", $class_id, $bus_number, $driver_name, $driver_phone, $route_details, $id);
        if ($stmt->execute()) {
            $successMsg = "Transport updated successfully.";
        } else {
            $errorMsg = "Failed to update transport.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Transport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('admin_sidebar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4 text-center">Edit Transport</h2>

    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="card mx-auto shadow" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Class</label>
                    <select name="class_id" class="form-select" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['class_id'] ?>" <?= ($c['class_id'] == $transport['class_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['class_name'] . ' - ' . $c['section']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bus Number</label>
                    <input type="text" class="form-control" name="bus_number" value="<?= htmlspecialchars($transport['bus_number']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Driver Name</label>
                    <input type="text" class="form-control" name="driver_name" value="<?= htmlspecialchars($transport['driver_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Driver Phone</label>
                    <input type="text" class="form-control" name="driver_phone" value="<?= htmlspecialchars($transport['driver_phone']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Route Details</label>
                    <textarea name="route_details" class="form-control" rows="3" required><?= htmlspecialchars($transport['route_details']) ?></textarea>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Update Transport</button>
                    <a href="view_transport.php" class="btn btn-secondary">Back to List</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
