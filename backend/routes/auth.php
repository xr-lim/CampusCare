<?php

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once '../config/database.php';

function normalizeRole(?string $role): ?string
{
    if ($role === null) {
        return null;
    }

    $normalized = ucfirst(strtolower(trim($role)));
    $allowedRoles = ['Student', 'Staff', 'Technician'];

    return in_array($normalized, $allowedRoles, true)
        ? $normalized
        : null;
}

$app->post('/register', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();

    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $rawPassword = $data['password'] ?? '';
    $role = normalizeRole($data['role'] ?? null);

    if ($username === '' || $email === '' || $rawPassword === '' || $role === null) {
        $response->getBody()->write(json_encode([
            'message' => 'Username, email, password, and a valid non-admin role are required'
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $password = password_hash($rawPassword, PASSWORD_DEFAULT);

    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $checkStmt->execute([$email]);
    $emailExists = $checkStmt->fetchColumn();

    if ($emailExists > 0) {
        $response->getBody()->write(json_encode([
            'message' => 'Email address is already registered'
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
    }

    $stmt = $pdo->prepare('INSERT INTO users(username, email, password, role) VALUES(?,?,?,?)');
    $stmt->execute([$username, $email, $password, $role]);

    $response->getBody()->write(json_encode([
        'message' => 'Registration successful'
    ]));

    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->post('/login', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();

    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if ($email === '' || $password === '') {
        $response->getBody()->write(json_encode([
            'message' => 'Email and password are required'
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email=?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        $response->getBody()->write(json_encode([
            'message' => 'Invalid credentials'
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }

    $configFile = __DIR__ . '/../config/jwt.php';

    if (!file_exists($configFile)) {
        $response->getBody()->write(json_encode([
            'message' => 'JWT configuration missing'
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }

    $secretConfig = require $configFile;
    $secretKey = is_array($secretConfig) ? ($secretConfig['secret'] ?? '') : $secretConfig;

    if ($secretKey === '') {
        $response->getBody()->write(json_encode([
            'message' => 'JWT secret is invalid'
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }

    $payload = [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'exp' => time() + 3600
    ];

    try {
        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        $response->getBody()->write(json_encode([
            'token' => $jwt,
            'user' => [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'message' => 'JWT Generation Error: ' . $e->getMessage()
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});
