<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtMiddleware
{
    public function __invoke(
        $request,
        $handler
    ) {

        $header = $request
            ->getHeaderLine('Authorization');

        if(!$header)
        {
            throw new Exception(
                "Token required"
            );
        }

        $token = str_replace(
            "Bearer ",
            "",
            $header
        );

        $secret = require '../config/jwt.php';

        $decoded = JWT::decode(
            $token,
            new Key(
                $secret['secret'],
                'HS256'
            )
        );

        $request =
            $request->withAttribute(
                'user',
                $decoded
            );

        return $handler->handle($request);
    }
}