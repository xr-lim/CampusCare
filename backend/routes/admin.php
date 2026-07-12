<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once '../config/database.php';
require_once '../middleware/JwtMiddleware.php';
require_once '../middleware/RoleMiddleware.php';

function adminJsonResponse(Response $response, array $payload, int $status = 200): Response
{
    $response->getBody()->write(json_encode($payload));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

function adminAllowedStatuses(): array
{
    return ['Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled', 'Rejected'];
}

function adminAllowedUrgencies(): array
{
    return ['Low', 'Medium', 'High'];
}

function adminFindRequestSummary(PDO $pdo, int $requestId): ?array
{
    $stmt = $pdo->prepare('SELECT id, status FROM maintenance_requests WHERE id = ?');
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    return $request ?: null;
}

function adminValidateInteger($value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    if (filter_var($value, FILTER_VALIDATE_INT) === false) {
        return null;
    }

    return (int) $value;
}

$app->get('/admin/dashboard', function (Request $request, Response $response) use ($pdo) {
    $countsStmt = $pdo->query(
        'SELECT status, COUNT(*) AS total
         FROM maintenance_requests
         GROUP BY status'
    );

    $counts = array_fill_keys(adminAllowedStatuses(), 0);
    while ($row = $countsStmt->fetch(PDO::FETCH_ASSOC)) {
        $counts[$row['status']] = (int) $row['total'];
    }

    $totalRequests = array_sum($counts);

    $recentStmt = $pdo->query(
        'SELECT
            mr.id,
            mr.description,
            mr.priority AS urgency,
            mr.status,
            mr.created_at,
            requester.username AS requester_name,
            categories.name AS category_name,
            locations.name AS location_name
         FROM maintenance_requests mr
         INNER JOIN users requester ON requester.id = mr.user_id
         INNER JOIN categories ON categories.id = mr.category_id
         INNER JOIN locations ON locations.id = mr.location_id
         ORDER BY mr.updated_at DESC, mr.created_at DESC
         LIMIT 5'
    );

    return adminJsonResponse($response, [
        'summary' => [
            'total' => $totalRequests,
            'pending' => $counts['Pending'],
            'assigned' => $counts['Assigned'],
            'inProgress' => $counts['In Progress'],
            'completed' => $counts['Completed'],
            'cancelled' => $counts['Cancelled'],
            'rejected' => $counts['Rejected']
        ],
        'recentRequests' => $recentStmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
})->add(new RoleMiddleware(['Admin']))->add(new JwtMiddleware());

$app->get('/admin/lookups', function (Request $request, Response $response) use ($pdo) {
    $categories = $pdo->query(
        'SELECT id, name
         FROM categories
         ORDER BY name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    $locations = $pdo->query(
        'SELECT id, name
         FROM locations
         ORDER BY name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    $techniciansStmt = $pdo->prepare(
        "SELECT id, username, email
         FROM users
         WHERE role = 'Technician'
         ORDER BY username ASC"
    );
    $techniciansStmt->execute();

    return adminJsonResponse($response, [
        'categories' => $categories,
        'locations' => $locations,
        'technicians' => $techniciansStmt->fetchAll(PDO::FETCH_ASSOC),
        'statuses' => adminAllowedStatuses(),
        'urgencies' => adminAllowedUrgencies()
    ]);
})->add(new RoleMiddleware(['Admin']))->add(new JwtMiddleware());

$app->get('/admin/requests', function (Request $request, Response $response) use ($pdo) {
    $params = $request->getQueryParams();
    $where = [];
    $values = [];

    $status = trim($params['status'] ?? '');
    if ($status !== '') {
        if (!in_array($status, adminAllowedStatuses(), true)) {
            return adminJsonResponse($response, ['message' => 'Invalid status filter'], 400);
        }

        $where[] = 'mr.status = ?';
        $values[] = $status;
    }

    $urgency = trim($params['urgency'] ?? '');
    if ($urgency !== '') {
        if (!in_array($urgency, adminAllowedUrgencies(), true)) {
            return adminJsonResponse($response, ['message' => 'Invalid urgency filter'], 400);
        }

        $where[] = 'mr.priority = ?';
        $values[] = $urgency;
    }

    foreach (['category_id', 'location_id', 'technician_id'] as $field) {
        $value = adminValidateInteger($params[$field] ?? null);
        if (($params[$field] ?? '') !== '' && $value === null) {
            return adminJsonResponse($response, ['message' => 'Invalid filter value supplied'], 400);
        }

        if ($value !== null) {
            $column = match ($field) {
                'category_id' => 'mr.category_id',
                'location_id' => 'mr.location_id',
                default => 'mr.assigned_technician_id'
            };

            $where[] = $column . ' = ?';
            $values[] = $value;
        }
    }

    $search = trim($params['search'] ?? '');
    if ($search !== '') {
        $where[] = '(requester.username LIKE ? OR requester.email LIKE ? OR mr.description LIKE ?)';
        $searchTerm = '%' . $search . '%';
        array_push($values, $searchTerm, $searchTerm, $searchTerm);
    }

    $sql = 'SELECT
                mr.id,
                mr.description,
                NULL AS photo_path,
                mr.priority AS urgency,
                mr.status,
                mr.created_at,
                mr.updated_at,
                requester.id AS requester_id,
                requester.username AS requester_name,
                requester.email AS requester_email,
                categories.id AS category_id,
                categories.name AS category_name,
                locations.id AS location_id,
                locations.name AS location_name,
                technician.id AS technician_id,
                technician.username AS technician_name
            FROM maintenance_requests mr
            INNER JOIN users requester ON requester.id = mr.user_id
            INNER JOIN categories ON categories.id = mr.category_id
            INNER JOIN locations ON locations.id = mr.location_id
            LEFT JOIN users technician ON technician.id = mr.assigned_technician_id';

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY mr.updated_at DESC, mr.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    return adminJsonResponse($response, [
        'requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
})->add(new RoleMiddleware(['Admin']))->add(new JwtMiddleware());

$app->get('/admin/requests/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
    $requestId = adminValidateInteger($args['id'] ?? null);

    if ($requestId === null) {
        return adminJsonResponse($response, ['message' => 'Invalid request id'], 400);
    }

    $stmt = $pdo->prepare(
        'SELECT
            mr.id,
            mr.description,
            NULL AS photo_path,
            mr.priority AS urgency,
            mr.status,
            mr.created_at,
            mr.updated_at,
            requester.id AS requester_id,
            requester.username AS requester_name,
            requester.email AS requester_email,
            categories.id AS category_id,
            categories.name AS category_name,
            locations.id AS location_id,
            locations.name AS location_name,
            technician.id AS technician_id,
            technician.username AS technician_name,
            technician.email AS technician_email
         FROM maintenance_requests mr
         INNER JOIN users requester ON requester.id = mr.user_id
         INNER JOIN categories ON categories.id = mr.category_id
         INNER JOIN locations ON locations.id = mr.location_id
         LEFT JOIN users technician ON technician.id = mr.assigned_technician_id
         WHERE mr.id = ?'
    );

    $stmt->execute([$requestId]);
    $requestDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$requestDetails) {
        return adminJsonResponse($response, ['message' => 'Maintenance request not found'], 404);
    }

    $imageStmt = $pdo->prepare('SELECT id, original_filename, mime_type, file_size, created_at FROM request_images WHERE request_id = ? ORDER BY id');
    $imageStmt->execute([$requestId]);
    $requestDetails['images'] = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

    return adminJsonResponse($response, [
        'request' => $requestDetails
    ]);
})->add(new RoleMiddleware(['Admin']))->add(new JwtMiddleware());

$app->get('/admin/requests/{id}/history', function (Request $request, Response $response, array $args) use ($pdo) {
    $requestId = adminValidateInteger($args['id'] ?? null);

    if ($requestId === null) {
        return adminJsonResponse($response, ['message' => 'Invalid request id'], 400);
    }

    if (!adminFindRequestSummary($pdo, $requestId)) {
        return adminJsonResponse($response, ['message' => 'Maintenance request not found'], 404);
    }

    $stmt = $pdo->prepare(
        'SELECT
            su.id,
            NULL AS old_status,
            su.status AS new_status,
            su.update_notes AS remarks,
            su.updated_at AS created_at,
            users.id AS updated_by_id,
            users.username AS updated_by_name,
            users.role AS updated_by_role
         FROM status_updates su
         INNER JOIN users ON users.id = su.updated_by
         WHERE su.request_id = ?
         ORDER BY su.updated_at DESC, su.id DESC'
    );

    $stmt->execute([$requestId]);

    return adminJsonResponse($response, [
        'history' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
})->add(new RoleMiddleware(['Admin']))->add(new JwtMiddleware());

$app->put('/admin/requests/{id}/assign', function (Request $request, Response $response, array $args) use ($pdo) {
    $requestId = adminValidateInteger($args['id'] ?? null);

    if ($requestId === null) {
        return adminJsonResponse($response, ['message' => 'Invalid request id'], 400);
    }

    if (!adminFindRequestSummary($pdo, $requestId)) {
        return adminJsonResponse($response, ['message' => 'Maintenance request not found'], 404);
    }

    $data = $request->getParsedBody();
    $technicianId = adminValidateInteger($data['technician_id'] ?? null);

    if ($technicianId === null) {
        return adminJsonResponse($response, ['message' => 'A valid technician id is required'], 400);
    }

    $technicianStmt = $pdo->prepare(
        "SELECT id, username, email
         FROM users
         WHERE id = ? AND role = 'Technician'"
    );
    $technicianStmt->execute([$technicianId]);
    $technician = $technicianStmt->fetch(PDO::FETCH_ASSOC);

    if (!$technician) {
        return adminJsonResponse($response, ['message' => 'Technician not found'], 404);
    }

    $adminUser = $request->getAttribute('user');
    $pdo->beginTransaction();
    try {
        $updateStmt = $pdo->prepare(
            "UPDATE maintenance_requests
             SET assigned_technician_id = ?, status = 'Assigned', updated_at = CURRENT_TIMESTAMP
             WHERE id = ?"
        );
        $updateStmt->execute([$technicianId, $requestId]);
        $historyStmt = $pdo->prepare(
            "INSERT INTO status_updates (request_id, status, updated_by, update_notes)
             VALUES (?, 'Assigned', ?, ?)"
        );
        $historyStmt->execute([$requestId, $adminUser->id, 'Assigned to ' . $technician['username'] . '.']);
        $pdo->commit();
    } catch (\Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return adminJsonResponse($response, ['message' => 'Unable to assign technician'], 500);
    }

    return adminJsonResponse($response, [
        'message' => 'Technician assigned successfully',
        'technician' => $technician
    ]);
})->add(new RoleMiddleware(['Admin']))->add(new JwtMiddleware());
// Work-status transitions are restricted to assigned technicians in requests.php.
