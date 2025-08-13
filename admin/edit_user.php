<?php
session_start();
include('config.php');

// Check admin login
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$successMsg = $errorMsg = "";

// Fetch classes
$classes = $conn->query("SELECT class_id, class_name, section FROM classes ORDER BY class_name ASC");

// Get user data
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}
$user_id = intval($_GET['id']);
$user = $conn->query("SELECT * FROM users WHERE user_id = $user_id")->fetch_assoc();
if (!$user) {
    header("Location: manage_users.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usertype = trim($_POST['usertype']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $gender = trim($_POST['gender']);
    $dob = trim($_POST['dob']);
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;

    // Handle optional password change
    $password_sql = "";
    if (!empty($_POST['password'])) {
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $password_sql = ", password_hash = '$password_hash'";
    }

    // Handle image upload
    $profile_picture_sql = "";
    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "images/";
        $file_name = time() . "_" . basename($_FILES['profile_picture']['name']);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            // Remove old picture
            if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                unlink($user['profile_picture']);
            }
            $profile_picture_sql = ", profile_picture = '$target_file'";
        } else {
            $errorMsg = "Failed to upload new profile picture.";
        }
    }

    if (empty($errorMsg)) {
        $sql = "
            UPDATE users
            SET usertype = '$usertype',
                first_name = '$first_name',
                last_name = '$last_name',
                email = '$email',
                gender = '$gender',
                dob = '$dob',
                contact_number = '$contact_number',
                address = '$address',
                class_id = " . ($class_id ? $class_id : "NULL") . ",
                updated_at = NOW()
                $password_sql
                $profile_picture_sql
            WHERE user_id = $user_id
        ";
        if ($conn->query($sql)) {
            $successMsg = "User updated successfully.";
            $user = $conn->query("SELECT * FROM users WHERE user_id = $user_id")->fetch_assoc(); // refresh data
        } else {
            $errorMsg = "Error updating user: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('admin_sidebar.php'); ?>

    <div class="container mt-5 mb-5">
        <h2 class="mb-4 text-center"><i class="fas fa-user-edit me-2"></i>Edit User</h2>

        <?php if ($successMsg): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="card mx-auto shadow" style="max-width: 900px;">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">User Type</label>
                            <select name="usertype" class="form-select" required>
                                <option value="Teacher" <?= $user['usertype'] == 'Teacher' ? 'selected' : '' ?>>Teacher</option>
                                <option value="Student" <?= $user['usertype'] == 'Student' ? 'selected' : '' ?>>Student</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="Male" <?= $user['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $user['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $user['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" value="<?= $user['dob'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($user['contact_number']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control"><?= htmlspecialchars($user['address']) ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" name="profile_picture" class="form-control" accept="image/*">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile" class="mt-2" style="max-height:100px;">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-select">
                                <option value="">Select Class</option>
                                <?php while ($class = $classes->fetch_assoc()): ?>
                                    <option value="<?= $class['class_id'] ?>" <?= $user['class_id'] == $class['class_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['class_name'] . " - " . $class['section']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Update User</button>
                        <a href="manage_users.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
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
