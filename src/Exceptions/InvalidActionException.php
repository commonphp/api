<?php

declare(strict_types=1);

namespace CommonPHP\API\Exceptions;

use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\Router\RouteMatch;
use Throwable;

class InvalidActionException extends ApiException
{
    public static function forHandler(RouteMatch $match, string $type): self
    {
        return new self(
            'API action for ' . $match->label() . ' is not dispatchable: ' . $type . '.',
            ResponseStatus::INTERNAL_SERVER_ERROR,
            'api.invalid_action',
            ['route' => $match->label(), 'handler_type' => $type],
        );
    }

    public static function failed(RouteMatch $match, Throwable $previous): self
    {
        return new self(
            'API action for ' . $match->label() . ' failed.',
            ResponseStatus::INTERNAL_SERVER_ERROR,
            'api.action_failed',
            ['route' => $match->label()],
            previous: $previous,
        );
    }
}
