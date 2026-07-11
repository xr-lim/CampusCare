<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once '../middleware/JwtMiddleware.php';
require_once '../config/database.php';

function jsonResponse(Response $response, array $data, int $status = 200)
{
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}

function isTechnician($jwt)
{
    return isset($jwt->role) && strtolower($jwt->role) === 'technician';
}

function validateRequestData($data)
{
    $errors = [];

    $title = isset($data['title']) ? trim($data['title']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $priority = isset($data['priority']) ? trim($data['priority']) : '';
    $category_id = $data['category_id'] ?? null;
    $location_id = $data['location_id'] ?? null;

    if ($title === '') {
        $errors[] = "Title is required.";
    } elseif (strlen($title) > 150) {
        $errors[] = "Title must not exceed 150 characters.";
    }

    if ($description === '') {
        $errors[] = "Description is required.";
    } elseif (strlen($description) > 1000) {
        $errors[] = "Description must not exceed 1000 characters.";
    }

    if (empty($category_id)) {
        $errors[] = "Category is required.";
    } elseif (!is_numeric($category_id)) {
        $errors[] = "Category must be a valid number.";
    }

    if (empty($location_id)) {
        $errors[] = "Location is required.";
    } elseif (!is_numeric($location_id)) {
        $errors[] = "Location must be a valid number.";
    }

    $allowedPriorities = ['Low', 'Medium', 'High'];

    if ($priority === '') {
        $errors[] = "Priority is required.";
    } elseif (!in_array($priority, $allowedPriorities)) {
        $errors[] = "Priority must be Low, Medium, or High.";
    }

    return $errors;
}

// 1. Submit Request (Create)
$app->post('/requests', function (Request $request, Response $response) use ($pdo) {
    $jwt = $request->getAttribute('user'); // Extract decrypted user info from JWT
    $data = $request->getParsedBody();

    $title = isset($data['title']) ? trim($data['title']) : '';
    $description = isset($data['description']) ? trim($data['description']) : '';
    $priority = isset($data['priority']) ? trim($data['priority']) : 'Medium';
    $category_id = $data['category_id'] ?? null;
    $location_id = $data['location_id'] ?? null;

    // Backend Input Validation
    $errors = validateRequestData($data);

if (!empty($errors)) {
    $response->getBody()->write(json_encode([
        "message" => "Validation failed",
        "errors" => $errors
    ]));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(400);
}

    // Verify if selected category and location exist in the database
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    if ($stmt->fetchColumn() == 0) {
        $response->getBody()->write(json_encode(["message" => "Invalid category selection."]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM locations WHERE id = ?");
    $stmt->execute([$location_id]);
    if ($stmt->fetchColumn() == 0) {
        $response->getBody()->write(json_encode(["message" => "Invalid location selection."]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        // Insert request
        $stmt = $pdo->prepare("
            INSERT INTO maintenance_requests (title, description, priority, category_id, location_id, user_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt->execute([$title, $description, $priority, $category_id, $location_id, $jwt->id]);
        
        $newRequestId = $pdo->lastInsertId();
        
        // Log the initial status update
        $logStmt = $pdo->prepare("
            INSERT INTO status_updates (request_id, status, updated_by, update_notes) 
            VALUES (?, 'Pending', ?, 'Request submitted successfully.')
        ");
        $logStmt->execute([$newRequestId, $jwt->id]);

        $response->getBody()->write(json_encode([
            "message" => "Maintenance request submitted successfully",
            "request_id" => $newRequestId
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(["message" => "Database error: " . $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add(new JwtMiddleware());

// View requests assigned to the authenticated technician.
$app->get('/technician/requests', function (Request $request, Response $response) use ($pdo) {
    $jwt = $request->getAttribute('user');

    if (!isTechnician($jwt)) {
        return jsonResponse($response, ["message" => "Technician access required"], 403);
    }

    try {
        $stmt = $pdo->prepare("
            SELECT mr.id, mr.title, mr.description, mr.priority, mr.status,
                   mr.created_at, mr.updated_at, c.name AS category_name,
                   l.name AS location_name, submitter.username AS requester_name,
                   latest.update_notes AS latest_notes
            FROM maintenance_requests mr
            INNER JOIN categories c ON c.id = mr.category_id
            INNER JOIN locations l ON l.id = mr.location_id
            INNER JOIN users submitter ON submitter.id = mr.user_id
            LEFT JOIN status_updates latest ON latest.id = (
                SELECT su.id FROM status_updates su
                WHERE su.request_id = mr.id
                ORDER BY su.updated_at DESC, su.id DESC LIMIT 1
            )
            WHERE mr.assigned_technician_id = ?
            AND mr.status NOT IN ('Cancelled', 'Rejected')
            ORDER BY FIELD(mr.status, 'In Progress', 'Assigned', 'Completed'),
                     FIELD(mr.priority, 'High', 'Medium', 'Low'), mr.created_at DESC
        ");
        $stmt->execute([$jwt->id]);
        return jsonResponse($response, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        return jsonResponse($response, ["message" => "Unable to load assigned requests"], 500);
    }
})->add(new JwtMiddleware());

// Update the status of a request assigned to the authenticated technician.
$app->put('/technician/requests/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
    $jwt = $request->getAttribute('user');
    $data = $request->getParsedBody() ?: [];
    $status = trim($data['status'] ?? '');
    $notes = trim($data['notes'] ?? '');

    if (!isTechnician($jwt)) {
        return jsonResponse($response, ["message" => "Technician access required"], 403);
    }
    if (!in_array($status, ['In Progress', 'Completed'], true)) {
        return jsonResponse($response, ["message" => "Status must be In Progress or Completed"], 400);
    }
    if ($notes === '') {
        return jsonResponse($response, ["message" => "Update notes are required"], 400);
    }
    if (strlen($notes) > 1000) {
        return jsonResponse($response, ["message" => "Update notes must not exceed 1000 characters"], 400);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT status FROM maintenance_requests WHERE id = ? AND assigned_technician_id = ? FOR UPDATE");
        $stmt->execute([$args['id'], $jwt->id]);
        $currentStatus = $stmt->fetchColumn();

        if ($currentStatus === false) {
            $pdo->rollBack();
            return jsonResponse($response, ["message" => "Assigned request not found"], 404);
        }

        $allowed = ($currentStatus === 'Assigned' && $status === 'In Progress') ||
                   ($currentStatus === 'In Progress' && $status === 'Completed');
        if (!$allowed) {
            $pdo->rollBack();
            return jsonResponse($response, ["message" => "Invalid status transition from {$currentStatus} to {$status}"], 400);
        }

        $stmt = $pdo->prepare("UPDATE maintenance_requests SET status = ? WHERE id = ? AND assigned_technician_id = ?");
        $stmt->execute([$status, $args['id'], $jwt->id]);
        $stmt = $pdo->prepare("INSERT INTO status_updates (request_id, status, updated_by, update_notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$args['id'], $status, $jwt->id, $notes]);
        $pdo->commit();

        return jsonResponse($response, ["message" => "Request status updated successfully"]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return jsonResponse($response, ["message" => "Unable to update request status"], 500);
    }
})->add(new JwtMiddleware());

// 2. View My Requests (Read List)
$app->get('/requests/my', function (Request $request, Response $response) use ($pdo) {
    $jwt = $request->getAttribute('user');

    try {
        $stmt = $pdo->prepare("
            SELECT 
                mr.id,
                mr.title,
                mr.description,
                mr.priority,
                mr.status,
                mr.category_id,
                mr.location_id,
                mr.created_at,
                c.name AS category_name,
                l.name AS location_name
            FROM maintenance_requests mr
            INNER JOIN categories c ON mr.category_id = c.id
            INNER JOIN locations l ON mr.location_id = l.id
            WHERE mr.user_id = ?
            ORDER BY mr.created_at DESC
        ");

        $stmt->execute([$jwt->id]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($requests));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "message" => "Database error: " . $e->getMessage()
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
})->add(new JwtMiddleware());

// 3. View Request Details (Read Single)
$app->get('/requests/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
    $jwt = $request->getAttribute('user');
    $id = $args['id'];

    try {
        $stmt = $pdo->prepare("
            SELECT 
                mr.id,
                mr.title,
                mr.description,
                mr.priority,
                mr.status,
                mr.category_id,
                mr.location_id,
                mr.created_at,
                mr.updated_at,
                c.name AS category_name,
                l.name AS location_name,
                technician.username AS technician_name
            FROM maintenance_requests mr
            INNER JOIN categories c ON mr.category_id = c.id
            INNER JOIN locations l ON mr.location_id = l.id
            LEFT JOIN users technician ON mr.assigned_technician_id = technician.id
            WHERE mr.id = ?
            AND mr.user_id = ?
        ");

        $stmt->execute([$id, $jwt->id]);
        $requestData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$requestData) {
            $response->getBody()->write(json_encode([
                "message" => "Request not found"
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $response->getBody()->write(json_encode($requestData));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "message" => "Database error: " . $e->getMessage()
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
})->add(new JwtMiddleware());

// 4. Edit Pending Request (Update)
$app->put('/requests/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
    $jwt = $request->getAttribute('user');
    $id = $args['id'];
    $data = $request->getParsedBody();

    $errors = validateRequestData($data);

    if (!empty($errors)) {
        $response->getBody()->write(json_encode([
            "message" => "Validation failed",
            "errors" => $errors
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    }

    try {
        $checkStmt = $pdo->prepare("
            SELECT id, status
            FROM maintenance_requests
            WHERE id = ?
            AND user_id = ?
        ");
        $checkStmt->execute([$id, $jwt->id]);
        $existingRequest = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingRequest) {
            $response->getBody()->write(json_encode([
                "message" => "Request not found"
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        if ($existingRequest['status'] !== 'Pending') {
            $response->getBody()->write(json_encode([
                "message" => "Only pending requests can be edited"
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $categoryStmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
        $categoryStmt->execute([$data['category_id']]);

        if ($categoryStmt->fetchColumn() == 0) {
            $response->getBody()->write(json_encode([
                "message" => "Invalid category selection."
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $locationStmt = $pdo->prepare("SELECT COUNT(*) FROM locations WHERE id = ?");
        $locationStmt->execute([$data['location_id']]);

        if ($locationStmt->fetchColumn() == 0) {
            $response->getBody()->write(json_encode([
                "message" => "Invalid location selection."
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $updateStmt = $pdo->prepare("
            UPDATE maintenance_requests
            SET title = ?,
                description = ?,
                priority = ?,
                category_id = ?,
                location_id = ?
            WHERE id = ?
            AND user_id = ?
        ");

        $updateStmt->execute([
            trim($data['title']),
            trim($data['description']),
            trim($data['priority']),
            $data['category_id'],
            $data['location_id'],
            $id,
            $jwt->id
        ]);

        $response->getBody()->write(json_encode([
            "message" => "Maintenance request updated successfully"
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "message" => "Database error: " . $e->getMessage()
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
})->add(new JwtMiddleware());

// 5. Cancel Pending Request (Delete/Cancel)
$app->delete('/requests/{id}', function (Request $request, Response $response, array $args) use ($pdo) {
    $jwt = $request->getAttribute('user');
    $id = $args['id'];

    try {
        $checkStmt = $pdo->prepare("
            SELECT id, status
            FROM maintenance_requests
            WHERE id = ?
            AND user_id = ?
        ");
        $checkStmt->execute([$id, $jwt->id]);
        $existingRequest = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingRequest) {
            $response->getBody()->write(json_encode([
                "message" => "Request not found"
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        if ($existingRequest['status'] !== 'Pending') {
            $response->getBody()->write(json_encode([
                "message" => "Only pending requests can be cancelled"
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $updateStmt = $pdo->prepare("
            UPDATE maintenance_requests
            SET status = 'Cancelled'
            WHERE id = ?
            AND user_id = ?
        ");
        $updateStmt->execute([$id, $jwt->id]);

        $logStmt = $pdo->prepare("
            INSERT INTO status_updates (request_id, status, updated_by, update_notes)
            VALUES (?, 'Cancelled', ?, 'Request cancelled by user.')
        ");
        $logStmt->execute([$id, $jwt->id]);

        $response->getBody()->write(json_encode([
            "message" => "Maintenance request cancelled successfully"
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            "message" => "Database error: " . $e->getMessage()
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
})->add(new JwtMiddleware());
