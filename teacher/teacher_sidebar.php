<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Teacher Dashboard</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet" />

  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #e6effc;
      margin-left: 280px;
      overflow-x: hidden;
    }

    /* Fixed Sidebar for desktop */
    nav.sidebar {
      width: 280px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
      color: white;
      padding: 0;
      z-index: 1000;
      overflow-y: auto;
      scrollbar-width: thin;
      scrollbar-color: #34495e #2c3e50;
    }

    /* Custom scrollbar for Webkit browsers */
    nav.sidebar::-webkit-scrollbar {
      width: 6px;
    }

    nav.sidebar::-webkit-scrollbar-track {
      background: #2c3e50;
      border-radius: 10px;
    }

    nav.sidebar::-webkit-scrollbar-thumb {
      background: #34495e;
      border-radius: 10px;
    }

    nav.sidebar::-webkit-scrollbar-thumb:hover {
      background: #4a6741;
    }

    .nav-link {
      font-size: clamp(0.9rem, 2.5vw, 1rem);
      transition: all 0.3s ease;
      border-radius: 8px;
      margin: 0.125rem 0.75rem;
      padding: 0.75rem 1rem;
      color: #ecf0f1 !important;
      border-left: 3px solid transparent;
    }

    .nav-link:hover {
      background: linear-gradient(90deg, #3498db, #2980b9);
      transform: translateX(3px);
      border-left-color: #3498db;
      box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
      color: white !important;
    }

    .nav-link.active {
      background: linear-gradient(90deg, #27ae60, #2ecc71);
      border-left-color: #27ae60;
      box-shadow: 0 4px 12px rgba(39, 174, 96, 0.4);
      color: white !important;
    }

    /* Mobile hamburger button */
    .toggle-btn {
      display: none;
      position: fixed;
      top: 1rem;
      left: 1rem;
      z-index: 1100;
      border: none;
      border-radius: 8px;
      padding: 0.75rem;
      background: linear-gradient(45deg, #3498db, #2980b9);
      box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    }

    /* Hide fixed sidebar on mobile */
    @media (max-width: 992px) {
      body {
        margin-left: 0 !important;
      }

      nav.sidebar {
        display: none !important;
        position: absolute !important;
        width: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
      }

      .toggle-btn {
        display: block;
      }
    }

    /* Style offcanvas sidebar */
    .offcanvas {
      width: 280px !important;
      background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%) !important;
    }

    .offcanvas .nav-link {
      color: #ecf0f1 !important;
      margin-bottom: 0.5rem;
      border-left: 3px solid transparent;
    }

    .offcanvas .nav-link:hover {
      background: linear-gradient(90deg, #3498db, #2980b9) !important;
      border-left-color: #3498db;
      transform: translateX(3px);
    }

    .offcanvas-header {
      background: linear-gradient(90deg, #3498db, #2980b9);
      color: white;
    }

    /* Brand styling */
    .sidebar-brand {
      font-weight: bold;
      font-size: 1.25rem;
      background: linear-gradient(45deg, #e74c3c, #c0392b);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin: 1.5rem 0;
      padding: 1rem;
      border-radius: 12px;
      text-align: center;
      border-bottom: 2px solid rgba(231, 76, 60, 0.3);
    }

    /* Divider */
    .sidebar-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent, #34495e, transparent);
      margin: 1rem 0;
    }

    /* Section headers */
    .section-header {
      font-size: 0.85rem;
      font-weight: bold;
      color: #bdc3c7;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 0.5rem 1rem;
      margin-top: 1rem;
      margin-bottom: 0.5rem;
    }

    @media (max-width: 576px) {
      .nav-link {
        font-size: 0.875rem;
        padding: 0.625rem 0.875rem;
      }

      .offcanvas-title {
        font-size: 1.1rem;
      }

      .sidebar-brand {
        font-size: 1.1rem;
      }
    }

    /* Active indicator */
    .nav-link i {
      width: 20px;
      text-align: center;
      transition: transform 0.3s ease;
    }

    .nav-link:hover i {
      transform: scale(1.1);
    }
  </style>
</head>
<body>

<!-- 🔘 Enhanced Hamburger Button (mobile only) -->
<button class="btn toggle-btn shadow-lg" type="button"
        data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas"
        aria-controls="sidebarOffcanvas">
  <i class="fas fa-bars fs-5"></i>
</button>

<!-- 🧱 Enhanced Fixed Sidebar (desktop only) -->
<nav class="sidebar d-flex flex-column flex-shrink-0">
  <!-- Brand -->
  <div class="sidebar-brand d-flex align-items-center justify-content-center mb-0">
    <i class="fas fa-chalkboard-teacher me-2"></i>
    <span>Teacher Portal</span>
  </div>
  
  <div class="p-3">
    <hr class="sidebar-divider">
    
    <!-- Main Navigation -->
    <ul class="nav nav-pills flex-column mb-3">
      <li class="nav-item">
        <a class="nav-link active" href="teacher_dashboard.php" aria-current="page">
          <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
      </li>
    </ul>

    <!-- 📋 Management Section -->
    <div class="section-header">
      <i class="fas fa-clipboard-list me-1"></i>MANAGEMENT
    </div>
    <ul class="nav nav-pills flex-column mb-3">
      <li class="nav-item">
        <a class="nav-link" href="mark_attendance.php">
          <i class="fas fa-calendar-check me-2"></i> Mark Attendance
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="announcements.php">
          <i class="fas fa-bullhorn me-2"></i> Announcements
        </a>
      </li>
    </ul>

    <!-- 📚 Academic Section -->
    <div class="section-header">
      <i class="fas fa-book-open me-1"></i>ACADEMIC
    </div>
    <ul class="nav nav-pills flex-column mb-3">
      <li class="nav-item">
        <a class="nav-link" href="add_syllabus.php">
          <i class="fas fa-file-alt me-2"></i> Manage Syllabus
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="add_homework.php">
          <i class="fas fa-tasks me-2"></i> Manage Homework
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="add_assignment.php">
          <i class="fas fa-clipboard-list me-2"></i> Manage Assignments
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="add_grades.php">
          <i class="fas fa-chart-line me-2"></i> Report & Grades
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="add_quiz.php">
          <i class="fas fa-question-circle me-2"></i> Create Quiz
        </a>
      </li>
    </ul>

    <!-- 💬 Communication Section -->
    <div class="section-header">
      <i class="fas fa-comments me-1"></i>COMMUNICATION
    </div>
    <ul class="nav nav-pills flex-column mb-3">
      <li class="nav-item">
        <a class="nav-link" href="teacher_feedback.php">
          <i class="fas fa-comment-dots me-2"></i> Student Feedback
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="teacher_leave.php">
          <i class="fas fa-calendar-times me-2"></i> Leave Requests
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="class_messages.php">
          <i class="fas fa-comments me-2"></i> Class Messages
        </a>
      </li>
    </ul>

    <hr class="sidebar-divider">
    
    <!-- Logout -->
    <div class="mt-auto">
      <a class="nav-link" href="logout.php">
        <i class="fas fa-sign-out-alt me-2"></i> Logout
      </a>
    </div>
  </div>
</nav>

<!-- 📱 Enhanced Offcanvas Sidebar (mobile only) - FULL MENU -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="sidebarOffcanvasLabel">
      <i class="fas fa-chalkboard-teacher me-2"></i> Teacher Portal
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <!-- Main Navigation -->
    <ul class="nav nav-pills flex-column mb-3 px-3 py-2">
      <li class="nav-item">
        <a class="nav-link active" href="teacher_dashboard.php">
          <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
      </li>
    </ul>

    <!-- Management Section -->
    <div class="px-3 pb-3">
      <div class="section-header mb-2">
        <i class="fas fa-clipboard-list me-1"></i>MANAGEMENT
      </div>
      <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
          <a class="nav-link" href="mark_attendance.php">
            <i class="fas fa-calendar-check me-2"></i> Mark Attendance
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="announcements.php">
            <i class="fas fa-bullhorn me-2"></i> Announcements
          </a>
        </li>
      </ul>
    </div>

    <!-- Academic Section -->
    <div class="px-3 pb-3">
      <div class="section-header mb-2">
        <i class="fas fa-book-open me-1"></i>ACADEMIC
      </div>
      <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
          <a class="nav-link" href="add_syllabus.php">
            <i class="fas fa-file-alt me-2"></i> Manage Syllabus
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="add_homework.php">
            <i class="fas fa-tasks me-2"></i> Manage Homework
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="add_assignment.php">
            <i class="fas fa-clipboard-list me-2"></i> Manage Assignments
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="add_grades.php">
            <i class="fas fa-chart-line me-2"></i> Report & Grades
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="add_quiz.php">
            <i class="fas fa-question-circle me-2"></i> Create Quiz
          </a>
        </li>
      </ul>
    </div>

    <!-- Communication Section -->
    <div class="px-3 pb-3">
      <div class="section-header mb-2">
        <i class="fas fa-comments me-1"></i>COMMUNICATION
      </div>
      <ul class="nav nav-pills flex-column mb-3">
        <li class="nav-item">
          <a class="nav-link" href="teacher_feedback.php">
            <i class="fas fa-comment-dots me-2"></i> Student Feedback
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="teacher_leave.php">
            <i class="fas fa-calendar-times me-2"></i> Leave Requests
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="class_messages.php">
            <i class="fas fa-comments me-2"></i> Class Messages
          </a>
        </li>
      </ul>
    </div>

    <!-- Logout Section -->
    <div class="px-3 pb-3 border-top">
      <a class="nav-link" href="logout.php">
        <i class="fas fa-sign-out-alt me-2"></i> Logout
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Add smooth scrolling for sidebar
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', function(e) {
    // Remove active class from all links
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    // Add active class to clicked link
    this.classList.add('active');
  });
});

// Auto-close mobile sidebar after clicking a link
document.querySelectorAll('.offcanvas .nav-link').forEach(link => {
  link.addEventListener('click', function() {
    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('sidebarOffcanvas'));
    if (offcanvas) {
      offcanvas.hide();
    }
  });
});
</script>

</body>
</html>