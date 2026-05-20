<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Fixtures;

use CommonPHP\API\ApiRequest;
use CommonPHP\API\Contracts\ActionInterface;
use CommonPHP\Router\RouteMatch;

final class PlainAction implements ActionInterface
{
    public function handle(ApiRequest $request, RouteMatch $match): mixed
    {
        return [
            'kind' => 'action',
            'id' => $request->routeParameter('id'),
            'route' => $match->label(),
        ];
    }
}
