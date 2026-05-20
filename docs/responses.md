# Responses

`ApiResponseFactory` creates API-friendly HTTP responses and normalizes action return values.

## JSON Responses

```php
use CommonPHP\API\ApiResponseFactory;

$response = (new ApiResponseFactory())->json([
    'ok' => true,
]);
```

`JsonResponse` sets `Content-Type: application/json; charset=utf-8`, keeps the original payload available through `payload()`, and reports its coarse API status with `apiStatus()`.

## Success Envelopes

Use `success()` or `ok()` when an endpoint should return a consistent envelope:

```php
$response = $responses->success(
    ['id' => 1],
    ['page' => 1],
);
```

The payload shape is:

```json
{
  "status": "success",
  "data": {
    "id": 1
  },
  "meta": {
    "page": 1
  }
}
```

## Creation, Accepted, And No Content

```php
$created = $responses->created(['id' => 1], '/api/items/1');
$accepted = $responses->accepted(['queued' => true]);
$empty = $responses->noContent();
```

`created()` adds a `Location` header when a location is provided. `noContent()` returns a plain `204` response with an empty body.

## Result Normalization

`from()` turns common action results into responses:

| Result | Response |
| --- | --- |
| `Response` | returned unchanged |
| `ApiProblem` | `ApiProblemResponse` |
| `ValidationResult` | `422` `ApiProblemResponse` |
| `null` | `204 No Content` |
| anything else | `JsonResponse` |

This keeps actions small while still allowing explicit responses when needed.
