<?php

$isHttps = !empty($_SERVER["HTTPS"])
    && $_SERVER["HTTPS"] !== "off";

ini_set("session.use_strict_mode", "1");
ini_set("session.use_only_cookies", "1");

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/studenthub-web-frameworks/",
    "secure" => $isHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    http_response_code(405);
    exit("Method Not Allowed");
}

require_once __DIR__ . "/db-mysqli.php";
require_once __DIR__ . '/remember-me.php';

$identifier = trim($_POST["login_identifier"] ?? "");
$password = $_POST["password"] ?? "";

if ($identifier === "" || $password === "") {
    header("Location: ../html/login.html?reason=invalid");
    exit;
}

try {
    $sql = "
        SELECT user_id, username, name, email,
               password_hash, role
        FROM users
        WHERE username = ? OR email = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    if (!$user || !password_verify($password, $user["password_hash"])) {
        header("Location: ../html/login.html?reason=invalid");
        exit;
    }

    if (!in_array($user['role'], ['student', 'faculty', 'admin'], true)) {
    http_response_code(403);
    exit('Account role is not permitted.');
}

    

    // Prevent session fixation after successful authentication.
    session_regenerate_id(true);

    $_SESSION["user_id"] = (int) $user["user_id"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["name"] = $user["name"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["role"] = $user["role"];
    $_SESSION["last_activity"] = time();

    // Intermediate extension: record last successful login.
    $update = $conn->prepare(
        "UPDATE users SET last_login = NOW() WHERE user_id = ?"
    );

    $update->bind_param("i", $_SESSION["user_id"]);
    $update->execute();
    $update->close();

    
if (isset($_POST['remember_me']) && $_POST['remember_me'] === '1') {
    issueRememberToken($conn, (int) $user['user_id']);
} else {
    clearRememberCookie($conn);
}

if ($_SESSION["role"] === "admin") {
    header("Location: ../html/admin-dashboard.php");
} elseif ($_SESSION["role"] === "faculty") {
    header("Location: ../html/faculty-dashboard.php");
}
else {
    header("Location: ../html/student-dashboard.php");
}

exit;


} catch (mysqli_sql_exception $e) {
    error_log("Login processing failed: " . $e->getMessage());
    http_response_code(500);
    exit("Login could not be processed. Please try again later.");
}