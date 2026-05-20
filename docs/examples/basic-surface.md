# Basic API Surface

```php
<?php

declare(strict_types=1);

use CommonPHP\API\ApiRequest;
use CommonPHP\API\ApiSurface;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Request;

$api = new ApiSurface();

$api->get('/status', static fn (): array => [
    'ready' => true,
]);

$api->post('/echo', static fn (ApiRequest $request): array => [
    'payload' => $request->payload(),
]);

$status = $api->handle(new Request(RequestMethod::GET, '/api/status'));
$echo = $api->handle(new Request(
    RequestMethod::POST,
    '/api/echo',
    ['Content-Type' => 'application/json'],
    '{"message":"hello"}',
));
```

The first response body is:

```json
{"ready":true}
```

The second response body is:

```json
{"payload":{"message":"hello"}}
```
