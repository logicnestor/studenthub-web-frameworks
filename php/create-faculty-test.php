
<?php
require_once __DIR__ . '/db-mysqli.php';

$username = 'faculty_test';
$name = 'Faculty Test';
$email = 'faculty.test@studenthub.com';
$mobile = '9876543210';
$password = 'FacultyTest@2026';
$course = 'cse';
$year = 'third';
$gender = 'other';
$role = 'faculty';

try {
    // Avoid creating duplicate accounts.
    $check = $conn->prepare(
        'SELECT user_id FROM users
         WHERE username = ? OR email = ? LIMIT 1'
    );
    $check->bind_param('ss', $username, $email);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing) {
        exit(
            'An account with this username or email already exists. ' .
            'Check the users table in phpMyAdmin.'
        );
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        'INSERT INTO users
        (username, name, email, mobile, password_hash,
         course, year, gender, role, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );

    $stmt->bind_param(
        'sssssssss',
        $username,
        $name,
        $email,
        $mobile,
        $passwordHash,
        $course,
        $year,
        $gender,
        $role
    );

    $stmt->execute();
    $stmt->close();

    echo 'Faculty test account created successfully.<br>';
    echo 'Username: faculty_test<br>';
    echo 'Email: faculty.test@studenthub.com<br>';
    echo 'Temporary test password: FacultyTest@2026<br>';
    echo 'Role: faculty<br>';
    echo '<strong>Delete this PHP script now.</strong>';

} catch (mysqli_sql_exception $e) {
    error_log('Faculty test account creation failed: ' . $e->getMessage());
    http_response_code(500);
    echo 'Account creation failed. Check the PHP error log.';
}
