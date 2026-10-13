
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
if (!$id) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    header('Location: manage-events.php?error=not_found');
    exit;
}

$stmt = $conn->prepare(
    'SELECT event_id, title, category, event_date, location,
            description, poster_path, status
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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

$title = $event['title'];
$category = $event['category'];
$eventDate = $event['event_date'];
$location = $event['location'];
$description = $event['description'];
$status = $event['status'];

$uploadDir = dirname(__DIR__) . '/uploads/event-posters/';
$relativeUploadDir = 'uploads/event-posters/';
$maxFileSize = 2 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'open';

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please reload the page and try again.';
    }

    if ($title === '' || strlen($title) > 150) {
        $errors[] = 'Title is required and must be at most 150 characters.';
    }

    if ($category === '' || strlen($category) > 100) {
        $errors[] = 'Category is required and must be at most 100 characters.';
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $eventDate);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $eventDate) {
        $errors[] = 'Please enter a valid event date.';
    }

    if ($location === '' || strlen($location) > 255) {
        $errors[] = 'Location is required and must be at most 255 characters.';
    }

    if ($description === '') {
        $errors[] = 'Description is required.';
    }

    if (!in_array($status, ['open', 'closed'], true)) {
        $errors[] = 'Please select a valid event status.';
    }

    $newPosterPath = $event['poster_path'];
    $uploadedAbsolutePath = null;
    $oldPosterPath = $event['poster_path'];

    $file = $_FILES['poster'] ?? null;
    $hasUpload = $file && $file['error'] !== UPLOAD_ERR_NO_FILE;

    if ($hasUpload) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'The poster upload failed. Please select the file again.';
        } elseif ($file['size'] <= 0 || $file['size'] > $maxFileSize) {
            $errors[] = 'Poster must be a non-empty image no larger than 2 MB.';
        } elseif (!is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid uploaded file.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $imageInfo = @getimagesize($file['tmp_name']);

            if (
                !isset($allowedTypes[$mime]) ||
                $imageInfo === false ||
                ($imageInfo['mime'] ?? '') !== $mime
            ) {
                $errors[] = 'Invalid poster. Upload a valid JPG, PNG, or WebP image.';
            } else {
                $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
                $newPosterPath = $relativeUploadDir . $filename;
                $uploadedAbsolutePath = $uploadDir . $filename;
            }
        }
    }

    if (!$errors && $uploadedAbsolutePath !== null) {
        if (!is_dir($uploadDir) &&
            !mkdir($uploadDir, 0755, true) &&
            !is_dir($uploadDir)) {
            $errors[] = 'Could not create the poster upload directory.';
        } elseif (!move_uploaded_file($file['tmp_name'], $uploadedAbsolutePath)) {
            $errors[] = 'Could not save the uploaded poster.';
        }
    }

    if (!$errors) {
        try {
            $stmt = $conn->prepare(
                'UPDATE events
                 SET title = ?, category = ?, event_date = ?, location = ?,
                     description = ?, poster_path = ?, status = ?
                 WHERE event_id = ?'
            );

            $stmt->bind_param(
                'sssssssi',
                $title,
                $category,
                $eventDate,
                $location,
                $description,
                $newPosterPath,
                $status,
                $id
            );

            $stmt->execute();
            $stmt->close();

            // Remove the previous poster only after the database update succeeds.
            if (
                $uploadedAbsolutePath !== null &&
                $oldPosterPath &&
                strpos($oldPosterPath, $relativeUploadDir) === 0
            ) {
                $oldAbsolutePath = dirname(__DIR__) . '/' . $oldPosterPath;
                if (is_file($oldAbsolutePath)) {
                    unlink($oldAbsolutePath);
                }
            }

            unset($_SESSION['csrf_token']);

            header('Location: manage-events.php?message=updated');
            exit;
        } catch (Throwable $exception) {
            if ($uploadedAbsolutePath && is_file($uploadedAbsolutePath)) {
                unlink($uploadedAbsolutePath);
            }

            error_log('Edit event failed: ' . $exception->getMessage());
            $errors[] = 'The event could not be updated. Please try again.';
        }
    } elseif ($uploadedAbsolutePath && is_file($uploadedAbsolutePath)) {
        unlink($uploadedAbsolutePath);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Event | StudentHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .form-card { max-width: 850px; margin: auto; border: 0; border-radius: 16px; }
        .current-poster { max-width: 240px; max-height: 180px; object-fit: contain; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card form-card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div>
                    <h1 class="h3 mb-1">Edit Event</h1>
                    <p class="text-secondary mb-0">Event ID: <?= (int)$event['event_id'] ?></p>
                </div>
                <a href="manage-events.php" class="btn btn-outline-secondary">Back to Events</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <strong>Please fix the following:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= (int)$event['event_id'] ?>">
                <input type="hidden" name="csrf_token"
                       value="<?= e($_SESSION['csrf_token']) ?>">

                <div class="mb-3">
                    <label for="title" class="form-label">Event Title *</label>
                    <input id="title" name="title" class="form-control"
                           maxlength="150" required value="<?= e($title) ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label">Category *</label>
                        <input id="category" name="category" class="form-control"
                               maxlength="100" required value="<?= e($category) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="event_date" class="form-label">Event Date *</label>
                        <input type="date" id="event_date" name="event_date"
                               class="form-control" required value="<?= e($eventDate) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Location *</label>
                    <input id="location" name="location" class="form-control"
                           maxlength="255" required value="<?= e($location) ?>">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description *</label>
                    <textarea id="description" name="description"
                              class="form-control" rows="4" required><?= e($description) ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Event Status *</label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="poster" class="form-label">Replace Poster (optional)</label>

                        <?php if (!empty($event['poster_path'])): ?>
                            <div class="mb-2">
                                <img class="current-poster d-block mb-2"
                                     src="../<?= e($event['poster_path']) ?>"
                                     alt="Current event poster">
                                <span class="form-text">Current poster. Leave the upload blank to keep it.</span>
                            </div>
                        <?php else: ?>
                            <p class="form-text">No poster uploaded yet.</p>
                        <?php endif; ?>

                        <input type="file" id="poster" name="poster"
                               class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        <div class="form-text">JPG, PNG, or WebP. Maximum size: 2 MB.</div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="manage-events.php" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
