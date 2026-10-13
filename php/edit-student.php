
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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid student ID.');
}

$stmt = $conn->prepare(
    'SELECT student_id, name, email, course, year
     FROM students WHERE student_id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}

$name = $student['name'];
$email = $student['email'];
$course = $student['course'];
$year = $student['year'];
$error = '';

$courses = ['B.Tech CSE', 'B.Tech IT'];
$years = ['First Year', 'Second Year', 'Third Year', 'Fourth Year'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid request token. Please reload the form.';
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $error = 'Name must contain between 2 and 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $error = 'Enter a valid email address.';
    } elseif (!in_array($course, $courses, true)) {
        $error = 'Select a valid course.';
    } elseif (!in_array($year, $years, true)) {
        $error = 'Select a valid year.';
    } else {
        try {
            $update = $conn->prepare(
                'UPDATE students
                 SET name = ?, email = ?, course = ?, year = ?
                 WHERE student_id = ?'
            );

            $update->bind_param(
                'ssssi',
                $name,
                $email,
                $course,
                $year,
                $id
            );

            $update->execute();
            $update->close();

            unset($_SESSION['csrf_token']);

            header('Location: manage-students.php?message=updated');
            exit;
        } catch (mysqli_sql_exception $exception) {
            if ((int) $exception->getCode() === 1062) {
                $error = 'Another student already uses this email address.';
            } else {
                error_log('Update student failed: ' . $exception->getMessage());
                $error = 'Could not update the student. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudentHub | Edit Student</title>
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
    <h2>Edit Student #<?= (int) $id ?></h2>

    <?php if ($error !== ''): ?>
        <p role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <form action="edit-student.php?id=<?= (int) $id ?>" method="post">
        <input type="hidden" name="student_id" value="<?= (int) $id ?>">
        <input type="hidden" name="csrf_token"
               value="<?= e($_SESSION['csrf_token']) ?>">

        <p>
            <label for="name">Full Name</label>
            <input id="name" name="name" type="text"
                   maxlength="100" required value="<?= e($name) ?>">
        </p>

        <p>
            <label for="email">Email</label>
            <input id="email" name="email" type="email"
                   maxlength="150" required value="<?= e($email) ?>">
        </p>

        <p>
            <label for="course">Course</label>
            <select id="course" name="course" required>
                <option value="">Select course</option>
                <?php foreach ($courses as $option): ?>
                    <option value="<?= e($option) ?>"
                        <?= $course === $option ? 'selected' : '' ?>>
                        <?= e($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="year">Year</label>
            <select id="year" name="year" required>
                <option value="">Select year</option>
                <?php foreach ($years as $option): ?>
                    <option value="<?= e($option) ?>"
                        <?= $year === $option ? 'selected' : '' ?>>
                        <?= e($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <button type="submit">Save Changes</button>
        <a href="manage-students.php">Cancel</a>
    </form>
</main>
</body>
</html>
