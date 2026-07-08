<?php
require_once '../middleware/JwtMiddleware.php';
require_once '../config/database.php';

$app->get('/home',
function(
    Request $request,
    Response $response
){
    $user = 
        $request->getAttribute('user');

    $response->getBody()->write(
        json_encode([
            "message"=>"Access granted",
            "user"=>$user
        ])
    );

    return $response
        ->withHeader(
            'Content-Type',
            'application/json'
        );
})->add(new JwtMiddleware());

$app->get('/profile', 
function (
    $request,
    $response
) use ($pdo) {
    $jwt =
        $request->getAttribute('user');

    $stmt = $pdo->prepare(
        "SELECT id, username, email, role
         FROM users
         WHERE id=?"
    );

    $stmt->execute([
        $jwt->id
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$user){
        $response->getBody()->write(
            json_encode([
                "message"=>"User not found"
            ])
        );

        return $response
            ->withStatus(404)
            ->withHeader(
                'Content-Type',
                'application/json'
            );
    }

    $response->getBody()->write(
        json_encode($user)
    );

    return $response
        ->withHeader(
            'Content-Type',
            'application/json'
        );
})->add(new JwtMiddleware());

$app->put('/profile',
function(
    $request,
    $response
) use ($pdo){
    $jwt = $request->getAttribute('user');
    $data = $request->getParsedBody();

    if(empty($data['username'])){
        $response->getBody()->write(
            json_encode([
                "message"=>"Username required"
            ])
        );

        return $response
            ->withStatus(400)
            ->withHeader(
                'Content-Type',
                'application/json'
            );
    }

    $stmt = $pdo->prepare(
        "UPDATE users
            SET username=?
            WHERE id=?"
    );

    $stmt->execute([
        $data['username'],
        $jwt->id
    ]);

    $response->getBody()->write(
        json_encode([
            "message"=>"Profile updated successfully"
        ])
    );

    return $response
        ->withHeader(
            'Content-Type',
            'application/json'
        );
})->add(new JwtMiddleware());

$app->delete('/profile',
function(
    $request,
    $response
) use ($pdo){

    $jwt =
        $request->getAttribute('user');

    $stmt = $pdo->prepare(
        "DELETE FROM users
         WHERE id=?"
    );

    $stmt->execute([
        $jwt->id
    ]);

    $response->getBody()->write(
        json_encode([
            "message"=>"Account deleted successfully"
        ])
    );

    return $response
        ->withHeader(
            'Content-Type',
            'application/json'
        );

})->add(new JwtMiddleware());