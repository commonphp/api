<?php

declare(strict_types=1);

namespace CommonPHP\API\Contracts;

use CommonPHP\API\ApiRequest;
use CommonPHP\Router\RouteMatch;

interface ActionInterface
{
    public function handle(ApiRequest $request, RouteMatch $match): mixed;
}
