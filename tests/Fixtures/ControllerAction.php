<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Fixtures;

use CommonPHP\API\ApiRequest;
use CommonPHP\Router\RouteMatch;

final class ControllerAction
{
    public function show(ApiRequest $request, RouteMatch $match): array
    {
        return [
            'kind' => 'controller',
            'path' => $request->path(),
            'route' => $match->label(),
        ];
    }
}
