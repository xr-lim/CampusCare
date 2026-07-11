<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once '../middleware/JwtMiddleware.php';
require_once '../config/database.php';

$app->get('/home', function (Request $request, Response $response) {
    $user = $request->getAttribute('user');

    $response->getBody()->write(json_encode([
        'message' => 'Access granted',
        'user' => $user
    ]));

    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware());

$app->get('/profile', function (Request $request, Response $response) use ($pdo) {
    $jwt = $request->getAttribute('user');

    $stmt = $pdo->prepare(
        'SELECT id, username, email, role
         FROM users
         WHERE id=?'
    );

    $stmt->execute([$jwt->id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $response->getBody()->write(json_encode([
            'message' => 'User not found'
        ]));

        return $response
            ->withStatus(404)
            ->withHeader('Content-Type', 'application/json');
    }

    $response->getBody()->write(json_encode($user));

    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware());

$app->put('/profile', function (Request $request, Response $response) use ($pdo) {
    $jwt = $request->getAttribute('user');
    $data = $request->getParsedBody();
    $username = trim($data['username'] ?? '');

    if ($username === '') {
        $response->getBody()->write(json_encode([
            'message' => 'Username required'
        ]));

        return $response
            ->withStatus(400)
            ->withHeader('Content-Type', 'application/json');
    }

    $stmt = $pdo->prepare(
        'UPDATE users
         SET username=?
         WHERE id=?'
    );

    $stmt->execute([$username, $jwt->id]);

    $response->getBody()->write(json_encode([
        'message' => 'Profile updated successfully'
    ]));

    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware());

$app->delete('/profile', function (Request $request, Response $response) use ($pdo) {
    $jwt = $request->getAttribute('user');

    $stmt = $pdo->prepare(
        'DELETE FROM users
         WHERE id=?'
    );

    $stmt->execute([$jwt->id]);

    $response->getBody()->write(json_encode([
        'message' => 'Account deleted successfully'
    ]));

    return $response->withHeader('Content-Type', 'application/json');
})->add(new JwtMiddleware());
