<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtMiddleware
{
    public function __invoke(
        $request,
        $handler
    ) {

        $response = new Slim\Psr7\Response();

        $header = $request
            ->getHeaderLine('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {

            $response->getBody()->write(
                json_encode([
                    "message" => "Authorization token required"
                ])
            );

            return $response
                ->withStatus(401)
                ->withHeader(
                    'Content-Type',
                    'application/json'
                );
        }

        $token = str_replace(
            'Bearer ',
            '',
            $header
        );

        try {
            $secret = require '../config/jwt.php';

            $decoded = JWT::decode(
                $token,
                new Key(
                    $secret['secret'],
                    'HS256'
                )
            );

            $request = $request->withAttribute(
                'user',
                $decoded
            );

            return $handler->handle($request);
        } catch (ExpiredException $e) {
            $response->getBody()->write(
                json_encode([
                    "message" => "Token expired"
                ])
            );

            return $response
                ->withStatus(401)
                ->withHeader(
                    'Content-Type',
                    'application/json'
                );


        } catch (Exception $e) {
            $response->getBody()->write(
                json_encode([
                    "message" => "Invalid token"
                ])
            );

            return $response
                ->withStatus(401)
                ->withHeader(
                    'Content-Type',
                    'application/json'
                );
        }
    }
}