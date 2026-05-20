# Usage

Most applications use `ApiSurface` plus either callable routes or action classes.

## Surface Routes

```php
use CommonPHP\API\ApiSurface;

$api = new ApiSurface();

$api->get('/users/{id}', static function ($request) {
    return [
        'id' => $request->routeParameter('id'),
    ];
});
```

The route is reachable at `/api/users/{id}` because the default path prefix is `/api`.

## Custom Prefixes

```php
$api = new ApiSurface(pathPrefix: '/service');

$api->get('/status', static fn () => ['ready' => true]);
```

This route is reachable at `/service/status`.

Use `pathPrefix: '/'` when the API owns the entire HTTP surface.

## Registering Routes

`ApiSurface` mirrors the common route helper methods:

```php
$api->get('/items', $handler);
$api->post('/items', $handler);
$api->put('/items/{id}', $handler);
$api->patch('/items/{id}', $handler);
$api->delete('/items/{id}', $handler);
$api->options('/items', $handler);
$api->any('/health', $handler);
$api->route(['GET', 'POST'], '/search', $handler);
```

Use `group()` when routes share a prefix or name prefix:

```php
$api->group('/v1', static function ($routes): void {
    $routes->get('/status', static fn () => ['ready' => true], 'status');
}, 'v1.');
```

## Handler Shapes

API routes support these handler shapes:

- a callable;
- an `ActionInterface` object;
- a `RouteHandlerInterface` object;
- an invokable class-string;
- `ClassName@method`;
- `ClassName::method`;
- `[ClassName::class, 'method']`;
- `[$object, 'method']`.

Action classes should use `ActionInterface` when they are API-specific. Existing router handlers can still be reused through `RouteHandlerInterface`.

## Return Values

```php
use CommonPHP\API\ApiProblem;
use CommonPHP\HTTP\Enums\ResponseStatus;

$api->get('/conflict', static fn () => ApiProblem::forStatus(
    ResponseStatus::CONFLICT,
    'This resource already exists.',
));
```

The response factory converts common return values into HTTP responses. See [Responses](responses.md).
