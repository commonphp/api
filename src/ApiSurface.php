<?php

declare(strict_types=1);

namespace CommonPHP\API;

use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\HTTP\ResponseFactory;

final class ApiSurface implements HttpSurfaceInterface
{
    public function supports(Request $request): bool
    {
        return $request->path() === '/api' || str_starts_with($request->path(), '/api/');
    }

    public function handle(Request $request): Response
    {
        return (new ResponseFactory())->json(['error' => 'API endpoint not found.'], 404);
    }
}
