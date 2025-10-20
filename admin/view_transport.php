<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$successMsg = $errorMsg = "";

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmtDel = $conn->prepare("DELETE FROM transport WHERE transport_id = ?");
    $stmtDel->bind_param("i", $id);
    if ($stmtDel->execute()) {
        $successMsg = "Transport deleted successfully.";
    } else {
        $errorMsg = "Failed to delete transport.";
    }
    $stmtDel->close();
}

// Fetch all transports with class info
$transports = [];
$stmt = $conn->prepare("
    SELECT t.transport_id, t.class_id, t.bus_number, t.driver_name, t.driver_phone, t.route_details,
           c.class_name, c.section
    FROM transport t
    JOIN classes c ON t.class_id = c.class_id
    ORDER BY t.transport_id DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transports[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Transports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('admin_sidebar.php'); ?>

    <div class="container mt-5">
        <h2 class="mb-4 text-center">Transport List</h2>

        <?php if ($successMsg): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="mb-3 text-end">
            <a href="add_transport.php" class="btn btn-primary">Add New Transport</a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Class</th>
                        <th>Bus Number</th>
                        <th>Driver Name</th>
                        <th>Driver Phone</th>
                        <th>Route Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transports): ?>
                        <?php foreach ($transports as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['class_name'] . ' - ' . $t['section']) ?></td>
                                <td><?= htmlspecialchars($t['bus_number']) ?></td>
                                <td><?= htmlspecialchars($t['driver_name']) ?></td>
                                <td><?= htmlspecialchars($t['driver_phone']) ?></td>
                                <td><?= htmlspecialchars($t['route_details']) ?></td>
                                <td>
                                    <a href="edit_transport.php?id=<?= $t['transport_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="view_transport.php?delete=<?= $t['transport_id'] ?>" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this transport?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No transports found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
