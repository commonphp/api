<?php

declare(strict_types=1);

namespace CommonPHP\API;

use CommonPHP\API\Contracts\ActionInterface;
use CommonPHP\API\Exceptions\ApiException;
use CommonPHP\API\Exceptions\InvalidActionException;
use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Exceptions\HttpException;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Contracts\RouteHandlerInterface;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteGroup;
use CommonPHP\Router\RouteMatch;
use CommonPHP\Router\Router;
use CommonPHP\Validation\Exceptions\ValidationException;
use Throwable;

final class ApiSurface implements HttpSurfaceInterface
{
    private Router $router;

    private ApiResponseFactory $responses;

    private ApiExceptionHandler $exceptions;

    private string $pathPrefix;

    public function __construct(
        ?Router $router = null,
        string $pathPrefix = '/api',
        ?ApiResponseFactory $responses = null,
        ?ApiExceptionHandler $exceptions = null,
    ) {
        $this->router = $router ?? new Router();
        $this->pathPrefix = $this->normalizePathPrefix($pathPrefix);
        $this->responses = $responses ?? new ApiResponseFactory();
        $this->exceptions = $exceptions ?? new ApiExceptionHandler($this->responses);
    }

    public function supports(Request $request): bool
    {
        return $this->pathPrefix === '/'
            || $request->path() === $this->pathPrefix
            || str_starts_with($request->path(), $this->pathPrefix . '/');
    }

    public function handle(Request $request): Response
    {
        $request = ApiRequest::fromRequest($request);

        try {
            if (!$this->supports($request)) {
                return $this->responses->problem(ApiProblem::forStatus(
                    ResponseStatus::NOT_FOUND,
                    'API endpoint not found.',
                    instance: $request->path(),
                    code: 'api_route_not_found',
                ));
            }

            $match = $this->router->match($request);

            return $this->dispatch($match, $request->withRouteMatch($match));
        } catch (Throwable $exception) {
            return $this->exceptions->handle($exception, $request);
        }
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function responses(): ApiResponseFactory
    {
        return $this->responses;
    }

    public function pathPrefix(): string
    {
        return $this->pathPrefix;
    }

    public function add(Route $route): static
    {
        $this->router->add($route);

        return $this;
    }

    public function route(
        RouteMethod|RequestMethod|string|array $methods,
        string $path,
        mixed $handler,
        ?string $name = null,
    ): Route {
        return $this->router->route($methods, $this->routePath($path), $handler, $name);
    }

    public function any(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::cases(), $path, $handler, $name);
    }

    public function get(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::GET, $path, $handler, $name);
    }

    public function post(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::POST, $path, $handler, $name);
    }

    public function put(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::PUT, $path, $handler, $name);
    }

    public function patch(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::PATCH, $path, $handler, $name);
    }

    public function delete(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::DELETE, $path, $handler, $name);
    }

    public function options(string $path, mixed $handler, ?string $name = null): Route
    {
        return $this->route(RouteMethod::OPTIONS, $path, $handler, $name);
    }

    /**
     * @param callable(RouteGroup): void|null $routes
     * @param array<string, mixed> $constraints
     * @param array<string, mixed> $defaults
     * @param array<string, mixed> $metadata
     * @param list<string> $schemes
     * @param list<mixed> $middleware
     */
    public function group(
        string $prefix = '',
        ?callable $routes = null,
        ?string $namePrefix = null,
        array $constraints = [],
        array $defaults = [],
        array $metadata = [],
        array $schemes = [],
        array $middleware = [],
    ): RouteGroup {
        return $this->router->group(
            $this->routePath($prefix),
            $routes,
            $namePrefix,
            $constraints,
            $defaults,
            $metadata,
            $schemes,
            $middleware,
        );
    }

    private function dispatch(RouteMatch $match, ApiRequest $request): Response
    {
        $handler = $this->resolveHandler($match);

        try {
            $result = $this->callHandler($handler, $request, $match);
        } catch (ApiException | HttpException | ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw InvalidActionException::failed($match, $exception);
        }

        return $this->responses->from($result);
    }

    private function resolveHandler(RouteMatch $match): mixed
    {
        $handler = $match->handler();

        if (is_array($handler) && count($handler) === 2) {
            if (is_callable($handler)) {
                return $handler;
            }

            [$target, $method] = array_values($handler);

            if (is_string($target) && is_string($method)) {
                return [$this->resolveClass($target), $method];
            }

            return $handler;
        }

        if ($handler instanceof ActionInterface || $handler instanceof RouteHandlerInterface || is_callable($handler)) {
            return $handler;
        }

        if (is_string($handler)) {
            if (str_contains($handler, '@')) {
                [$class, $method] = explode('@', $handler, 2);

                return [$this->resolveClass($class), $method];
            }

            if (str_contains($handler, '::')) {
                [$class, $method] = explode('::', $handler, 2);

                return [$this->resolveClass($class), $method];
            }

            if (class_exists($handler)) {
                return $this->resolveClass($handler);
            }
        }

        throw InvalidActionException::forHandler($match, get_debug_type($handler));
    }

    private function callHandler(mixed $handler, ApiRequest $request, RouteMatch $match): mixed
    {
        if ($handler instanceof ActionInterface) {
            return $handler->handle($request, $match);
        }

        if ($handler instanceof RouteHandlerInterface) {
            return $handler->handle($request, $match);
        }

        if (is_callable($handler)) {
            return $handler($request, $match);
        }

        throw InvalidActionException::forHandler($match, get_debug_type($handler));
    }

    private function resolveClass(string $class): object
    {
        if (!class_exists($class)) {
            throw new InvalidActionException(
                'API action class "' . $class . '" was not found.',
                ResponseStatus::INTERNAL_SERVER_ERROR,
                'api.action_not_found',
                ['class' => $class],
            );
        }

        return new $class();
    }

    private function routePath(string $path): string
    {
        $path = $this->normalizeRoutePath($path);

        if ($this->pathPrefix === '/') {
            return $path;
        }

        if ($path === $this->pathPrefix || str_starts_with($path, $this->pathPrefix . '/')) {
            return $path;
        }

        if ($path === '/') {
            return $this->pathPrefix;
        }

        return $this->pathPrefix . $path;
    }

    private function normalizePathPrefix(string $pathPrefix): string
    {
        $pathPrefix = $this->normalizeRoutePath($pathPrefix);

        return $pathPrefix === '/' ? '/' : rtrim($pathPrefix, '/');
    }

    private function normalizeRoutePath(string $path): string
    {
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return '/';
        }

        return str_starts_with($path, '/') ? $path : '/' . $path;
    }
}
