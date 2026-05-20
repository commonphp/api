# Problem Responses

API uses `ApiProblem` and `ApiProblemResponse` for structured failures.

The response content type is:

```text
application/problem+json; charset=utf-8
```

## Creating Problems

```php
use CommonPHP\API\ApiProblem;
use CommonPHP\HTTP\Enums\ResponseStatus;

$problem = ApiProblem::forStatus(
    ResponseStatus::CONFLICT,
    'The resource already exists.',
    code: 'resource_conflict',
);
```

The JSON body includes `type`, `title`, and `status`. It includes `detail`, `instance`, `code`, `errors`, and extension fields when supplied.

## Validation Problems

```php
$problem = ApiProblem::fromValidation($result);
```

Validation problems use:

- status `422`;
- title `Validation Failed`;
- code `validation_failed`;
- an `errors` array based on `ValidationResult::toArray()`.

## Problem Extensions

Use extension fields for safe, client-useful metadata:

```php
$problem = ApiProblem::forStatus(429, 'Too many requests.')
    ->withExtension('retry_after_seconds', 30);
```

Reserved fields such as `title` and `status` are not overwritten by extensions.

## Details In Production

Generic server exceptions should usually omit internal details. `ApiExceptionHandler` hides generic throwable details by default. Pass `exposeDetails: true` only in controlled development or test environments.
