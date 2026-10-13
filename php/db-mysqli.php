<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$dbUser = "root";
$dbPassword = "30911375";
$dbName = "studenthub_db";

try {
    $conn = new mysqli(
        $host,
        $dbUser,
        $dbPassword,
        $dbName
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    error_log("MySQLi connection failed: " . $e->getMessage());
    http_response_code(500);
    exit("Database connection failed. Please try again later.");
}