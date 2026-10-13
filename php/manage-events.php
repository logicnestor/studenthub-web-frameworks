
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

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$status = trim($_GET['status'] ?? '');

$allowedStatuses = ['open', 'closed'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(title LIKE ? OR location LIKE ? OR description LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= 'sss';
}

if ($category !== '') {
    $where[] = 'category = ?';
    $params[] = $category;
    $types .= 's';
}

if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
    $types .= 's';
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countSql = 'SELECT COUNT(*) AS total FROM events' . $whereSql;
$countStmt = $conn->prepare($countSql);

if ($params) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalEvents = (int)$countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = max(1, (int)ceil($totalEvents / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = 'SELECT event_id, title, category, event_date, location,
               description, poster_path, status
        FROM events' . $whereSql . '
        ORDER BY event_date DESC, event_id DESC
        LIMIT ? OFFSET ?';

$stmt = $conn->prepare($sql);

if ($params) {
    $allTypes = $types . 'ii';
    $allParams = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($allTypes, ...$allParams);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();

$categoryResult = $conn->query(
    "SELECT DISTINCT category FROM events
     WHERE category IS NOT NULL AND category <> ''
     ORDER BY category"
);

$categories = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row['category'];
}

$messageMap = [
    'added' => 'Event added successfully.',
    'updated' => 'Event updated successfully.',
    'deleted' => 'Event deleted successfully.'
];

$errorMap = [
    'not_found' => 'The requested event was not found.',
    'invalid' => 'Please check the event details and try again.',
    'upload' => 'The poster could not be uploaded. Check the file type and size.',
    'delete_failed' => 'The event could not be deleted. It may be linked to other records.'
];

$messageKey = $_GET['message'] ?? '';
$errorKey = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | StudentHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .page-header { display:flex; justify-content:space-between;
                       align-items:center; gap:1rem; flex-wrap:wrap; }
        .event-poster { width:100px; height:65px; object-fit:cover;
                        border-radius:8px; background:#e9ecef; }
        .description-cell { max-width:220px; }
        .table td, .table th { vertical-align:middle; }
        .filter-card, .table-card { border:0; border-radius:14px; }
        @media (max-width: 768px) {
            .event-poster { width:75px; height:55px; }
        }
    </style>
</head>
<body>
<div class="container-fluid container-lg py-4">
    <div class="page-header mb-4">
        <div>
            <h1 class="h3 mb-1">Event Management</h1>
            <p class="text-secondary mb-0">Manage StudentHub events and posters.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="../html/admin-dashboard.php" class="btn btn-outline-secondary">
                Admin Dashboard
            </a>
            <a href="add-event.php" class="btn btn-primary">+ Add Event</a>
        </div>
    </div>

    <?php if (isset($messageMap[$messageKey])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= e($messageMap[$messageKey]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"
                    aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMap[$errorKey])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= e($errorMap[$errorKey]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"
                    aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card filter-card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="manage-events.php"
                  class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="search" class="form-label">Search events</label>
                    <input type="search" id="search" name="search"
                           class="form-control"
                           placeholder="Title, location or description"
                           value="<?= e($search) ?>">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select id="category" name="category" class="form-select">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $item): ?>
                            <option value="<?= e($item) ?>"
                                <?= $category === $item ? 'selected' : '' ?>>
                                <?= e($item) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>
                            Open
                        </option>
                        <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>
                            Closed
                        </option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">
                        Filter
                    </button>
                    <a href="manage-events.php" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-secondary">
            <?= $totalEvents ?> event(s) found
        </span>
        <span class="text-secondary small">
            Page <?= $page ?> of <?= $totalPages ?>
        </span>
    </div>

    <div class="card table-card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Poster</th>
                        <th>Event</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$events): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-secondary">
                            No events match your search.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <?php if (!empty($event['poster_path'])): ?>
                                    <img class="event-poster"
                                         src="../<?= e($event['poster_path']) ?>"
                                         alt="Poster for <?= e($event['title']) ?>">
                                <?php else: ?>
                                    <div class="event-poster d-flex align-items-center
                                                justify-content-center text-secondary small">
                                        No poster
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($event['title']) ?></strong>
                                <div class="small text-secondary description-cell">
                                    <?= e($event['description']) ?>
                                </div>
                            </td>
                            <td><?= e($event['category']) ?></td>
                            <td><?= e($event['event_date']) ?></td>
                            <td><?= e($event['location']) ?></td>
                            <td>
                                <span class="badge <?= $event['status'] === 'open'
                                    ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= e(ucfirst($event['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="view-event.php?id=<?= (int)$event['event_id'] ?>">
                                        View
                                    </a>
                                    <a class="btn btn-sm btn-outline-warning"
                                       href="edit-event.php?id=<?= (int)$event['event_id'] ?>">
                                        Edit
                                    </a>
                                    <a class="btn btn-sm btn-outline-danger"
                                       href="delete-event.php?id=<?= (int)$event['event_id'] ?>">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-4" aria-label="Event pages">
            <ul class="pagination justify-content-center flex-wrap">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php
                    $query = $_GET;
                    $query['page'] = $p;
                    $url = 'manage-events.php?' . http_build_query($query);
                    ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e($url) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
