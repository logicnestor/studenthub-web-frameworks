
<?php
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/db-mysqli.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrator access is required.');
}

$search = trim($_GET['search'] ?? '');
$course = trim($_GET['course'] ?? '');

$allowedCourses = ['B.Tech CSE', 'B.Tech IT'];

if (!in_array($course, $allowedCourses, true)) {
    $course = '';
}

$sql = 'SELECT student_id, name, email, course, year
        FROM students';

if ($search !== '' && $course !== '') {
    $sql .= ' WHERE (name LIKE ? OR email LIKE ?) AND course = ?';
} elseif ($search !== '') {
    $sql .= ' WHERE name LIKE ? OR email LIKE ?';
} elseif ($course !== '') {
    $sql .= ' WHERE course = ?';
}

$sql .= ' ORDER BY student_id ASC';

$stmt = $conn->prepare($sql);

if ($search !== '' && $course !== '') {
    $like = '%' . $search . '%';
    $stmt->bind_param('sss', $like, $like, $course);
} elseif ($search !== '') {
    $like = '%' . $search . '%';
    $stmt->bind_param('ss', $like, $like);
} elseif ($course !== '') {
    $stmt->bind_param('s', $course);
}

$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="studenthub-students.csv"');
header('X-Content-Type-Options: nosniff');

$output = fopen('php://output', 'w');

// UTF-8 BOM improves compatibility with spreadsheet applications.
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    'Student ID',
    'Name',
    'Email',
    'Course',
    'Year'
]);

while ($student = $result->fetch_assoc()) {
    $row = [];

    foreach ($student as $value) {
        $value = (string) $value;

        // Reduce CSV formula-injection risk in spreadsheet applications.
        if (preg_match('/^[\x00-\x20]*[=+\-@]/', $value)) {
            $value = "'" . $value;
        }

        $row[] = $value;
    }

    fputcsv($output, $row);
}

fclose($output);
$stmt->close();
$conn->close();
exit;
