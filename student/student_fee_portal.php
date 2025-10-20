<?php
session_start();
include('../config.php');

if (!isset($_SESSION["user_id"]) || $_SESSION["usertype"] !== "Student") {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION["user_id"];
$successMsg = $errorMsg = "";

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['voucher_id'])) {
    $voucher_id = intval($_POST['voucher_id']);
    $paid_amount = floatval($_POST['paid_amount']);
    $method = trim($_POST['method']);
    $voucher_image = '';

    if (isset($_FILES['voucher_image']) && $_FILES['voucher_image']['error'] == 0) {
        $ext = pathinfo($_FILES['voucher_image']['name'], PATHINFO_EXTENSION);
        $voucher_image = 'voucher_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['voucher_image']['tmp_name'], '../uploads/vouchers/' . $voucher_image);
    }

    $stmt = $conn->prepare("INSERT INTO payments (voucher_id, paid_amount, method, voucher_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $voucher_id, $paid_amount, $method, $voucher_image);
    if ($stmt->execute()) {
        // Update voucher status
        $stmt2 = $conn->prepare("UPDATE fee_vouchers SET status='Paid' WHERE voucher_id=?");
        $stmt2->bind_param("i", $voucher_id);
        $stmt2->execute();
        $stmt2->close();

        $successMsg = "Payment submitted successfully.";
    } else {
        $errorMsg = "Payment submission failed.";
    }
    $stmt->close();
}

// Fetch student's vouchers
$vouchers = [];
$stmt = $conn->prepare("SELECT * FROM fee_vouchers WHERE student_id = ? ORDER BY voucher_id DESC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $vouchers[] = $row;
}
$stmt->close();

// Fetch student's payments
$payments = [];
$stmt = $conn->prepare("
    SELECT p.*, fv.description
    FROM payments p
    JOIN fee_vouchers fv ON p.voucher_id = fv.voucher_id
    WHERE fv.student_id = ?
    ORDER BY p.payment_id DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $payments[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fee Portal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('student_sidebar.php'); ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4 text-center">My Fees & Payments</h2>

    <?php if ($successMsg): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
    <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <h4>Fee Vouchers</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Description</th>
                    <th>Month/Year</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($vouchers): ?>
                    <?php foreach ($vouchers as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['description']) ?></td>
                            <td><?= htmlspecialchars($v['month_year']) ?></td>
                            <td><?= htmlspecialchars($v['amount']) ?></td>
                            <td><?= htmlspecialchars($v['due_date']) ?></td>
                            <td><?= htmlspecialchars($v['status']) ?></td>
                            <td>
                                <?php if ($v['status'] === 'Pending'): ?>
                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#payModal<?= $v['voucher_id'] ?>">Pay</button>
                                <?php else: ?>
                                    Paid
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Payment Modal -->
                        <div class="modal fade" id="payModal<?= $v['voucher_id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Pay Voucher: <?= htmlspecialchars($v['description']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="voucher_id" value="<?= $v['voucher_id'] ?>">
                                            <div class="mb-3">
                                                <label>Amount</label>
                                                <input type="number" name="paid_amount" class="form-control" value="<?= $v['amount'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label>Payment Method</label>
                                                <select name="method" class="form-select" required>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                    <option value="Online">Online</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label>Upload Voucher Image (optional)</label>
                                                <input type="file" name="voucher_image" class="form-control">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">Pay Now</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No fee vouchers available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h4 class="mt-5">Payment History</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Voucher</th>
                    <th>Paid Amount</th>
                    <th>Payment Date</th>
                    <th>Method</th>
                    <th>Voucher Image</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments): ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['description']) ?></td>
                            <td><?= htmlspecialchars($p['paid_amount']) ?></td>
                            <td><?= htmlspecialchars($p['payment_date']) ?></td>
                            <td><?= htmlspecialchars($p['method']) ?></td>
                            <td>
                                <?php if ($p['voucher_image']): ?>
                                    <a href="../uploads/vouchers/<?= htmlspecialchars($p['voucher_image']) ?>" target="_blank">View</a>
                                <?php else: ?>N/A<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">No payments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
