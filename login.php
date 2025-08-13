<?php
session_start();
include('config.php');

$email = $password = $usertype = "";
$email_err = $password_err = $usertype_err = $login_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate usertype
    if (empty($_POST["usertype"]) || !in_array($_POST["usertype"], ['Teacher', 'Student'])) {
        $usertype_err = "Please select a valid user type.";
    } else {
        $usertype = $_POST["usertype"];
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // If no errors, check credentials
    if (empty($email_err) && empty($password_err) && empty($usertype_err)) {
        $sql = "SELECT user_id, usertype, email, password_hash FROM users WHERE email = ? AND usertype = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $param_email, $param_usertype);
        $param_email = $email;
        $param_usertype = $usertype;
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $user_id, $db_usertype, $db_email, $hashed_password);

            if (mysqli_stmt_fetch($stmt)) {
                if (password_verify($password, $hashed_password)) {
                    $_SESSION["user_id"] = $user_id;
                    $_SESSION["email"] = $db_email;
                    $_SESSION["usertype"] = $db_usertype;

                    // Redirect based on usertype
                    if ($db_usertype === 'Teacher') {
                        header("Location: teacher/teacher_dashboard.php");
                    } elseif ($db_usertype === 'Student') {
                        header("Location: student/student_dashboard.php");
                    }
                    exit();
                } else {
                    $password_err = "The password you entered is incorrect.";
                }
            }
        } else {
            $email_err = "No account found with that email and user type.";
        }
        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #e6effc;
            font-family: Arial, sans-serif;
        }
        .container { padding: 3rem 1rem; }
        .card { border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 450px; margin: 0 auto; }
        .card-body { padding: 2rem; }
        .btn-primary:hover { background-color: #0056b3; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="text-center mb-4">User Login</h3>
                    <?php if (!empty($login_msg)) echo '<div class="alert alert-danger">'.$login_msg.'</div>'; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                        <div class="mb-3">
                            <label for="usertype" class="form-label">Login as</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                <select id="usertype" name="usertype" class="form-select <?php echo (!empty($usertype_err)) ? 'is-invalid' : ''; ?>">
                                    <option value="">Select User Type</option>
                                    <option value="Teacher" <?php echo ($usertype === 'Teacher') ? 'selected' : ''; ?>>Teacher</option>
                                    <option value="Student" <?php echo ($usertype === 'Student') ? 'selected' : ''; ?>>Student</option>
                                </select>
                                <div class="invalid-feedback"><?php echo $usertype_err; ?></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="text" id="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" />
                                <div class="invalid-feedback"><?php echo $email_err; ?></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" placeholder="Enter your password" />
                                <div class="invalid-feedback"><?php echo $password_err; ?></div>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Log In</button>
                        </div>
                    </form>
                    <p class="text-center mt-3">Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
