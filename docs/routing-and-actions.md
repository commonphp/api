# Routing And Actions

API routes are powered by `comphp/router` and exposed through convenience methods on `ApiSurface`.

## Route Registration

```php
$api->get('/users/{id}', UserShowAction::class);
$api->post('/users', UserCreateAction::class);
```

Routes registered through the surface are prefixed with the configured API mount. With the default `/api` prefix, `/users/{id}` becomes `/api/users/{id}`.

`add(Route $route)` accepts an already-built route and does not change its path. Use it when the route has already been created with the correct API path.

## Action Interface

```php
use CommonPHP\API\ApiRequest;
use CommonPHP\API\Contracts\ActionInterface;
use CommonPHP\Router\RouteMatch;

final class UserShowAction implements ActionInterface
{
    public function handle(ApiRequest $request, RouteMatch $match): mixed
    {
        return [
            'id' => $request->routeParameter('id'),
        ];
    }
}
```

The handler receives the API request and the router match. It may return any value supported by `ApiResponseFactory::from()`.

## Abstract Actions

`AbstractAction` is optional. Use it when response helper methods make the action clearer:

```php
use CommonPHP\API\ApiRequest;
use CommonPHP\API\Contracts\AbstractAction;
use CommonPHP\Router\RouteMatch;

final class UserCreateAction extends AbstractAction
{
    public function handle(ApiRequest $request, RouteMatch $match): mixed
    {
        return $this->created([
            'name' => $request->input('name'),
        ], '/api/users/1');
    }
}
```

Available helpers include `json()`, `ok()`, `created()`, `noContent()`, `problem()`, and `validation()`.

## Reusing Router Handlers

`RouteHandlerInterface` handlers from the router package can be used directly. They receive the same request object, which will be an `ApiRequest` during API dispatch.

```php
$api->get('/legacy', new ExistingRouteHandler());
```
