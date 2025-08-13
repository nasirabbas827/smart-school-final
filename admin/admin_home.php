<?php
session_start();
include('config.php');

// Check if the user is logged in as an admin
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Count total classes
$classCount = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM classes");
if ($row = mysqli_fetch_assoc($result)) {
    $classCount = $row['total'];
}

// Count total subjects
$subjectCount = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM subjects");
if ($row = mysqli_fetch_assoc($result)) {
    $subjectCount = $row['total'];
}

// Count total timetables
$timetableCount = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM timetables");
if ($row = mysqli_fetch_assoc($result)) {
    $timetableCount = $row['total'];
}

// Count total users
$userCount = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
if ($row = mysqli_fetch_assoc($result)) {
    $userCount = $row['total'];
}

// Fetch latest classes
$classList = mysqli_query($conn, "SELECT class_id, class_name, section, created_at FROM classes ORDER BY created_at DESC LIMIT 5");

// Fetch latest subjects
$subjectList = mysqli_query($conn, "SELECT s.subject_id, s.subject_name, c.class_name 
    FROM subjects s
    JOIN classes c ON s.class_id = c.class_id
    ORDER BY s.subject_id DESC LIMIT 5");

// Fetch latest timetables
$timetableList = mysqli_query($conn, "SELECT t.timetable_id, c.class_name, s.subject_name, t.day_of_week, t.start_time, t.end_time 
    FROM timetables t
    JOIN classes c ON t.class_id = c.class_id
    JOIN subjects s ON t.subject_id = s.subject_id
    ORDER BY t.timetable_id DESC LIMIT 5");

// Fetch latest users
$userList = mysqli_query($conn, "SELECT user_id, first_name, last_name, email, created_at 
    FROM users ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />
    <style>
        body { background-color: #e6effc; font-family: Arial, sans-serif; margin-left: 280px; }
        .container { padding: 2rem 1rem; }
        h2, h4 { color: #333; }
        .card { border-radius: 8px; transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .card-icon { font-size: clamp(2rem, 5vw, 3rem); }
        .table { background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
        .table th { background-color: #f8f9fa; }
        .table-hover tbody tr:hover { background-color: #f1f6ff; }
        .navbar-toggler { display: none; position: fixed; top: 1rem; left: 1rem; z-index: 1100; }
        @media (max-width: 992px) { body { margin-left: 0; } .navbar-toggler { display: block; } }
    </style>
</head>
<body>
<button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
    <span class="navbar-toggler-icon"></span>
</button>

<?php include('admin_sidebar.php'); ?>

<div class="container mt-5">
    <h2 class="text-center mb-4"><i class="fas fa-user-shield me-2"></i>Admin Dashboard</h2>

    <div class="row mb-5">
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card text-white bg-primary shadow">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title"><i class="fas fa-chalkboard me-2"></i>Total Classes</h5>
                        <p class="card-text h4"><?= $classCount ?></p>
                    </div>
                    <i class="fas fa-chalkboard card-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card text-white bg-success shadow">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title"><i class="fas fa-book me-2"></i>Total Subjects</h5>
                        <p class="card-text h4"><?= $subjectCount ?></p>
                    </div>
                    <i class="fas fa-book card-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card text-white bg-info shadow">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Total Timetables</h5>
                        <p class="card-text h4"><?= $timetableCount ?></p>
                    </div>
                    <i class="fas fa-calendar-alt card-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card text-white bg-warning shadow">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title"><i class="fas fa-users me-2"></i>Total Users</h5>
                        <p class="card-text h4"><?= $userCount ?></p>
                    </div>
                    <i class="fas fa-users card-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Classes Table -->
    <h4><i class="fas fa-chalkboard me-2"></i>Latest Classes</h4>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Class Name</th>
                    <th>Section</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($classList) > 0): ?>
                    <?php while ($class = mysqli_fetch_assoc($classList)): ?>
                        <tr>
                            <td><?= $class['class_id'] ?></td>
                            <td><?= htmlspecialchars($class['class_name']) ?></td>
                            <td><?= htmlspecialchars($class['section']) ?></td>
                            <td><?= $class['created_at'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">No classes found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Subjects Table -->
    <h4><i class="fas fa-book me-2"></i>Latest Subjects</h4>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Subject Name</th>
                    <th>Class</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($subjectList) > 0): ?>
                    <?php while ($subject = mysqli_fetch_assoc($subjectList)): ?>
                        <tr>
                            <td><?= $subject['subject_id'] ?></td>
                            <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                            <td><?= htmlspecialchars($subject['class_name']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">No subjects found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Timetables Table -->
    <h4><i class="fas fa-calendar-alt me-2"></i>Latest Timetables</h4>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Day</th>
                    <th>Start</th>
                    <th>End</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($timetableList) > 0): ?>
                    <?php while ($t = mysqli_fetch_assoc($timetableList)): ?>
                        <tr>
                            <td><?= $t['timetable_id'] ?></td>
                            <td><?= htmlspecialchars($t['class_name']) ?></td>
                            <td><?= htmlspecialchars($t['subject_name']) ?></td>
                            <td><?= $t['day_of_week'] ?></td>
                            <td><?= $t['start_time'] ?></td>
                            <td><?= $t['end_time'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center">No timetables found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Users Table -->
    <h4><i class="fas fa-users me-2"></i>Latest Users</h4>
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($userList) > 0): ?>
                    <?php while ($user = mysqli_fetch_assoc($userList)): ?>
                        <tr>
                            <td><?= $user['user_id'] ?></td>
                            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= $user['created_at'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>
