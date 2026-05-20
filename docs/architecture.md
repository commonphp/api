# Architecture

CommonPHP API is a thin adapter between HTTP, Router, Validation, and application action code.

Its job is to:

- decide whether an HTTP request belongs to the API surface;
- adapt a `Request` into an `ApiRequest`;
- register API routes with a path prefix;
- dispatch action handlers;
- normalize action return values into HTTP responses;
- format known failures as JSON problem responses.

It should not become a full application framework.

Related pages:

- [Package boundaries](package-boundaries.md)
- [Requests](requests.md)
- [Responses](responses.md)
- [Routing and actions](routing-and-actions.md)
- [Error handling](error-handling.md)

## Request Flow

The current `ApiSurface::handle()` flow is:

1. Convert the incoming `Request` to `ApiRequest`.
2. Check the configured path prefix.
3. Match the request with `Router`.
4. Attach the `RouteMatch` and route parameters to the request.
5. Resolve the route handler shape.
6. Call the action, route handler, callable, controller method, or invokable class.
7. Convert the action result through `ApiResponseFactory::from()`.
8. Convert known exceptions through `ApiExceptionHandler`.

## Main Object Roles

| Object | Responsibility |
| --- | --- |
| `ApiSurface` | API mount, route registration, matching, dispatch, result normalization |
| `ApiRequest` | HTTP request plus API payload and route parameter helpers |
| `ApiResponseFactory` | JSON, success, problem, validation, created, accepted, and no-content responses |
| `JsonResponse` | JSON response that retains its original payload |
| `ApiProblem` | Serializable problem details object |
| `ApiProblemResponse` | `application/problem+json` response for an `ApiProblem` |
| `ApiExceptionHandler` | Maps known exceptions to problem responses |
| `ActionInterface` | Contract for action classes |
| `AbstractAction` | Optional base class with response helper methods |

## Design Philosophy

API should stay:

- easy to wire into any HTTP/runtime setup;
- predictable about JSON output;
- strict about transport errors;
- simple to debug from response status, problem code, and route label;
- small enough that routing, validation, and HTTP remain reusable on their own.
