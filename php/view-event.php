
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Event | StudentHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .event-card { max-width: 850px; margin: auto; border: 0; border-radius: 16px; }
        .event-poster { width: 100%; max-height: 400px; object-fit: contain;
                        background: #eef1f5; border-radius: 12px; }
        .detail-label { color: #6c757d; font-size: .9rem; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="card event-card shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <h1 class="h3 mb-0">Event Details</h1>
                <a href="manage-events.php" class="btn btn-outline-secondary">
                    Back to Events
                </a>
            </div>

            <?php if (!empty($event['poster_path'])): ?>
                <img class="event-poster mb-4"
                     src="../<?= e($event['poster_path']) ?>"
                     alt="Poster for <?= e($event['title']) ?>">
            <?php else: ?>
                <div class="alert alert-light border mb-4">
                    No poster has been uploaded for this event.
                </div>
            <?php endif; ?>

            <h2 class="h4 mb-3"><?= e($event['title']) ?></h2>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="detail-label">Event ID</div>
                    <div><?= (int)$event['event_id'] ?></div>
                </div>
                <div class="col-md-6">
                    <div class="detail-label">Category</div>
                    <div><?= e($event['category']) ?></div>
                </div>
                <div class="col-md-6">
                    <div class="detail-label">Date</div>
                    <div><?= e($event['event_date']) ?></div>
                </div>
                <div class="col-md-6">
                    <div class="detail-label">Location</div>
                    <div><?= e($event['location']) ?></div>
                </div>
                <div class="col-md-6">
                    <div class="detail-label">Status</div>
                    <span class="badge <?= $event['status'] === 'open'
                        ? 'text-bg-success' : 'text-bg-secondary' ?>">
                        <?= e(ucfirst($event['status'])) ?>
                    </span>
                </div>
                <div class="col-12">
                    <div class="detail-label">Description</div>
                    <p class="mb-0"><?= nl2br(e($event['description'])) ?></p>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <a href="edit-event.php?id=<?= (int)$event['event_id'] ?>"
                   class="btn btn-primary">Edit Event</a>
                <a href="delete-event.php?id=<?= (int)$event['event_id'] ?>"
                   class="btn btn-outline-danger">Delete Event</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
