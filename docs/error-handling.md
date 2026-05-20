# Error Handling

`ApiExceptionHandler` converts expected package exceptions into problem responses.

## Known Exception Mapping

| Exception | Response |
| --- | --- |
| `ApiException` | uses the exception status, code, message, and context |
| `ValidationException` with a result | `422` validation problem |
| `ValidationException` without a result | `422` problem |
| `InvalidRequestException` | `400` problem with code `invalid_request` |
| `RouteNotFoundException` | `404` problem with code `api_route_not_found` |
| `MethodNotAllowedException` | `405` problem with `Allow` header |
| `SchemaNotAllowedException` | `400` problem with code `scheme_not_allowed` |
| other `RouterException` | `500` problem with code `router_error` |
| other `HttpException` | `400` problem with code `http_error` |
| generic `Throwable` | `500` problem with code `internal_error` |

## API Exceptions

Use `ApiException` when action code needs to stop with a known API status:

```php
use CommonPHP\API\Exceptions\ApiException;

throw new ApiException(
    'The current user cannot access this resource.',
    403,
    'forbidden',
    ['permission' => 'users.view'],
);
```

The exception context is copied into the problem response as extension fields.

## Action Failures

Unexpected action failures are wrapped as `InvalidActionException::failed()` before the exception handler formats the response. The public response says which route failed without leaking the original exception message unless the handler is configured to expose details.

## Content Types

`ApiRequest::payload()` throws `UnsupportedContentTypeException` for non-empty bodies whose `Content-Type` is not JSON-compatible. That maps to `415 Unsupported Media Type`.
