<?php

require_once "db.php";

$search = trim($_GET["search"] ?? "");
$students = [];
$error = "";

try {
    $sql = "
    SELECT student_id, name, email, course, year
    FROM students
    WHERE name LIKE :name_search
       OR email LIKE :email_search
    ORDER BY name ASC
";

$stmt = $pdo->prepare($sql);

$searchTerm = "%" . $search . "%";

$stmt->execute([
    "name_search" => $searchTerm,
    "email_search" => $searchTerm
]);

    $students = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Student search failed: " . $e->getMessage());
    $error = "Unable to load student data.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Student Search - StudentHub</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #333;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #1f3c88;
        }

        form {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        input[type="search"] {
            flex: 1;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        button {
            padding: 12px 20px;
            background: #1f3c88;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #162d68;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #1f3c88;
            color: white;
        }

        .message {
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 6px;
        }

        .error {
            background: #f8d7da;
            border-color: #f5c6cb;
        }

        @media (max-width: 700px) {
            form {
                flex-direction: column;
            }

            table {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Student Search</h1>

    <form method="get" action="student-search.php">

        <label for="search">Search students:</label>

        <input
            type="search"
            id="search"
            name="search"
            value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>"
            placeholder="Enter student name or email"
        >

        <button type="submit">Search</button>

    </form>

    <?php if ($error): ?>

        <p class="message error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>

    <?php elseif (count($students) === 0): ?>

        <p class="message">
            No students found.
        </p>

    <?php else: ?>

        <table>

            <caption>
                Student Search Results
            </caption>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Year</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach ($students as $student): ?>

                <tr>
                    <td>
                        <?= htmlspecialchars(
                            $student["student_id"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $student["name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $student["email"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $student["course"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $student["year"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    <?php endif; ?>

</div>

</body>
</html>