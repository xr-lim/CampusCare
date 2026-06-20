<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;

class BaseController {
    protected function jsonResponse(Response $response, $data, int $status = 200): Response {
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}