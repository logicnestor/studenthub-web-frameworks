
<?php
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/db-mysqli.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrator access is required.');
}

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$search = trim($_GET['search'] ?? '');
$course = trim($_GET['course'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 5;

$allowedCourses = ['B.Tech CSE', 'B.Tech IT'];
if (!in_array($course, $allowedCourses, true)) {
    $course = '';
}

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(name LIKE ? OR email LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($course !== '') {
    $where[] = 'course = ?';
    $params[] = $course;
    $types .= 's';
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = $conn->prepare(
    'SELECT COUNT(*) AS total FROM students' . $whereSql
);

if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$total = (int) $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = 'SELECT student_id, name, email, course, year
        FROM students' . $whereSql .
       ' ORDER BY student_id DESC LIMIT ? OFFSET ?';

$listStmt = $conn->prepare($sql);

$listParams = $params;
$listParams[] = $perPage;
$listParams[] = $offset;
$listTypes = $types . 'ii';

$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();
$students = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listStmt->close();

$notice = $_GET['message'] ?? '';
$messages = [
    'added' => 'Student added successfully.',
    'updated' => 'Student updated successfully.',
    'deleted' => 'Student deleted successfully.'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StudentHub | Manage Students</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header>
    <div class="container">
        <h1>StudentHub</h1>
        <nav aria-label="Admin navigation">
            <a href="../html/admin-dashboard.php">Admin Dashboard</a>
        </nav>
    </div>
</header>

<main class="container">
    <h2>Manage Students</h2>

    <?php if (isset($messages[$notice])): ?>
        <p role="status">
            <?= escapeHtml($messages[$notice]) ?>
        </p>
    <?php endif; ?>

    <p>
        Total matching students:
        <strong><?= $total ?></strong>
    </p>

    <p>
        <a href="add-student.php">+ Add Student</a>
        |
        <a href="export-students.php?<?= escapeHtml(http_build_query([
            'search' => $search,
            'course' => $course
        ])) ?>">Export CSV</a>
    </p>

    <form method="get" action="manage-students.php">
        <label for="search">Search name or email</label>
        <input
            type="search"
            id="search"
            name="search"
            value="<?= escapeHtml($search) ?>"
        >

        <label for="course">Filter by course</label>
        <select id="course" name="course">
            <option value="">All courses</option>
            <?php foreach ($allowedCourses as $option): ?>
                <option
                    value="<?= escapeHtml($option) ?>"
                    <?= $course === $option ? 'selected' : '' ?>
                >
                    <?= escapeHtml($option) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Search / Filter</button>
        <a href="manage-students.php">Reset</a>
    </form>

    <div class="table-container">
        <table>
            <caption>Student Records</caption>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$students): ?>
                <tr>
                    <td colspan="6">No students found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= (int) $student['student_id'] ?></td>
                        <td><?= escapeHtml($student['name']) ?></td>
                        <td><?= escapeHtml($student['email']) ?></td>
                        <td><?= escapeHtml($student['course']) ?></td>
                        <td><?= escapeHtml($student['year']) ?></td>
                        <td>
                            <a href="view-student.php?id=<?= (int) $student['student_id'] ?>">View</a>
                            |
                            <a href="edit-student.php?id=<?= (int) $student['student_id'] ?>">Edit</a>
                            |
                            <a href="delete-student.php?id=<?= (int) $student['student_id'] ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="Pagination">
        <?php if ($page > 1): ?>
            <a href="?<?= escapeHtml(http_build_query([
                'search' => $search,
                'course' => $course,
                'page' => $page - 1
            ])) ?>">Previous</a>
        <?php endif; ?>

        Page <?= $page ?> of <?= $totalPages ?>

        <?php if ($page < $totalPages): ?>
            <a href="?<?= escapeHtml(http_build_query([
                'search' => $search,
                'course' => $course,
                'page' => $page + 1
            ])) ?>">Next</a>
        <?php endif; ?>
    </nav>
</main>
</body>
</html>
