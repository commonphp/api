<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Fixtures;

use CommonPHP\API\ApiRequest;
use CommonPHP\Router\RouteMatch;

final class InvokableAction
{
    public function __invoke(ApiRequest $request, RouteMatch $match): array
    {
        return [
            'kind' => 'invokable',
            'path' => $request->path(),
            'route' => $match->label(),
        ];
    }
}
