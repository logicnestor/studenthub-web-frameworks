
<?php
require_once __DIR__ . '/../php/auth-check.php';

$role = $_SESSION['role'] ?? '';

if ($role !== 'admin') {
    if ($role === 'student') {
        header('Location: student-dashboard.php');
    } elseif ($role === 'faculty') {
        header('Location: faculty-dashboard.php');
    } else {
        header('Location: login.html?reason=login');
    }
    exit;
}

$adminName = htmlspecialchars(
    $_SESSION['name'] ?? 'Admin User',
    ENT_QUOTES,
    'UTF-8'
);

$adminEmail = htmlspecialchars(
    $_SESSION['email'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Admin Dashboard</title>
    <meta name="description" content="Administrator Dashboard of StudentHub">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>StudentHub</h1>
            <nav aria-label="Main Navigation">
                <a href="index.html">Home</a> |
                <a href="admin-dashboard.php" aria-current="page">
                    Admin Dashboard
                </a> |
                <form action="../php/logout.php" method="post" style="display:inline">
                    <button type="submit">Logout</button>
                </form>
            </nav>
        </div>
    </header>

    <main>
        <h2>Administrator Dashboard</h2>
        <div class="dashboard-grid">
            <section>
                <h3>Administrator Information</h3>
                <p><strong>Name:</strong> <?= $adminName ?></p>
                <p><strong>Email:</strong> <?= $adminEmail ?></p>
                <p><strong>Role:</strong> System Administrator</p>
            </section>

            <section>
                <h3>Quick Management</h3>
                <nav class="dashboard-nav" aria-label="Administrator Actions">
                    <ul>
                        <li><a href="#">Manage Students</a></li>
                        <li><a href="#">Manage Faculty</a></li>
                        <li><a href="#">Manage Departments</a></li>
                        <li><a href="#">Manage Attendance</a></li>
                        <li><a href="#">Manage Assignments</a></li>
                        <li><a href="#">Manage Study Materials</a></li>
                    </ul>
                </nav>
            </section>

            <section>
                <h3>System Statistics</h3>
                <table>
                    <caption>System Overview</caption>
                    <thead>
                        <tr>
                            <th scope="col">Category</th>
                            <th scope="col">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Total Students</td><td>450</td></tr>
                        <tr><td>Total Faculty</td><td>35</td></tr>
                        <tr><td>Departments</td><td>5</td></tr>
                        <tr><td>Courses</td><td>18</td></tr>
                    </tbody>
                </table>
            </section>

            <section>
                <h3>Recent Activities</h3>
                <article>
                    <h4>New Student Registered</h4>
                    <p>A new student has been added to the Computer Science department.</p>
                </article>
                <article>
                    <h4>Faculty Added</h4>
                    <p>A new faculty member has been assigned to Semester 3.</p>
                </article>
                <article>
                    <h4>Study Material Uploaded</h4>
                    <p>New Web Development Framework notes have been uploaded.</p>
                </article>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2026 StudentHub. All Rights Reserved.</p>
            <address>
                Email:
                <a href="mailto:info@studenthub.com">info@studenthub.com</a>
            </address>
        </div>
    </footer>
</body>
</html>
