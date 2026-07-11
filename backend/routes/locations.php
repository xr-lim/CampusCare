<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once '../config/database.php';
require_once '../middleware/JwtMiddleware.php';
require_once '../middleware/RoleMiddleware.php';

// Get all locations (Public - no authentication required)
$app->get('/locations', function(Request $request, Response $response) use ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, type, description, created_at, updated_at FROM locations ORDER BY type ASC, name ASC");
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($locations));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "Failed to fetch locations",
            "error" => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// Get single location by ID (Public)
$app->get('/locations/{id}', function(Request $request, Response $response, array $args) use ($pdo) {
    try {
        $id = $args['id'];

        // Validate ID
        if (!is_numeric($id) || $id <= 0) {
            $response->getBody()->write(json_encode([
                "message" => "Invalid location ID"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $pdo->prepare("SELECT id, name, type, description, created_at, updated_at FROM locations WHERE id = ?");
        $stmt->execute([$id]);
        $location = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$location) {
            $response->getBody()->write(json_encode([
                "message" => "Location not found"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($location));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "Failed to fetch location",
            "error" => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// Create new location (Admin only)
$app->post('/locations', function(Request $request, Response $response) use ($pdo) {
    try {
       
        $data = $request->getParsedBody();
        $name = trim($data['name'] ?? '');
        $type = trim($data['type'] ?? '');
        $description = trim($data['description'] ?? '');

        // Validation
        if (empty($name)) {
            $response->getBody()->write(json_encode([
                "message" => "Location name is required"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (empty($type)) {
            $response->getBody()->write(json_encode([
                "message" => "Location type is required"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (strlen($name) > 100) {
            $response->getBody()->write(json_encode([
                "message" => "Location name must not exceed 100 characters"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (strlen($type) > 50) {
            $response->getBody()->write(json_encode([
                "message" => "Location type must not exceed 50 characters"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if location already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM locations WHERE name = ?");
        $checkStmt->execute([$name]);
        if ($checkStmt->fetchColumn() > 0) {
            $response->getBody()->write(json_encode([
                "message" => "Location already exists"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $stmt = $pdo->prepare("INSERT INTO locations (name, type, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $type, $description ?: null]);

        $locationId = $pdo->lastInsertId();
        $newLocation = [
            "id" => (int)$locationId,
            "name" => $name,
            "type" => $type,
            "description" => $description ?: null,
            "created_at" => date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode([
            "message" => "Location created successfully",
            "data" => $newLocation
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "Failed to create location",
            "error" => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})
->add(new RoleMiddleware(['Admin']))
->add(new JwtMiddleware());

// Update location (Admin only)
$app->put('/locations/{id}', function(Request $request, Response $response, array $args) use ($pdo) {
    try {
        $id = $args['id'];
       
        // Validate ID
        if (!is_numeric($id) || $id <= 0) {
            $response->getBody()->write(json_encode([
                "message" => "Invalid location ID"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if location exists
        $checkStmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
        $checkStmt->execute([$id]);
        $location = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$location) {
            $response->getBody()->write(json_encode([
                "message" => "Location not found"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $data = $request->getParsedBody();
        $name = isset($data['name']) ? trim($data['name']) : $location['name'];
        $type = isset($data['type']) ? trim($data['type']) : $location['type'];
        $description = isset($data['description']) ? trim($data['description']) : $location['description'];

        // Validation
        if (empty($name)) {
            $response->getBody()->write(json_encode([
                "message" => "Location name is required"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (empty($type)) {
            $response->getBody()->write(json_encode([
                "message" => "Location type is required"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (strlen($name) > 100) {
            $response->getBody()->write(json_encode([
                "message" => "Location name must not exceed 100 characters"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (strlen($type) > 50) {
            $response->getBody()->write(json_encode([
                "message" => "Location type must not exceed 50 characters"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if new name already exists (excluding current location)
        $duplicateStmt = $pdo->prepare("SELECT COUNT(*) FROM locations WHERE name = ? AND id != ?");
        $duplicateStmt->execute([$name, $id]);
        if ($duplicateStmt->fetchColumn() > 0) {
            $response->getBody()->write(json_encode([
                "message" => "Location name already exists"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $stmt = $pdo->prepare("UPDATE locations SET name = ?, type = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $type, $description ?: null, $id]);

        $updatedLocation = [
            "id" => (int)$id,
            "name" => $name,
            "type" => $type,
            "description" => $description ?: null,
            "created_at" => $location['created_at'],
            "updated_at" => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode([
            "message" => "Location updated successfully",
            "data" => $updatedLocation
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "Failed to update location",
            "error" => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})
->add(new RoleMiddleware(['Admin']))
->add(new JwtMiddleware());

// Delete location (Admin only)
$app->delete('/locations/{id}', function(Request $request, Response $response, array $args) use ($pdo) {
    try {
        $id = $args['id'];
        // Validate ID
        if (!is_numeric($id) || $id <= 0) {
            $response->getBody()->write(json_encode([
                "message" => "Invalid location ID"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if location exists
        $checkStmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
        $checkStmt->execute([$id]);
        $location = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$location) {
            $response->getBody()->write(json_encode([
                "message" => "Location not found"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Check if location is being used in any maintenance requests
        $usageStmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE location_id = ?");
        $usageStmt->execute([$id]);
        if ($usageStmt->fetchColumn() > 0) {
            $response->getBody()->write(json_encode([
                "message" => "Cannot delete location that is used in maintenance requests"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ?");
        $stmt->execute([$id]);

        $response->getBody()->write(json_encode([
            "message" => "Location deleted successfully"
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "Failed to delete location",
            "error" => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})
->add(new RoleMiddleware(['Admin']))
->add(new JwtMiddleware());
