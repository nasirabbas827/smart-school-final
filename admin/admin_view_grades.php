<?php
session_start();
include('config.php');

// Check admin login
if (!isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("Location: admin_login.php");
    exit;
}

// Filters
$filter_student = isset($_GET['student_id']) ? intval($_GET['student_id']) : '';

// Fetch all students for dropdown
$students = [];
$res = $conn->query("SELECT user_id, first_name, last_name FROM users WHERE usertype='Student' ORDER BY first_name, last_name");
while ($row = $res->fetch_assoc()) $students[] = $row;

// Fetch grades with class, subject, and teacher name
$sql = "
    SELECT g.*, 
           u.first_name AS student_first, u.last_name AS student_last,
           c.class_name, c.section,
           s.subject_name,
           t.first_name AS teacher_first, t.last_name AS teacher_last
    FROM grades g
    JOIN users u ON g.student_id = u.user_id
    JOIN classes c ON g.class_id = c.class_id
    JOIN subjects s ON g.subject_id = s.subject_id
    JOIN users t ON g.entered_by = t.user_id
    WHERE 1=1
";

$params = [];
$types = "";

// Apply student filter
if (!empty($filter_student)) {
    $sql .= " AND g.student_id = ?";
    $params[] = $filter_student;
    $types .= "i";
}

$sql .= " ORDER BY u.first_name ASC, u.last_name ASC, c.class_name ASC, s.subject_name ASC, g.term ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$grades = [];
while ($row = $result->fetch_assoc()) $grades[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin - Student Grades</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="margin-left:280px; background-color:#e6effc;">
<?php include('admin_sidebar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4 text-center">Student Grades</h2>

    <!-- Filter -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="student_id" class="form-label">Filter by Student</label>
            <select class="form-select" name="student_id" id="student_id">
                <option value="">All Students</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?= $s['user_id'] ?>" <?= ($filter_student == $s['user_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="admin_view_grades.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <!-- CSV Button -->
    <div class="d-flex justify-content-end mb-2">
        <button id="generateCsv" class="btn btn-success">Generate CSV</button>
    </div>

    <!-- Grades Table -->
    <div class="table-responsive">
        <table id="gradesTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Exam Type</th>
                    <th>Term</th>
                    <th>Marks Obtained</th>
                    <th>Max Marks</th>
                    <th>Grade</th>
                    <th>Comments</th>
                    <th>Entered By (Teacher)</th>
                    <th>Entered At</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($grades): ?>
                    <?php foreach ($grades as $g): ?>
                        <tr>
                            <td><?= htmlspecialchars($g['student_first'] . ' ' . $g['student_last']) ?></td>
                            <td><?= htmlspecialchars($g['class_name'] . ' - ' . $g['section']) ?></td>
                            <td><?= htmlspecialchars($g['subject_name']) ?></td>
                            <td><?= htmlspecialchars($g['exam_type']) ?></td>
                            <td><?= htmlspecialchars($g['term']) ?></td>
                            <td><?= htmlspecialchars($g['marks_obtained']) ?></td>
                            <td><?= htmlspecialchars($g['max_marks']) ?></td>
                            <td><?= htmlspecialchars($g['grade']) ?></td>
                            <td><?= htmlspecialchars($g['comments']) ?></td>
                            <td><?= htmlspecialchars($g['teacher_first'] . ' ' . $g['teacher_last']) ?></td>
                            <td><?= htmlspecialchars($g['entered_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="text-center">No grades found.</td></tr>
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

document.getElementById('generateCsv').addEventListener('click', function() {
    generateCSV('gradesTable', 'student_grades.csv');
});
</script>
</body>
</html>

<?php $conn->close(); ?>
