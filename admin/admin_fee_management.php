<?php
session_start();
include('config.php');

if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$successMsg = $errorMsg = "";

// Handle Delete Voucher
if (isset($_GET['delete_voucher'])) {
    $id = intval($_GET['delete_voucher']);
    $stmt = $conn->prepare("DELETE FROM fee_vouchers WHERE voucher_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $successMsg = "Fee voucher deleted successfully.";
    else $errorMsg = "Failed to delete fee voucher.";
    $stmt->close();
}

// Handle Delete Payment
if (isset($_GET['delete_payment'])) {
    $id = intval($_GET['delete_payment']);
    $stmt = $conn->prepare("DELETE FROM payments WHERE payment_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $successMsg = "Payment deleted successfully.";
    else $errorMsg = "Failed to delete payment.";
    $stmt->close();
}

// Fetch vouchers
$vouchers = [];
$stmt = $conn->prepare("
    SELECT fv.*, u.first_name, u.last_name
    FROM fee_vouchers fv
    JOIN users u ON fv.student_id = u.user_id
    ORDER BY fv.voucher_id DESC
");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $vouchers[] = $row;
$stmt->close();

// Fetch payments
$payments = [];
$stmt = $conn->prepare("
    SELECT p.*, fv.description, u.first_name, u.last_name
    FROM payments p
    JOIN fee_vouchers fv ON p.voucher_id = fv.voucher_id
    JOIN users u ON fv.student_id = u.user_id
    ORDER BY p.payment_id DESC
");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $payments[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('admin_sidebar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4 text-center">Fee Vouchers & Payments</h2>

    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="mb-3 text-end">
        <a href="add_fee_voucher.php" class="btn btn-primary">Add New Voucher</a>
    </div>

    <!-- Fee Vouchers Table -->
    <h4 class="mt-4">Fee Vouchers</h4>
    <div class="d-flex justify-content-end mb-2">
        <button id="generateCsvVouchers" class="btn btn-success">Generate CSV</button>
    </div>
    <div class="table-responsive">
        <table id="vouchersTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Student</th>
                    <th>Description</th>
                    <th>Month/Year</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($vouchers): ?>
                    <?php foreach ($vouchers as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['first_name'] . ' ' . $v['last_name']) ?></td>
                            <td><?= htmlspecialchars($v['description']) ?></td>
                            <td><?= htmlspecialchars($v['month_year']) ?></td>
                            <td><?= htmlspecialchars($v['amount']) ?></td>
                            <td><?= htmlspecialchars($v['due_date']) ?></td>
                            <td><?= htmlspecialchars($v['status']) ?></td>
                            <td>
                                <a href="admin_fee_management.php?delete_voucher=<?= $v['voucher_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this voucher?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No fee vouchers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Payments Table -->
    <h4 class="mt-5">Payments</h4>
    <div class="d-flex justify-content-end mb-2">
        <button id="generateCsvPayments" class="btn btn-success">Generate CSV</button>
    </div>
    <div class="table-responsive">
        <table id="paymentsTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Student</th>
                    <th>Voucher</th>
                    <th>Paid Amount</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                    <th>Voucher Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments): ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                            <td><?= htmlspecialchars($p['description']) ?></td>
                            <td><?= htmlspecialchars($p['paid_amount']) ?></td>
                            <td><?= htmlspecialchars($p['payment_date']) ?></td>
                            <td><?= htmlspecialchars($p['method']) ?></td>
                            <td>
                                <?php if ($p['voucher_image']): ?>
                                    <a href="../uploads/vouchers/<?= htmlspecialchars($p['voucher_image']) ?>" target="_blank">View</a>
                                <?php else: ?>N/A<?php endif; ?>
                            </td>
                            <td>
                                <a href="admin_fee_management.php?delete_payment=<?= $p['payment_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this payment?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No payments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// CSV generation function
function generateCSV(tableId, filename) {
    let table = document.getElementById(tableId);
    let rows = table.querySelectorAll('tr');
    let csv = [];
    rows.forEach(row => {
        let cols = row.querySelectorAll('th, td');
        let rowData = [];
        cols.forEach(col => {
            let data = col.innerText.replace(/"/g, '""');
            rowData.push('"' + data + '"');
        });
        csv.push(rowData.join(','));
    });
    let csvFile = new Blob([csv.join('\n')], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

document.getElementById('generateCsvVouchers').addEventListener('click', function() {
    generateCSV('vouchersTable', 'fee_vouchers.csv');
});

document.getElementById('generateCsvPayments').addEventListener('click', function() {
    generateCSV('paymentsTable', 'payments.csv');
});
</script>
</body>
</html>

<?php $conn->close(); ?>
