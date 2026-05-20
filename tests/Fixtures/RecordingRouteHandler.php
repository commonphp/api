<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Fixtures;

use CommonPHP\API\ApiResponseFactory;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Contracts\RouteHandlerInterface;
use CommonPHP\Router\RouteMatch;

final class RecordingRouteHandler implements RouteHandlerInterface
{
    public function handle(Request $request, RouteMatch $match): Response
    {
        return (new ApiResponseFactory())->json([
            'kind' => 'route-handler',
            'request_class' => $request::class,
            'route' => $match->label(),
        ]);
    }
}
