<?php

class RoleMiddleware {
    private $roles;

    public function __construct($roles)
    {
        $this->roles = is_array($roles) ? $roles : [$roles];
    }

    public function __invoke($request,$handler)
    {
        $user =
        $request->getAttribute('user');

        if(!$user)
        {
            $response =
            new Slim\Psr7\Response();

            $response->getBody()->write(
                json_encode([
                    "message"=>"Unauthorized"
                ])
            );

            return $response
            ->withStatus(401)
            ->withHeader(
                'Content-Type',
                'application/json'
            );
        }

        if(!in_array(
            $user->role,
            $this->roles
        ))
        {
            $response =
            new Slim\Psr7\Response();

            $response->getBody()->write(
                json_encode([
                    "message"=>"Access denied"
                ])
            );

            return $response
            ->withStatus(403)
            ->withHeader(
                'Content-Type',
                'application/json'
            );
        }

        return $handler->handle($request);
    }
}