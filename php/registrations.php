<?php

// ==========================================
// StudentHub Stored Registrations
// Practical 7 - Intermediate Extension
// ==========================================

$storageFile = __DIR__ . "/data/registrations.json";

$registrations = [];
$errorMessage = "";

if (file_exists($storageFile)) {

    $jsonData = file_get_contents($storageFile);

    if ($jsonData !== false && trim($jsonData) !== "") {

        $decodedData = json_decode($jsonData, true);

        if (is_array($decodedData)) {
            $registrations = $decodedData;
        } else {
            $errorMessage = "Unable to read registration data.";
        }

    }

} else {

    $errorMessage = "Registration data file does not exist.";

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>StudentHub | Registrations</title>

    <link rel="stylesheet"
          href="../css/style.css">

</head>

<body>

<header>

    <div class="container">

        <h1>StudentHub</h1>

        <nav aria-label="Main Navigation">

            <a href="../html/index.html">
                Home
            </a>

            <a href="../html/registration.html">
                Registration
            </a>

            <a href="registrations.php"
               aria-current="page">
                Registrations
            </a>

        </nav>

    </div>

</header>


<main>

    <section class="registration-records">

        <h2>Registered Students</h2>

        <p>
            Registration records stored by the StudentHub
            PHP backend.
        </p>


        <?php if ($errorMessage !== ""): ?>

            <p class="form-message error">
                <?php
                    echo htmlspecialchars(
                        $errorMessage,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                ?>
            </p>

        <?php elseif (empty($registrations)): ?>

            <p class="form-message">
                No registration records found.
            </p>

        <?php else: ?>

            <div class="table-container">

                <table>

                    <caption>
                        Student Registration Records
                    </caption>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Name</th>

                            <th>Email</th>

                            <th>Mobile</th>

                            <th>Course</th>

                            <th>Year</th>

                            <th>Gender</th>

                            <th>Registered At</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach (
                            $registrations as $registration
                        ): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registration["id"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registration["name"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registration["email"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registration["mobile"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registration["course"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registration["year"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registration["gender"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $registration["registered_at"] ?? "",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>


<footer>

    <div class="container">

        <p>
            &copy; 2026 StudentHub. All Rights Reserved.
        </p>

    </div>

</footer>

</body>

</html>