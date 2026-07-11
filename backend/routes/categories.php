<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once '../config/database.php';
require_once '../middleware/JwtMiddleware.php';
require_once '../middleware/RoleMiddleware.php';

// Get all categories (Public - no authentication required)
$app->get('/categories', function(Request $request, Response $response) use ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, description, created_at, updated_at FROM categories ORDER BY name ASC");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($categories));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "Failed to fetch categories",
            "error" => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// Get single category by ID (Public)
$app->get('/categories/{id}', function(Request $request, Response $response) use ($pdo) {
    try {
        $id = $request->getAttribute('id');

        // Validate ID
        if (!is_numeric($id) || $id <= 0) {
            $response->getBody()->write(json_encode([
                "message" => "Invalid category ID"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $pdo->prepare("SELECT id, name, description, created_at, updated_at FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $response->getBody()->write(json_encode([
                "message" => "Category not found"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($category));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "Failed to fetch category",
            "error" => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// Create new category (Admin only)
$app->post('/categories', function(Request $request, Response $response) use ($pdo) {
    try {
        
        $data = $request->getParsedBody();
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');

        // Validation
        if (empty($name)) {
            $response->getBody()->write(json_encode([
                "message" => "Category name is required"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (strlen($name) > 100) {
            $response->getBody()->write(json_encode([
                "message" => "Category name must not exceed 100 characters"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if category already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $checkStmt->execute([$name]);
        if ($checkStmt->fetchColumn() > 0) {
            $response->getBody()->write(json_encode([
                "message" => "Category already exists"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description ?: null]);

        $categoryId = $pdo->lastInsertId();
        $newCategory = [
            "id" => (int)$categoryId,
            "name" => $name,
            "description" => $description ?: null,
            "created_at" => date('Y-m-d H:i:s'),
            "updated_at" => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode([
            "message" => "Category created successfully",
            "data" => $newCategory
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "Failed to create category",
            "error" => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})
->add(new RoleMiddleware(['Admin']))
->add(new JwtMiddleware());

// Update category (Admin only)
$app->put('/categories/{id}', function(Request $request, Response $response) use ($pdo) {
    try {

        // Check if category exists
        $checkStmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $checkStmt->execute([$id]);
        $category = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $response->getBody()->write(json_encode([
                "message" => "Category not found"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $data = $request->getParsedBody();
        $name = isset($data['name']) ? trim($data['name']) : $category['name'];
        $description = isset($data['description']) ? trim($data['description']) : $category['description'];

        // Validation
        if (empty($name)) {
            $response->getBody()->write(json_encode([
                "message" => "Category name is required"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (strlen($name) > 100) {
            $response->getBody()->write(json_encode([
                "message" => "Category name must not exceed 100 characters"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if new name already exists (excluding current category)
        $duplicateStmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
        $duplicateStmt->execute([$name, $id]);
        if ($duplicateStmt->fetchColumn() > 0) {
            $response->getBody()->write(json_encode([
                "message" => "Category name already exists"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description ?: null, $id]);

        $updatedCategory = [
            "id" => (int)$id,
            "name" => $name,
            "description" => $description ?: null,
            "created_at" => $category['created_at'],
            "updated_at" => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode([
            "message" => "Category updated successfully",
            "data" => $updatedCategory
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "Failed to update category",
            "error" => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})
->add(new RoleMiddleware(['Admin']))
->add(new JwtMiddleware());

// Delete category (Admin only)
$app->delete('/categories/{id}', function(Request $request, Response $response) use ($pdo) {
    try {
       
        // Validate ID
        if (!is_numeric($id) || $id <= 0) {
            $response->getBody()->write(json_encode([
                "message" => "Invalid category ID"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if category exists
        $checkStmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $checkStmt->execute([$id]);
        $category = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $response->getBody()->write(json_encode([
                "message" => "Category not found"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Check if category is being used in any maintenance requests
        $usageStmt = $pdo->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE category_id = ?");
        $usageStmt->execute([$id]);
        if ($usageStmt->fetchColumn() > 0) {
            $response->getBody()->write(json_encode([
                "message" => "Cannot delete category that is used in maintenance requests"
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);

        $response->getBody()->write(json_encode([
            "message" => "Category deleted successfully"
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "Failed to delete category",
            "error" => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})
->add(new RoleMiddleware(['Admin']))
->add(new JwtMiddleware());
