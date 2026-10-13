
<?php
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/db-mysqli.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrator access is required.');
}

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid student ID.');
}

$stmt = $conn->prepare(
    'SELECT student_id, name, email, course, year
     FROM students
     WHERE student_id = ?
     LIMIT 1'
);

$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudentHub | View Student</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header>
    <div class="container">
        <h1>StudentHub</h1>
        <a href="manage-students.php">Back to Student Management</a>
    </div>
</header>

<main class="container">
    <h2>Student Details</h2>

    <section>
        <p><strong>Student ID:</strong> <?= (int) $student['student_id'] ?></p>
        <p><strong>Name:</strong> <?= e($student['name']) ?></p>
        <p><strong>Email:</strong> <?= e($student['email']) ?></p>
        <p><strong>Course:</strong> <?= e($student['course']) ?></p>
        <p><strong>Year:</strong> <?= e($student['year']) ?></p>
    </section>

    <p>
        <a href="edit-student.php?id=<?= (int) $student['student_id'] ?>">
            Edit Student
        </a>
        |
        <a href="manage-students.php">Back to List</a>
    </p>
</main>
</body>
</html>
