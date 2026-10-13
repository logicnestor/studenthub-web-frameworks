<?php
require_once __DIR__ . "/auth-check.php";

if ($_SESSION["role"] !== "admin") {
    header("Location: student-dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StudentHub</title>
</head>
<body>
    <h1>Admin Dashboard</h1>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION["name"], ENT_QUOTES, "UTF-8") ?>.
    </p>

    <p>Role: Administrator</p>

    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>
</body>
</html>