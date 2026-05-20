# CommonPHP API Documentation

CommonPHP API is the JSON surface layer for CommonPHP applications. It mounts API routes, adapts HTTP requests into API requests, dispatches action handlers, normalizes action return values, and turns expected failures into JSON problem responses.

The package is deliberately small. HTTP primitives stay in `comphp/http`, route matching stays in `comphp/router`, and reusable input validation stays in `comphp/validation`.

## Start Here

- [Getting started](getting-started.md)
- [Usage](usage.md)
- [Package boundaries](package-boundaries.md)

## API Concepts

- [Architecture](architecture.md)
- [Requests](requests.md)
- [Responses](responses.md)
- [Routing and actions](routing-and-actions.md)
- [Problem responses](problem-responses.md)
- [Error handling](error-handling.md)

## Examples

- [Examples index](examples/index.md)
- [Basic API surface](examples/basic-surface.md)
- [Action class](examples/action-class.md)
- [Validation errors](examples/validation-errors.md)

## Development

- [Testing and QA](testing.md)

## Public API Map

Entry points:

- `CommonPHP\API\ApiSurface`
- `CommonPHP\API\ApiRequest`
- `CommonPHP\API\ApiResponseFactory`
- `CommonPHP\API\ApiExceptionHandler`

Response objects:

- `CommonPHP\API\JsonResponse`
- `CommonPHP\API\ApiProblem`
- `CommonPHP\API\ApiProblemResponse`

Contracts:

- `CommonPHP\API\Contracts\ActionInterface`
- `CommonPHP\API\Contracts\AbstractAction`
- `CommonPHP\API\Contracts\ApiResponseInterface`

Enums:

- `CommonPHP\API\Enums\ApiStatus`

Exceptions:

- `CommonPHP\API\Exceptions\ApiException`
- `CommonPHP\API\Exceptions\InvalidActionException`
- `CommonPHP\API\Exceptions\UnsupportedContentTypeException`
