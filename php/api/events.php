
<?php
// StudentHub Practical 13: Events JSON API

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../auth-check.php';
require_once __DIR__ . '/../db-mysqli.php';

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function readJsonBody(): array {
    $raw = file_get_contents('php://input');

    if ($raw === false || trim($raw) === '') {
        respond(400, [
            'success' => false,
            'message' => 'A JSON request body is required.'
        ]);
    }

    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        respond(400, [
            'success' => false,
            'message' => 'The request body contains invalid JSON.'
        ]);
    }

    if (!is_array($data)) {
        respond(400, [
            'success' => false,
            'message' => 'The JSON body must be an object.'
        ]);
    }

    return $data;
}

function validateEvent(array $data): array {
    $title = trim((string)($data['title'] ?? ''));
    $category = trim((string)($data['category'] ?? ''));
    $eventDate = trim((string)($data['event_date'] ?? ''));
    $location = trim((string)($data['location'] ?? ''));
    $description = trim((string)($data['description'] ?? ''));
    $status = $data['status'] ?? 'open';

    if ($title === '' || strlen($title) > 150) {
        respond(422, [
            'success' => false,
            'message' => 'Title is required and must be at most 150 characters.'
        ]);
    }

    if ($category === '' || strlen($category) > 100) {
        respond(422, [
            'success' => false,
            'message' => 'Category is required and must be at most 100 characters.'
        ]);
    }

    $date = DateTime::createFromFormat('!Y-m-d', $eventDate);
    if (!$date || $date->format('Y-m-d') !== $eventDate) {
        respond(422, [
            'success' => false,
            'message' => 'Enter a valid event date in YYYY-MM-DD format.'
        ]);
    }

    if ($location === '' || strlen($location) > 255) {
        respond(422, [
            'success' => false,
            'message' => 'Location is required and must be at most 255 characters.'
        ]);
    }

    if ($description === '') {
        respond(422, [
            'success' => false,
            'message' => 'Description is required.'
        ]);
    }

    if (!in_array($status, ['open', 'closed'], true)) {
        respond(422, [
            'success' => false,
            'message' => 'Status must be open or closed.'
        ]);
    }

    return [
        $title,
        $category,
        $eventDate,
        $location,
        $description,
        $status
    ];
}

// All API operations require an administrator.
if (($_SESSION['role'] ?? '') !== 'admin') {
    respond(403, [
        'success' => false,
        'message' => 'Administrator access is required.'
    ]);
}

if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' &&
    ($_GET['action'] ?? '') === 'csrf'
) {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    respond(200, [
        'success' => true,
        'csrf_token' => $_SESSION['csrf_token']
    ]);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $search = trim($_GET['q'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $status = trim($_GET['status'] ?? '');

        if (!in_array($status, ['', 'open', 'closed'], true)) {
            respond(422, [
                'success' => false,
                'message' => 'Invalid status filter.'
            ]);
        }

        $term = '%' . $search . '%';

        $stmt = $conn->prepare(
            'SELECT event_id, title, category, event_date, location,
                    description, poster_path, status
             FROM events
             WHERE (? = "" OR title LIKE ? OR location LIKE ? OR description LIKE ?)
               AND (? = "" OR category = ?)
               AND (? = "" OR status = ?)
             ORDER BY event_date DESC, event_id DESC'
        );

        $stmt->bind_param(
            'ssssssss',
            $search,
            $term,
            $term,
            $term,
            $category,
            $category,
            $status,
            $status
        );

        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $row['event_id'] = (int)$row['event_id'];
            $events[] = $row;
        }

        $stmt->close();

        respond(200, [
            'success' => true,
            'count' => count($events),
            'data' => $events
        ]);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');

        respond(405, [
            'success' => false,
            'message' => 'HTTP method not allowed.'
        ]);
    }

    $data = readJsonBody();

    // Require a session-bound CSRF token for state-changing requests.
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $requestToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (
        !is_string($sessionToken) ||
        $sessionToken === '' ||
        !is_string($requestToken) ||
        !hash_equals($sessionToken, $requestToken)
    ) {
        respond(403, [
            'success' => false,
            'message' => 'Invalid or missing CSRF token. Reload the page and try again.'
        ]);
    }

    $action = $data['action'] ?? '';

    if ($action === 'create') {
        [
            $title,
            $category,
            $eventDate,
            $location,
            $description,
            $status
        ] = validateEvent($data);

        $posterPath = null;

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
        $id = (int)$conn->insert_id;
        $stmt->close();

        respond(201, [
            'success' => true,
            'message' => 'Event created successfully.',
            'id' => $id
        ]);
    }

    if ($action === 'update') {
        $id = filter_var($data['event_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            respond(422, [
                'success' => false,
                'message' => 'A valid event ID is required.'
            ]);
        }

        [
            $title,
            $category,
            $eventDate,
            $location,
            $description,
            $status
        ] = validateEvent($data);

        $stmt = $conn->prepare(
            'UPDATE events
             SET title = ?, category = ?, event_date = ?, location = ?,
                 description = ?, status = ?
             WHERE event_id = ?'
        );

        $stmt->bind_param(
            'ssssssi',
            $title,
            $category,
            $eventDate,
            $location,
            $description,
            $status,
            $id
        );

        $stmt->execute();
        $found = $stmt->affected_rows;
        $stmt->close();

        // affected_rows can be zero when the submitted values are unchanged.
        $check = $conn->prepare(
            'SELECT event_id FROM events WHERE event_id = ?'
        );
        $check->bind_param('i', $id);
        $check->execute();
        $exists = (bool)$check->get_result()->fetch_assoc();
        $check->close();

        if (!$exists) {
            respond(404, [
                'success' => false,
                'message' => 'Event not found.'
            ]);
        }

        respond(200, [
            'success' => true,
            'message' => $found > 0
                ? 'Event updated successfully.'
                : 'Event saved; no field values changed.',
            'id' => $id
        ]);
    }

    if ($action === 'delete') {
        $id = filter_var($data['event_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            respond(422, [
                'success' => false,
                'message' => 'A valid event ID is required.'
            ]);
        }

        $stmt = $conn->prepare('DELETE FROM events WHERE event_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted !== 1) {
            respond(404, [
                'success' => false,
                'message' => 'Event not found.'
            ]);
        }

        respond(200, [
            'success' => true,
            'message' => 'Event deleted successfully.',
            'id' => $id
        ]);
    }

    respond(400, [
        'success' => false,
        'message' => 'Unknown action. Use create, update, or delete.'
    ]);

} catch (mysqli_sql_exception $exception) {
    error_log('Events API database error: ' . $exception->getMessage());

    if ((int)$exception->getCode() === 1451) {
        respond(409, [
            'success' => false,
            'message' => 'This event is linked to other records and cannot be deleted.'
        ]);
    }

    respond(500, [
        'success' => false,
        'message' => 'A database error occurred.'
    ]);
} catch (Throwable $exception) {
    error_log('Events API error: ' . $exception->getMessage());

    respond(500, [
        'success' => false,
        'message' => 'An unexpected server error occurred.'
    ]);
}
