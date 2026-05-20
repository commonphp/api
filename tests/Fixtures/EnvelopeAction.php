<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Fixtures;

use CommonPHP\API\ApiRequest;
use CommonPHP\API\Contracts\AbstractAction;
use CommonPHP\Router\RouteMatch;

final class EnvelopeAction extends AbstractAction
{
    public function handle(ApiRequest $request, RouteMatch $match): mixed
    {
        return $this->ok(
            ['id' => $request->routeParameter('id')],
            ['route' => $match->label()],
        );
    }
}
