<?php

declare(strict_types=1);

namespace CommonPHP\API;

use CommonPHP\API\Exceptions\UnsupportedContentTypeException;
use CommonPHP\HTTP\Exceptions\InvalidRequestException;
use CommonPHP\HTTP\Request;
use CommonPHP\Router\RouteMatch;

final class ApiRequest extends Request
{
    public const ROUTE_MATCH_ATTRIBUTE = 'api.route_match';

    public const ROUTE_PARAMETERS_ATTRIBUTE = 'api.route_parameters';

    public static function fromRequest(Request $request): self
    {
        if ($request instanceof self) {
            return $request;
        }

        return new self(
            $request->method(),
            $request->uri(),
            $request->headers(),
            $request->body(),
            $request->queryParams(),
            $request->parsedBody(),
            $request->cookies(),
            $request->files(),
            $request->serverParams(),
            $request->scheme(),
            $request->attributes(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $parsedBody = $this->parsedBody();

        if (is_array($parsedBody)) {
            return $parsedBody;
        }

        if (is_object($parsedBody)) {
            return get_object_vars($parsedBody);
        }

        if ($parsedBody !== null) {
            throw InvalidRequestException::because('Parsed API body must be an array or object.');
        }

        if (trim($this->body()) === '') {
            return [];
        }

        if (!$this->isJson()) {
            throw UnsupportedContentTypeException::forRequest($this);
        }

        $payload = $this->json(true);

        if (!is_array($payload)) {
            throw InvalidRequestException::because('API JSON body must decode to an object or array.');
        }

        return $payload;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $payload = $this->payload();

        if ($key === null) {
            return $payload;
        }

        return $this->valueFrom($payload, $key, $default);
    }

    public function hasInput(string $key): bool
    {
        $missing = new class {
        };

        return $this->input($key, $missing) !== $missing;
    }

    public function routeMatch(): ?RouteMatch
    {
        $match = $this->attribute(self::ROUTE_MATCH_ATTRIBUTE);

        return $match instanceof RouteMatch ? $match : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function routeParameters(): array
    {
        $match = $this->routeMatch();

        if ($match !== null) {
            return $match->parameters()->all();
        }

        $parameters = $this->attribute(self::ROUTE_PARAMETERS_ATTRIBUTE, []);

        return is_array($parameters) ? $parameters : [];
    }

    public function routeParameter(string $name, mixed $default = null): mixed
    {
        $parameters = $this->routeParameters();

        return $parameters[$name] ?? $default;
    }

    public function withRouteMatch(RouteMatch $match): self
    {
        return $this
            ->withAttribute(self::ROUTE_MATCH_ATTRIBUTE, $match)
            ->withAttribute(self::ROUTE_PARAMETERS_ATTRIBUTE, $match->parameters()->all());
    }

    /**
     * @param array<string, mixed> $values
     */
    private function valueFrom(array $values, string $key, mixed $default): mixed
    {
        if (array_key_exists($key, $values)) {
            return $values[$key];
        }

        $current = $values;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
