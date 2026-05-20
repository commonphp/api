# Requests

`ApiRequest` extends `CommonPHP\HTTP\Request` with helpers that are useful inside API actions.

## Adapting HTTP Requests

`ApiSurface` automatically adapts requests before dispatch. You can also adapt manually:

```php
use CommonPHP\API\ApiRequest;
use CommonPHP\HTTP\Request;

$apiRequest = ApiRequest::fromRequest($request);
```

If the request is already an `ApiRequest`, the same instance is returned.

## Payloads

`payload()` returns an array. It checks sources in this order:

1. parsed body array;
2. parsed body object converted with `get_object_vars()`;
3. empty array for an empty body;
4. decoded JSON body when `Content-Type` is JSON-compatible.

```php
$payload = $request->payload();
```

Non-empty non-JSON bodies throw `UnsupportedContentTypeException`. Invalid JSON or scalar JSON values throw `InvalidRequestException`.

## Input Lookup

Use `input()` to get the full payload or a single value:

```php
$payload = $request->input();
$email = $request->input('email');
$timezone = $request->input('settings.timezone', 'UTC');
```

`hasInput()` distinguishes missing keys from keys present with `null` values:

```php
if ($request->hasInput('deleted_at')) {
    // The key was present, even if its value was null.
}
```

## Route Data

When `ApiSurface` dispatches a matched route, it attaches the `RouteMatch` and parameters to the request.

```php
$match = $request->routeMatch();
$id = $request->routeParameter('id');
$parameters = $request->routeParameters();
```

Route parameters can also be supplied through request attributes with `ApiRequest::ROUTE_PARAMETERS_ATTRIBUTE`.
