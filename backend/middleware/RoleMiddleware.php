<?php

class RoleMiddleware {
    private $roles;

    public function __construct($roles)
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $this->roles = array_map('strtolower', $roles);
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
            strtolower($user->role ?? ''),
            $this->roles,
            true
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
