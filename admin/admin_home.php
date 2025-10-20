<?php
session_start();
include('config.php'); // Assumes this connects to your database ($conn)

// -----------------------------------------------------------------------------
// 1. Authorization Check
// -----------------------------------------------------------------------------
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    // Redirect non-admin or unauthenticated users
    header("Location: admin_login.php");
    exit;
}

// -----------------------------------------------------------------------------
// 2. Comprehensive Data Counting (Stats Cards)
// -----------------------------------------------------------------------------

// Initialize all counts to 0
$classCount = $subjectCount = $timetableCount = $userCount = 0;
$teacherCount = $studentCount = $homeworkCount = $assignmentCount = 0;
$pendingFeesCount = $pendingLeaveCount = $announcementCount = $feedbackCount = 0;

// Function to safely execute count queries
function getCount($conn, $table, $where = "") {
    $sql = "SELECT COUNT(*) as total FROM $table $where";
    $result = mysqli_query($conn, $sql);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return (int)$row['total'];
    }
    return 0;
}

// Core Counts
$classCount = getCount($conn, "classes");
$subjectCount = getCount($conn, "subjects");
$timetableCount = getCount($conn, "timetables");
$userCount = getCount($conn, "users");

// New Granular Counts
$teacherCount = getCount($conn, "users", "WHERE usertype = 'Teacher'");
$studentCount = getCount($conn, "users", "WHERE usertype = 'Student'");
$homeworkCount = getCount($conn, "homework");
$assignmentCount = getCount($conn, "assignments");
$announcementCount = getCount($conn, "announcements");
$feedbackCount = getCount($conn, "feedback");

// Status-based Counts
$pendingFeesCount = getCount($conn, "fee_vouchers", "WHERE status = 'Pending'");
$pendingLeaveCount = getCount($conn, "leave_applications", "WHERE status = 'Pending'");


// -----------------------------------------------------------------------------
// 3. Fetch Latest Records for Tables
// -----------------------------------------------------------------------------

// Fetch latest records (Keeping existing ones)
$classList = mysqli_query($conn, "SELECT class_id, class_name, section, created_at FROM classes ORDER BY created_at DESC LIMIT 5");
$subjectList = mysqli_query($conn, "SELECT s.subject_id, s.subject_name, c.class_name FROM subjects s JOIN classes c ON s.class_id = c.class_id ORDER BY s.subject_id DESC LIMIT 5");
$timetableList = mysqli_query($conn, "SELECT t.timetable_id, c.class_name, s.subject_name, t.day_of_week, t.start_time, t.end_time FROM timetables t JOIN classes c ON t.class_id = c.class_id JOIN subjects s ON t.subject_id = s.subject_id ORDER BY t.timetable_id DESC LIMIT 5");
$userList = mysqli_query($conn, "SELECT user_id, usertype, first_name, last_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");

// New Latest Records
$latestHomework = mysqli_query($conn, "SELECT h.homework_id, h.title, c.class_name, s.subject_name, h.due_date FROM homework h JOIN classes c ON h.class_id = c.class_id JOIN subjects s ON h.subject_id = s.subject_id ORDER BY h.created_at DESC LIMIT 5");
$latestApplications = mysqli_query($conn, "SELECT l.leave_id, u.first_name, u.last_name, c.class_name, l.start_date, l.end_date, l.status FROM leave_applications l JOIN users u ON l.student_id = u.user_id JOIN classes c ON l.class_id = c.class_id ORDER BY l.applied_at DESC LIMIT 5");

