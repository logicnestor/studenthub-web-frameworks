<?php

require_once "db.php";

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>StudentHub Database Test</title>";
echo "</head>";
echo "<body>";

echo "<h1>StudentHub Database Test</h1>";

echo "<p>Database connection successful.</p>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total_students FROM students");
    $result = $stmt->fetch();

    echo "<p>Total students: "
        . htmlspecialchars(
            $result["total_students"],
            ENT_QUOTES,
            "UTF-8"
        )
        . "</p>";

} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    echo "<p>Database query failed.</p>";
}

echo "</body>";
echo "</html>";