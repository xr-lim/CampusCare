<?php

require_once '../middleware/JwtMiddleware.php';

$app->get('/home',
function(
    Request $request,
    Response $response
){
    $user =
        $request->getAttribute('user');

    $response->getBody()->write(
        json_encode($user)
    );

    return $response
        ->withHeader(
            'Content-Type',
            'application/json'
        );
})
->add(new JwtMiddleware());