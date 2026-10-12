<?php

$host = "localhost";
$username_db = "root";
$password_db = "30911375";
$database = "studenthub_db";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method Not Allowed");
}

$conn = new mysqli(
    $host,
    $username_db,
    $password_db,
    $database
);

if ($conn->connect_error) {
    error_log("MySQLi connection failed: " . $conn->connect_error);

    http_response_code(500);
    exit("Database connection failed. Please try again later.");
}

$conn->set_charset("utf8mb4");

function clean_input($value)
{
    return trim($value ?? "");
}

$username = clean_input($_POST["username"]);
$name = clean_input($_POST["name"]);
$email = clean_input($_POST["email"]);
$mobile = clean_input($_POST["mobile"]);
$password = $_POST["password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";
$course = clean_input($_POST["course"]);
$year = clean_input($_POST["year"]);
$gender = clean_input($_POST["gender"]);
$terms = isset($_POST["terms"]);

$errors = [];

/*
 * Backend validation
 */

if (!preg_match('/^[A-Za-z0-9_]{4,50}$/', $username)) {
    $errors[] = "Username must contain 4-50 letters, numbers, or underscores.";
}

if (!preg_match('/^[A-Za-z ]{2,50}$/', $name)) {
    $errors[] = "Please enter a valid name.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
    $errors[] = "Please enter a valid 10-digit mobile number.";
}

if (strlen($password) < 8) {
    $errors[] = "Password must contain at least 8 characters.";
}

if (!preg_match('/[A-Z]/', $password)) {
    $errors[] = "Password must contain at least one uppercase letter.";
}

if (!preg_match('/[a-z]/', $password)) {
    $errors[] = "Password must contain at least one lowercase letter.";
}

if (!preg_match('/[0-9]/', $password)) {
    $errors[] = "Password must contain at least one number.";
}

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

$allowed_courses = [
    "cse",
    "it",
    "ce",
    "me"
];

if (!in_array($course, $allowed_courses, true)) {
    $errors[] = "Invalid course selected.";
}

$allowed_years = [
    "first",
    "second",
    "third",
    "fourth"
];

if (!in_array($year, $allowed_years, true)) {
    $errors[] = "Invalid year selected.";
}

$allowed_genders = [
    "male",
    "female",
    "other"
];

if (!in_array($gender, $allowed_genders, true)) {
    $errors[] = "Invalid gender selected.";
}

if (!$terms) {
    $errors[] = "You must accept the terms and conditions.";
}

/*
 * Stop if validation fails
 */

if (!empty($errors)) {

    echo "<!DOCTYPE html>";
    echo "<html lang='en'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Registration Error</title>";
    echo "</head>";

    echo "<body>";

    echo "<h1>Registration Failed</h1>";

    echo "<ul>";

    foreach ($errors as $error) {
        echo "<li>" .
            htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) .
            "</li>";
    }

    echo "</ul>";

    echo "<p>";
    echo "<a href='../html/registration.html'>";
    echo "Return to Registration";
    echo "</a>";
    echo "</p>";

    echo "</body>";
    echo "</html>";

    $conn->close();

    exit;
}

/*
 * Duplicate username/email check
 */

$duplicate_sql = "
    SELECT user_id
    FROM users
    WHERE username = ? OR email = ?
    LIMIT 1
";

$duplicate_stmt = $conn->prepare($duplicate_sql);

if (!$duplicate_stmt) {
    error_log("Duplicate check prepare failed: " . $conn->error);
    http_response_code(500);
    exit("Unable to process registration.");
}

$duplicate_stmt->bind_param(
    "ss",
    $username,
    $email
);

$duplicate_stmt->execute();

$duplicate_result = $duplicate_stmt->get_result();

if ($duplicate_result->num_rows > 0) {

    $duplicate_stmt->close();
    $conn->close();

    echo "<!DOCTYPE html>";
    echo "<html lang='en'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Duplicate Registration</title>";
    echo "</head>";

    echo "<body>";

    echo "<h1>Registration Failed</h1>";

    echo "<p>";
    echo "Username or email already exists.";
    echo "</p>";

    echo "<p>";
    echo "<a href='../html/registration.html'>";
    echo "Return to Registration";
    echo "</a>";
    echo "</p>";

    echo "</body>";
    echo "</html>";

    exit;
}

$duplicate_stmt->close();

/*
 * Hash password
 */

$password_hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

if ($password_hash === false) {
    error_log("Password hashing failed.");

    $conn->close();

    http_response_code(500);
    exit("Unable to securely process the password.");
}

/*
 * Insert user
 */

$insert_sql = "
    INSERT INTO users
    (
        username,
        name,
        email,
        mobile,
        password_hash,
        course,
        year,
        gender,
        created_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
";

$insert_stmt = $conn->prepare($insert_sql);

if (!$insert_stmt) {
    error_log("Insert prepare failed: " . $conn->error);

    $conn->close();

    http_response_code(500);
    exit("Unable to complete registration.");
}

$insert_stmt->bind_param(
    "ssssssss",
    $username,
    $name,
    $email,
    $mobile,
    $password_hash,
    $course,
    $year,
    $gender
);

if ($insert_stmt->execute()) {

    echo "<!DOCTYPE html>";
    echo "<html lang='en'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Registration Successful</title>";
    echo "</head>";

    echo "<body>";

    echo "<h1>Registration Successful</h1>";

    echo "<p>";
    echo "Your StudentHub account has been created successfully.";
    echo "</p>";

    echo "<p>";
    echo "Username: " .
        htmlspecialchars(
            $username,
            ENT_QUOTES,
            "UTF-8"
        );
    echo "</p>";

    echo "<p>";
    echo "<a href='../html/login.html'>";
    echo "Go to Login";
    echo "</a>";
    echo "</p>";

    echo "</body>";
    echo "</html>";

} else {

    error_log("User insertion failed: " . $insert_stmt->error);

    http_response_code(500);

    echo "Registration could not be completed.";
}

$insert_stmt->close();
$conn->close();