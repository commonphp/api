# Package Boundaries

CommonPHP API owns JSON-oriented surface dispatch and response conventions for API endpoints.

## Belongs Here

- API route mounting and route helper methods.
- API-specific request helpers for JSON payloads and route parameters.
- JSON response and problem response objects.
- API action contracts and action response helpers.
- Translation from validation results to API problem responses.
- Translation from known HTTP/router/API exceptions to API problem responses.

## Does Not Belong Here

- Low-level HTTP request and response primitives.
- Native response emission.
- General route collections, matchers, constraints, and dispatch primitives.
- Validation rule definitions and validators.
- Authentication, authorization, sessions, CSRF, or password hashing.
- Database access, persistence, repositories, or query builders.
- Runtime bootstrapping, module loading, service providers, or containers.
- HTML page rendering, templates, static assets, or UI components.

Those concerns should remain in their own packages and integrate through the API layer.

## Integration Shape

Application code should let `comphp/router` describe route matching, `comphp/http` carry request and response state, and `comphp/validation` produce `ValidationResult` objects. API then turns those pieces into a JSON endpoint experience.

For example, an action may validate input with Validation and return the `ValidationResult` directly. API will convert it into a `422` problem response without the validator needing to know about HTTP.
