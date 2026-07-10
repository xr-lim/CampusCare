<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once '../middleware/JwtMiddleware.php';
require_once '../config/database.php';

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

$app->get('/categories', function (Request $request, Response $response) use ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($categories));

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

$app->get('/locations', function (Request $request, Response $response) use ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, name FROM locations ORDER BY name");
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($locations));

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
$app->delete('/requests/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write(json_encode(["message" => "Cancel request route is registered for ID: " . $id]));
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware());