// Error check for connection (Optional but recommended)
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard | Comprehensive Overview</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />
    <style>
        /* General Styles */
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin-left: 280px; }
        .container { padding: 2rem 1rem; }
        h2, h4 { color: #2c3e50; }

        /* Card Styles */
        .card { border-radius: 10px; border: none; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); transition: all 0.3s; }
        .card:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
        .card-icon { font-size: clamp(2rem, 5vw, 3rem); opacity: 0.7; }
        .card-body p { font-weight: bold; }

        /* Color Customization */
        .bg-primary { background-color: #3498db !important; }
        .bg-success { background-color: #2ecc71 !important; }
        .bg-info { background-color: #34495e !important; }
        .bg-warning { background-color: #f39c12 !important; }
        .bg-danger { background-color: #e74c3c !important; }
        .bg-secondary { background-color: #95a5a6 !important; }

        /* Table Styles */
        .table-responsive { border-radius: 8px; overflow: hidden; }
        .table { background-color: #ffffff; margin-bottom: 0; }
        .table th { background-color: #ecf0f1; color: #34495e; font-weight: 600; }
        .table-hover tbody tr:hover { background-color: #dbe4f1; cursor: pointer; }
        
        /* Sidebar and Mobile Toggle */
        .navbar-toggler { display: none; position: fixed; top: 1rem; left: 1rem; z-index: 1100; border: 1px solid #3498db; background-color: #fff; }
        .navbar-toggler-icon { color: #3498db; }
        @media (max-width: 992px) { body { margin-left: 0; } .navbar-toggler { display: block; } }
    </style>
</head>
<body>
<button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
    <span class="fas fa-bars"></span>
</button>

<?php 
// Assuming 'admin_sidebar.php' contains the offcanvas sidebar structure.
include('admin_sidebar.php'); 
?>

<div class="container mt-5">
    <h2 class="text-center mb-5"><i class="fas fa-tachometer-alt me-3"></i>Admin Dashboard Overview</h2>

    <div class="row mb-5">
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-4">
            <div class="card text-white bg-primary shadow">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title"><i class="fas fa-chalkboard me-2"></i>Total Classes</h5>
                        <p class="card-text h4"><?= $classCount ?></p>
                    </div>
                    <i class="fas fa-school card-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-4">
            <div class="card text-white bg-success shadow">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title"><i class="fas fa-book-open me-2"></i>Total Subjects</h5>
                        <p class="card-text h4"><?= $subjectCount ?></p>
                    </div>
                    <i class="fas fa-book-open card-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-4">
            <div class="card text-white bg-info shadow">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title"><i class="fas fa-calendar-alt me-2"></i>Total Timetables</h5>
                        <p class="card-text h4"><?= $timetableCount ?></p>
                    </div>
                    <i class="fas fa-calendar-check card-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12 mb-4">
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

    <div class="row mb-5">
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
            <div class="card text-white bg-secondary shadow">
                <div class="card-body text-center p-3">
                    <i class="fas fa-graduation-cap d-block card-icon mx-auto mb-2"></i>
                    <h6 class="card-title mb-1">Students</h6>
                    <p class="card-text h5"><?= $studentCount ?></p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
            <div class="card text-white bg-secondary shadow">
                <div class="card-body text-center p-3">
                    <i class="fas fa-chalkboard-teacher d-block card-icon mx-auto mb-2"></i>
                    <h6 class="card-title mb-1">Teachers</h6>
                    <p class="card-text h5"><?= $teacherCount ?></p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
            <div class="card text-white bg-success shadow">
                <div class="card-body text-center p-3">
                    <i class="fas fa-file-alt d-block card-icon mx-auto mb-2"></i>
                    <h6 class="card-title mb-1">Homework/Assign</h6>
                    <p class="card-text h5"><?= $homeworkCount + $assignmentCount ?></p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
            <div class="card text-white bg-danger shadow">
                <div class="card-body text-center p-3">
                    <i class="fas fa-money-bill-wave d-block card-icon mx-auto mb-2"></i>
                    <h6 class="card-title mb-1">Pending Fees</h6>
                    <p class="card-text h5"><?= $pendingFeesCount ?></p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
            <div class="card text-white bg-danger shadow">
                <div class="card-body text-center p-3">
                    <i class="fas fa-plane-departure d-block card-icon mx-auto mb-2"></i>
                    <h6 class="card-title mb-1">Pending Leaves</h6>
                    <p class="card-text h5"><?= $pendingLeaveCount ?></p>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-4">
            <div class="card text-white bg-info shadow">
                <div class="card-body text-center p-3">
                    <i class="fas fa-bullhorn d-block card-icon mx-auto mb-2"></i>
                    <h6 class="card-title mb-1">Announcements</h6>
                    <p class="card-text h5"><?= $announcementCount ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-6 mb-4">
            <h4><i class="fas fa-chalkboard me-2"></i>Latest Classes</h4>
            <div class="table-responsive">
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
                                    <td><?= date('Y-m-d', strtotime($class['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">No classes found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h4 class="mt-4"><i class="fas fa-book me-2"></i>Latest Subjects</h4>
            <div class="table-responsive">
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
        </div>

        <div class="col-lg-6 mb-4">
            <h4><i class="fas fa-calendar-alt me-2"></i>Latest Timetable Entries</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Day</th>
                            <th>Time</th>
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
                                    <td><?= date('h:i A', strtotime($t['start_time'])) . ' - ' . date('h:i A', strtotime($t['end_time'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No timetables found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h4 class="mt-4"><i class="fas fa-users me-2"></i>Latest Users</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($userList) > 0): ?>
                            <?php while ($user = mysqli_fetch_assoc($userList)): ?>
                                <tr>
                                    <td><?= $user['user_id'] ?></td>
                                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                    <td><span class="badge bg-<?= ($user['usertype'] == 'Teacher') ? 'success' : 'primary' ?>"><?= $user['usertype'] ?></span></td>
                                    <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <hr class="my-5">

    <div class="row">
        <div class="col-lg-6 mb-4">
            <h4><i class="fas fa-tasks me-2"></i>Latest Homework/Assignments</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Class/Subject</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($latestHomework) > 0): ?>
                            <?php while ($hw = mysqli_fetch_assoc($latestHomework)): ?>
                                <tr>
                                    <td><?= $hw['homework_id'] ?></td>
                                    <td><?= htmlspecialchars($hw['title']) ?></td>
                                    <td><?= htmlspecialchars($hw['class_name'] . ' - ' . $hw['subject_name']) ?></td>
                                    <td><?= date('Y-m-d', strtotime($hw['due_date'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">No homework or assignments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <h4><i class="fas fa-bell me-2"></i>Latest Leave Applications</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Dates</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($latestApplications) > 0): ?>
                            <?php while ($leave = mysqli_fetch_assoc($latestApplications)): ?>
                                <tr>
                                    <td><?= $leave['leave_id'] ?></td>
                                    <td><?= htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']) ?></td>
                                    <td><?= htmlspecialchars($leave['class_name']) ?></td>
                                    <td><?= date('Y-m-d', strtotime($leave['start_date'])) . ' to ' . date('Y-m-d', strtotime($leave['end_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($leave['status'] == 'Pending') ? 'warning' : (($leave['status'] == 'Approved') ? 'success' : 'danger') ?>">
                                            <?= $leave['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No leave applications found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conn); ?>