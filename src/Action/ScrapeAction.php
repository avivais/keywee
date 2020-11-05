<?php

namespace App\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ScrapeAction
{
    public function __invoke(
        ServerRequestInterface $request, 
        ResponseInterface $response
    ): ResponseInterface 
	{
        $response->getBody()->write(json_encode(['success' => 'hi']));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
	{
		$response->getBody()->write(json_encode(['success' => 'got it']));
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function post(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
	{
		$response->getBody()->write(json_encode(['success' => 'post it', '_GET' => print_r($_GET,true), '_POST' => print_r($_POST,true)]));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
}
