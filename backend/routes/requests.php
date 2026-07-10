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


// 2. View My Requests (Read List)
$app->get('/requests/my', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(["message" => "View my requests route is registered"]));
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware());

// 3. View Request Details (Read Single)
$app->get('/requests/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write(json_encode(["message" => "View request details route is registered for ID: " . $id]));
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware());

// 4. Edit Pending Request (Update)
$app->put('/requests/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write(json_encode(["message" => "Edit request route is registered for ID: " . $id]));
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware());

// 5. Cancel Pending Request (Delete/Cancel)
$app->delete('/requests/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write(json_encode(["message" => "Cancel request route is registered for ID: " . $id]));
    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware());
