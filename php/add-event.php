
<?php
session_start();

require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/db-mysqli.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Admin access is required.');
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$title = '';
$category = '';
$eventDate = '';
$location = '';
$description = '';
$status = 'open';

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

    $posterPath = null;
    $uploadedAbsolutePath = null;

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
                $extension = $allowedTypes[$mime];
                $filename = bin2hex(random_bytes(16)) . '.' . $extension;
                $posterPath = $relativeUploadDir . $filename;
                $uploadedAbsolutePath = $uploadDir . $filename;
            }
        }
    }

    if (!$errors) {
        if (!is_dir($uploadDir) &&
            !mkdir($uploadDir, 0755, true) &&
            !is_dir($uploadDir)) {
            $errors[] = 'Could not create the poster upload directory.';
        }
    }

    if (!$errors && $posterPath !== null) {
        if (!move_uploaded_file($file['tmp_name'], $uploadedAbsolutePath)) {
            $errors[] = 'Could not save the uploaded poster.';
        }
    }

    if (!$errors) {
        try {
            $stmt = $conn->prepare(
                'INSERT INTO events
                 (title, category, event_date, location, description, poster_path, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'sssssss',
                $title,
                $category,
                $eventDate,
                $location,
                $description,
                $posterPath,
                $status
            );

            $stmt->execute();
            $stmt->close();

            unset($_SESSION['csrf_token']);

            header('Location: manage-events.php?message=added');
            exit;
        } catch (Throwable $exception) {
            if ($uploadedAbsolutePath && is_file($uploadedAbsolutePath)) {
                unlink($uploadedAbsolutePath);
            }

            error_log('Add event failed: ' . $exception->getMessage());
            $errors[] = 'The event could not be saved. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Event | StudentHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .form-card { max-width: 850px; margin: auto; border: 0; border-radius: 16px; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card form-card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div>
                    <h1 class="h3 mb-1">Add New Event</h1>
                    <p class="text-secondary mb-0">Enter event information and an optional poster.</p>
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
                               maxlength="100" required value="<?= e($category) ?>"
                               placeholder="Workshop, Seminar, Sports...">
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
                    <textarea id="description" name="description" class="form-control"
                              rows="4" required><?= e($description) ?></textarea>
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
                        <label for="poster" class="form-label">Event Poster (optional)</label>
                        <input type="hidden" name="MAX_FILE_SIZE" value="2097152">
                        <input type="file" id="poster" name="poster"
                               class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        <div class="form-text">JPG, PNG, or WebP. Maximum size: 2 MB.</div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">Save Event</button>
                    <a href="manage-events.php" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
