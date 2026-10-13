
<?php
require_once __DIR__ . '/../php/auth-check.php';

$role = $_SESSION['role'] ?? '';

if ($role !== 'student') {
    if ($role === 'admin') {
        header('Location: admin-dashboard.php');
    } elseif ($role === 'faculty') {
        header('Location: faculty-dashboard.php');
    } else {
        header('Location: login.html?reason=login');
    }
    exit;
}

$studentName = htmlspecialchars(
    $_SESSION['name'] ?? 'Student',
    ENT_QUOTES,
    'UTF-8'
);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Student Dashboard</title>
    <meta name="description" content="Student Dashboard of StudentHub Portal">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>StudentHub</h1>
            <nav class="dashboard-nav" aria-label="Student Dashboard Navigation">
                <a href="index.html">Home</a> |
                <a href="about.html">About</a> |
                <a href="student-dashboard.php" aria-current="page">Dashboard</a> |
                <form action="../php/logout.php" method="post" style="display:inline">
                    <button type="submit">Logout</button>
                </form>
            </nav>
        </div>
    </header>

    <main>
        <h2>Student Dashboard</h2>
        <p>Welcome, <strong><?= $studentName ?></strong>!</p>

        <div class="dashboard-grid">
            <section>
                <h3>Student Information</h3>
                <p><strong>Name:</strong> <?= $studentName ?></p>
                <p><strong>Enrollment Number:</strong> D26DCS151</p>
                <p><strong>Department:</strong> Computer Science Engineering</p>
                <p><strong>Semester:</strong> 3</p>
            </section>

            <section>
                <h3>Academic Overview</h3>
                <ul>
                    <li>Current Semester: 3</li>
                    <li>Subjects Enrolled: 6</li>
                    <li>Overall Attendance: 91%</li>
                    <li>Pending Assignments: 2</li>
                </ul>
            </section>

            <section>
                <h3>Quick Links</h3>
                <nav aria-label="Student Quick Links">
                    <ul>
                        <li><a href="attendance.html">Attendance</a></li>
                        <li><a href="assignments.html">Assignments</a></li>
                        <li><a href="study-materials.html">Study Materials</a></li>
                        <li><a href="profile.html">Profile</a></li>
                    </ul>
                </nav>
            </section>

            <section>
                <h3>Recent Notifications</h3>
                <article>
                    <h4>Assignment Submission</h4>
                    <p>Web Development Framework assignment is due on 25 August 2026.</p>
                </article>
                <article>
                    <h4>Attendance Update</h4>
                    <p>Your attendance has been updated for the current week.</p>
                </article>
                <article>
                    <h4>Study Material</h4>
                    <p>New lecture notes have been uploaded by the faculty.</p>
                </article>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2026 StudentHub. All Rights Reserved.</p>
            <address>
                Email: <a href="mailto:info@studenthub.com">info@studenthub.com</a>
            </address>
        </div>
    </footer>
</body>
</html>
