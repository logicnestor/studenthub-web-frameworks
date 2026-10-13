
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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
    $token = $_POST['csrf_token'] ?? '';

    if (!$id || $id < 1) {
        $error = 'Invalid student ID.';
    } elseif (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request token. Reload the page and try again.';
    } else {
        try {
            $stmt = $conn->prepare(
                'DELETE FROM students WHERE student_id = ?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($deleted === 1) {
                unset($_SESSION['csrf_token']);
                header('Location: manage-students.php?message=deleted');
                exit;
            }

            $error = 'Student not found or already deleted.';
        } catch (mysqli_sql_exception $exception) {
            error_log('Delete student failed: ' . $exception->getMessage());

            if ((int) $exception->getCode() === 1451) {
                $error = 'This student is linked to other records and cannot be deleted.';
            } else {
                $error = 'Could not delete the student. Please try again.';
            }
        }
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

$id = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (int) ($_POST['student_id'] ?? 0)
    : filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid student ID.');
}

$stmt = $conn->prepare(
    'SELECT student_id, name, email, course, year
     FROM students WHERE student_id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student && $error === '') {
    http_response_code(404);
    exit('Student not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudentHub | Delete Student</title>
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
    <h2>Delete Student</h2>

    <?php if ($error !== ''): ?>
        <p role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <?php if ($student): ?>
        <p>Are you sure you want to permanently delete this student?</p>

        <section>
            <p><strong>ID:</strong> <?= (int) $student['student_id'] ?></p>
            <p><strong>Name:</strong> <?= e($student['name']) ?></p>
            <p><strong>Email:</strong> <?= e($student['email']) ?></p>
            <p><strong>Course:</strong> <?= e($student['course']) ?></p>
            <p><strong>Year:</strong> <?= e($student['year']) ?></p>
        </section>

        <form action="delete-student.php" method="post">
            <input type="hidden" name="student_id"
                   value="<?= (int) $student['student_id'] ?>">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION['csrf_token']) ?>">

            <button type="submit"
                    onclick="return confirm('Permanently delete this student?');">
                Confirm Delete
            </button>

            <a href="manage-students.php">Cancel</a>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
