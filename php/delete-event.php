
<?php
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/db-mysqli.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Admin access is required.');
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    header('Location: manage-events.php?error=not_found');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $conn->prepare(
    'SELECT event_id, title, event_date, location, poster_path
     FROM events WHERE event_id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    header('Location: manage-events.php?error=not_found');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'Invalid form token. Refresh the page and try again.';
    } else {
        try {
            $stmt = $conn->prepare('DELETE FROM events WHERE event_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($deleted !== 1) {
                header('Location: manage-events.php?error=not_found');
                exit;
            }

            // Delete only files inside the dedicated event-poster directory.
            $posterPath = $event['poster_path'] ?? '';
            $posterPrefix = 'uploads/event-posters/';

            if (
                $posterPath !== '' &&
                strpos($posterPath, $posterPrefix) === 0 &&
                basename($posterPath) === substr($posterPath, strlen($posterPrefix))
            ) {
                $uploadRoot = realpath(dirname(__DIR__) . '/uploads/event-posters');
                $posterAbsolutePath = $uploadRoot
                    ? $uploadRoot . DIRECTORY_SEPARATOR . basename($posterPath)
                    : false;

                if ($posterAbsolutePath && is_file($posterAbsolutePath)) {
                    if (!unlink($posterAbsolutePath)) {
                        error_log('Could not remove poster for deleted event ID ' . $id);
                    }
                }
            }

            unset($_SESSION['csrf_token']);

            header('Location: manage-events.php?message=deleted');
            exit;
        } catch (mysqli_sql_exception $exception) {
            error_log('Delete event failed: ' . $exception->getMessage());

            if ((int)$exception->getCode() === 1451) {
                $error = 'This event is linked to other records and cannot be deleted.';
            } else {
                $error = 'The event could not be deleted. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delete Event | StudentHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .confirm-card { max-width: 600px; margin: 70px auto; border: 0; border-radius: 16px; }
    </style>
</head>
<body>
<div class="container px-3">
    <div class="card confirm-card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <h1 class="h3 text-danger mb-3">Delete Event?</h1>

            <p>Are you sure you want to delete this event?</p>

            <dl class="row">
                <dt class="col-sm-4">Event ID</dt>
                <dd class="col-sm-8"><?= (int)$event['event_id'] ?></dd>

                <dt class="col-sm-4">Title</dt>
                <dd class="col-sm-8"><?= e($event['title']) ?></dd>

                <dt class="col-sm-4">Date</dt>
                <dd class="col-sm-8"><?= e($event['event_date']) ?></dd>

                <dt class="col-sm-4">Location</dt>
                <dd class="col-sm-8"><?= e($event['location']) ?></dd>
            </dl>

            <div class="alert alert-warning">
                This action cannot be undone. The event's uploaded poster will also
                be removed if the database deletion succeeds.
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST"
                  onsubmit="return confirm('Permanently delete this event?');">
                <input type="hidden" name="id" value="<?= (int)$event['event_id'] ?>">
                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION['csrf_token']) ?>">

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-danger">
                        Yes, Delete Event
                    </button>
                    <a href="manage-events.php" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
