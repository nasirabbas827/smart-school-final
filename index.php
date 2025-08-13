<!DOCTYPE html>
<html lang="en">
<head>
    <title>Smart School Management System</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #e6effc;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Header styling */
        .header {
            text-align: center;
            padding: 3rem 1rem;
            color: #333;
        }

        .header h1 {
            font-size: clamp(1.8rem, 5vw, 2.5rem);
            margin-bottom: 0.625rem;
        }

        .header p {
            font-size: clamp(0.9rem, 3vw, 1.1rem);
            color: #666;
            margin-bottom: 1.5rem;
        }

        .btn-signin, .btn-register {
            padding: 0.625rem 1.875rem;
            border-radius: 5px;
            margin: 0.5rem;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            display: inline-flex;
            align-items: center;
            transition: background-color 0.3s;
        }

        .btn-signin {
            background-color: #007bff;
            color: #fff;
            border: none;
        }

        .btn-register {
            background-color: #fff;
            color: #007bff;
            border: 1px solid #007bff;
        }

        .btn-signin:hover, .btn-register:hover {
            background-color: #0056b3;
            color: #fff;
            border-color: #0056b3;
        }

        .btn-signin i, .btn-register i {
            margin-right: 0.5rem;
        }

        /* Feature cards styling */
        .feature-cards {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            padding: 2.5rem 1rem;
        }

        .feature-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 320px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: #007bff;
            margin-bottom: 1rem;
        }

        .feature-card h5 {
            margin-bottom: 1rem;
            color: #333;
            font-size: 1.25rem;
        }

        .feature-card ul {
            list-style: none;
            padding: 0;
            text-align: left;
        }

        .feature-card ul li {
            margin-bottom: 0.625rem;
            color: #666;
            display: flex;
            align-items: center;
        }

        .feature-card ul li:before {
            content: "\f058";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #007bff;
            display: inline-block;
            width: 1.5em;
            margin-right: 0.5rem;
        }

        /* Footer styling */
        footer {
            background: #f8f9fa;
            padding: 2rem 1rem;
            text-align: center;
        }

        footer p {
            margin: 0;
            color: #666;
        }

        .social-icons a {
            color: #333;
            margin: 0 0.5rem;
            font-size: 1.2rem;
            transition: color 0.3s;
        }

        .social-icons a:hover {
            color: #007bff;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header {
                padding: 2rem 1rem;
            }

            .feature-cards {
                flex-direction: column;
                align-items: center;
            }

            .feature-card {
                max-width: 90%;
            }

            .btn-signin, .btn-register {
                width: 100%;
                max-width: 200px;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .header h1 {
                font-size: 1.5rem;
            }

            .header p {
                font-size: 0.875rem;
            }

            .feature-card i {
                font-size: 2rem;
            }

            .feature-card h5 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Smart School Management System</h1>
        <p>Efficiently manage students, teachers, classes, attendance, and school resources</p>
        <div>
            <a class="btn-signin" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a class="btn-register" href="admin/admin_login.php"><i class="fas fa-user-shield"></i> Admin Login</a>
        </div>
    </div>

    <div class="feature-cards">
        <div class="feature-card">
            <i class="fas fa-user-graduate"></i>
            <h5>Student Management</h5>
            <ul>
                <li>Enroll and manage students</li>
                <li>Track student profiles</li>
                <li>Monitor academic progress</li>
            </ul>
        </div>
        <div class="feature-card">
            <i class="fas fa-chalkboard-teacher"></i>
            <h5>Teacher Management</h5>
            <ul>
                <li>Manage teacher profiles</li>
                <li>Assign classes and subjects</li>
                <li>Monitor teacher performance</li>
            </ul>
        </div>
        <div class="feature-card">
            <i class="fas fa-school"></i>
            <h5>Class & Attendance</h5>
            <ul>
                <li>Organize class schedules</li>
                <li>Track student attendance</li>
                <li>Generate attendance reports</li>
            </ul>
        </div>
    </div>

    <footer class="mt-5 py-3 bg-light text-center">
        <div class="social-icons">
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
        <p class="mt-2">© 2025 Smart School Management System. All rights reserved.</p>
    </footer>

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
