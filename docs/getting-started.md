# Getting Started

CommonPHP API gives an application a JSON-first surface built on the HTTP and Router packages.

## Install

```bash
composer require comphp/api
```

In this monorepo, the package is available through the workspace path repository and the root Composer autoloader.

## Create A Surface

```php
<?php

declare(strict_types=1);

use CommonPHP\API\ApiRequest;
use CommonPHP\API\ApiSurface;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Request;

$api = new ApiSurface();

$api->get('/status', static fn (ApiRequest $request): array => [
    'status' => 'ok',
    'path' => $request->path(),
]);

$response = $api->handle(new Request(RequestMethod::GET, '/api/status'));

echo $response->statusCode(); // 200
echo $response->body();       // {"status":"ok","path":"/api/status"}
```

By default, route helpers mount paths under `/api`. Registering `/status` creates a route for `/api/status`.

## Return Values

Action handlers may return:

- a `CommonPHP\HTTP\Response`, returned as-is;
- an `ApiProblem`, converted to `application/problem+json`;
- a `ValidationResult`, converted to a `422` problem response;
- `null`, converted to `204 No Content`;
- any other value, encoded as JSON.

```php
$api->post('/users', static function (ApiRequest $request): array {
    return [
        'name' => $request->input('name'),
    ];
});
```

## Request Payloads

`ApiRequest::payload()` reads parsed bodies first. If no parsed body exists, it accepts JSON request bodies and returns an array.

```php
$api->post('/users', static function (ApiRequest $request): array {
    $payload = $request->payload();

    return [
        'email' => $payload['email'] ?? null,
    ];
});
```

Use `input('nested.key')` for simple dot-notation lookup.
