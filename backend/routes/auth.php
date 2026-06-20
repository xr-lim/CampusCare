<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;

require_once '../config/database.php';

$app->post('/register', function(Request $request, Response $response) use ($pdo) {

    $data = $request->getParsedBody();

    $username = $data['username'] ?? null;
    $email = $data['email'] ?? null;
    $password = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
    $role = $data['role'] ?? null;

    if (!$username || !$email || !$password || !$role) {
        $response->getBody()->write(json_encode([
            "message" => "All fields including role are required"
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    $emailExists = $checkStmt->fetchColumn();

    if ($emailExists > 0) {
        $response->getBody()->write(json_encode([
            "message" => "Email address is already registered"
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
    }

    $stmt = $pdo->prepare("INSERT INTO users(username, email, password, role) VALUES(?,?,?,?)");
    $stmt->execute([$username, $email, $password, $role]);

    $response->getBody()->write(json_encode([
        "message" => "Registration successful"
    ]));

    return $response->withHeader('Content-Type', 'application/json');
});


$app->post('/login', function(Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($data['password'], $user['password'])) {
        $response->getBody()->write(json_encode([
            "message" => "Invalid credentials"
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }

    $secretKey = 'your_super_secret_key_that_is_at_least_32_characters_long_12345';
    $configFile = __DIR__ . '/../config/jwt.php';

    if (file_exists($configFile)) {
        $secretConfig = require $configFile;
        if (is_array($secretConfig) && isset($secretConfig['secret'])) {
            $secretKey = $secretConfig['secret'];
        } elseif (is_string($secretConfig)) {
            $secretKey = $secretConfig;
        }
    }

    $payload = [
        "id" => $user['id'],
        "email" => $user['email'],
        "role" => $user['role'] ?? 'student',
        "exp" => time() + 3600
    ];

    try {
        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        $response->getBody()->write(json_encode([
            "token" => $jwt,
            "user" => [
                "id" => $user['id'],
                "username" => $user['username'],
                "role" => $user['role'] ?? 'student'
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');

    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            "message" => "JWT Generation Error: " . $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});