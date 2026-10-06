<?php

// ==========================================
// StudentHub Registration Processor
// Practical 7
// PHP Server-Side Validation + JSON Storage
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    exit("Invalid request method.");
}


// ==========================================
// Helper Function
// ==========================================

function clean_input($value)
{
    return trim(
        htmlspecialchars(
            stripslashes($value),
            ENT_QUOTES,
            "UTF-8"
        )
    );
}


// ==========================================
// Retrieve Submitted Data
// ==========================================

$name = clean_input($_POST["name"] ?? "");

$email = clean_input($_POST["email"] ?? "");

$mobile = clean_input($_POST["mobile"] ?? "");

$password = $_POST["password"] ?? "";

$confirmPassword = $_POST["confirm-password"] ?? "";

$course = clean_input($_POST["course"] ?? "");

$year = clean_input($_POST["year"] ?? "");

$gender = clean_input($_POST["gender"] ?? "");

$terms = isset($_POST["terms"]);


// ==========================================
// Validation
// ==========================================

$errors = [];


// Name

if ($name === "") {

    $errors[] = "Name is required.";

} elseif (
    !preg_match(
        "/^[A-Za-z ]{2,50}$/",
        $name
    )
) {

    $errors[] =
        "Name must contain only letters and spaces.";

}


// Email

if ($email === "") {

    $errors[] = "Email is required.";

} elseif (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    $errors[] =
        "Please enter a valid email address.";

}


// Mobile

if ($mobile === "") {

    $errors[] =
        "Mobile number is required.";

} elseif (
    !preg_match(
        "/^[6-9][0-9]{9}$/",
        $mobile
    )
) {

    $errors[] =
        "Please enter a valid 10-digit mobile number.";

}


// Password

if ($password === "") {

    $errors[] =
        "Password is required.";

} elseif (strlen($password) < 8) {

    $errors[] =
        "Password must contain at least 8 characters.";

} elseif (!preg_match("/[A-Z]/", $password)) {

    $errors[] =
        "Password must contain at least one uppercase letter.";

} elseif (!preg_match("/[a-z]/", $password)) {

    $errors[] =
        "Password must contain at least one lowercase letter.";

} elseif (!preg_match("/[0-9]/", $password)) {

    $errors[] =
        "Password must contain at least one number.";

} elseif (!preg_match("/[^A-Za-z0-9]/", $password)) {

    $errors[] =
        "Password must contain at least one special character.";

}


// Confirm Password

if ($confirmPassword === "") {

    $errors[] =
        "Please confirm your password.";

} elseif ($password !== $confirmPassword) {

    $errors[] =
        "Passwords do not match.";

}


// Course

$allowedCourses = [
    "cse",
    "it",
    "ce",
    "me"
];

if (!in_array($course, $allowedCourses, true)) {

    $errors[] =
        "Please select a valid course.";

}


// Year

$allowedYears = [
    "first",
    "second",
    "third",
    "fourth"
];

if (!in_array($year, $allowedYears, true)) {

    $errors[] =
        "Please select a valid year.";

}


// Gender

$allowedGenders = [
    "male",
    "female",
    "other"
];

if (!in_array($gender, $allowedGenders, true)) {

    $errors[] =
        "Please select a valid gender.";

}


// Terms

if (!$terms) {

    $errors[] =
        "You must accept the Terms and Conditions.";

}


// ==========================================
// Display Validation Errors
// ==========================================

if (!empty($errors)) {

    echo "<!DOCTYPE html>";
    echo "<html lang='en'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>StudentHub | Registration Error</title>";
    echo "<link rel='stylesheet' href='../css/style.css'>";
    echo "</head>";

    echo "<body>";

    echo "<main>";

    echo "<section>";

    echo "<h1>Registration Failed</h1>";

    echo "<p>Please correct the following errors:</p>";

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
    echo "<a href='../html/registration.html' class='btn'>";
    echo "Back to Registration";
    echo "</a>";
    echo "</p>";

    echo "</section>";

    echo "</main>";

    echo "</body>";
    echo "</html>";

    exit;
}


// ==========================================
// Prepare Storage File
// ==========================================

$storageFile =
    __DIR__ . "/data/registrations.json";


// ==========================================
// Read Existing Records
// ==========================================

$registrations = [];

if (file_exists($storageFile)) {

    $jsonData =
        file_get_contents($storageFile);

    if ($jsonData !== false &&
        trim($jsonData) !== "") {

        $decodedData =
            json_decode(
                $jsonData,
                true
            );

        if (is_array($decodedData)) {

            $registrations =
                $decodedData;

        }

    }

}


// ==========================================
// Create New Registration
// ==========================================

$newRegistration = [

    "id" => uniqid(
        "student_",
        true
    ),

    "name" => $name,

    "email" => $email,

    "mobile" => $mobile,

    "course" => $course,

    "year" => $year,

    "gender" => $gender,

    "registered_at" =>
        date("Y-m-d H:i:s")

];


// ==========================================
// Add Record
// ==========================================

$registrations[] =
    $newRegistration;


// ==========================================
// Save JSON
// ==========================================

$jsonOutput =
    json_encode(
        $registrations,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_SLASHES
    );


if ($jsonOutput === false) {

    http_response_code(500);

    exit(
        "Unable to prepare registration data."
    );

}


if (
    file_put_contents(
        $storageFile,
        $jsonOutput,
        LOCK_EX
    ) === false
) {

    http_response_code(500);

    exit(
        "Unable to save registration data."
    );

}


// ==========================================
// Success Response
// ==========================================

echo "<!DOCTYPE html>";
echo "<html lang='en'>";

echo "<head>";

echo "<meta charset='UTF-8'>";

echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";

echo "<title>StudentHub | Registration Successful</title>";

echo "<link rel='stylesheet' href='../css/style.css'>";

echo "</head>";

echo "<body>";

echo "<main>";

echo "<section>";

echo "<h1>Registration Successful!</h1>";

echo "<p>";
echo "Your StudentHub registration has been successfully processed.";
echo "</p>";

echo "<p>";
echo "<a href='../html/registration.html' class='btn'>";
echo "Register Another Student";
echo "</a>";
echo "</p>";

echo "<p>";
echo "<a href='../html/index.html'>";
echo "Return to Home";
echo "</a>";
echo "</p>";

echo "</section>";

echo "</main>";

echo "</body>";

echo "</html>";

?>