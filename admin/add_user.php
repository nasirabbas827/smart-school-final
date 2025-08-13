<?php
session_start();
include('config.php');

// Check admin login
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

$successMsg = $errorMsg = "";

// Fetch classes for dropdown
$classes = $conn->query("SELECT class_id, class_name, section FROM classes ORDER BY class_name ASC");

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usertype = trim($_POST['usertype']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $gender = trim($_POST['gender']);
    $dob = trim($_POST['dob']);
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;

    // Validate required fields
    if (empty($usertype) || empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($gender) || empty($dob)) {
        $errorMsg = "Please fill in all required fields.";
    } else {
        // Password hashing
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Handle image upload
        $profile_picture = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $target_dir = "images/";
            $file_name = time() . "_" . basename($_FILES['profile_picture']['name']);
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $profile_picture = $target_file;
            } else {
                $errorMsg = "Failed to upload profile picture.";
            }
        }

        if (empty($errorMsg)) {
            $stmt = $conn->prepare("INSERT INTO users (usertype, first_name, last_name, email, password_hash, gender, dob, contact_number, address, profile_picture, class_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssi", $usertype, $first_name, $last_name, $email, $password_hash, $gender, $dob, $contact_number, $address, $profile_picture, $class_id);

            if ($stmt->execute()) {
                $successMsg = "User added successfully.";
            } else {
                $errorMsg = "Error adding user: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body style="margin-left:280px; background-color:#e6effc;">
    <?php include('admin_sidebar.php'); ?>

    <div class="container mt-5 mb-5">
        <h2 class="mb-4 text-center"><i class="fas fa-user-plus me-2"></i>Add New User</h2>

        <?php if ($successMsg): ?>
            <div class="alert alert-success text-center"><?= htmlspecialchars($successMsg) ?></div>
        <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <div class="card mx-auto shadow" style="max-width: 900px;">
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">User Type</label>
                            <select name="usertype" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Teacher">Teacher</option>
                                <option value="Student">Student</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control"></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" name="profile_picture" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-select">
                                <option value="">Select Class</option>
                                <?php while ($class = $classes->fetch_assoc()): ?>
                                    <option value="<?= $class['class_id'] ?>">
                                        <?= htmlspecialchars($class['class_name'] . " - " . $class['section']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save User</button>
                        <a href="manage_users.php" class="btn btn-secondary"><i class="fas fa-eye me-1"></i>View Users</a>
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
