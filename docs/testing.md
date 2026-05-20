# Testing And QA

The API package has a package-local PHPUnit suite.

## Run Tests

From the workspace root:

```bash
vendor/bin/phpunit -c package/api/phpunit.xml.dist
```

On Windows:

```bat
vendor\bin\phpunit.bat -c package\api\phpunit.xml.dist
```

From the package root, use the local or workspace PHPUnit binary depending on how dependencies were installed.

## Test Coverage Goals

The suite covers:

- API status mapping;
- problem details construction and serialization;
- JSON and problem responses;
- response factory helpers and return-value normalization;
- API request adaptation, payload parsing, input lookup, and route parameters;
- API exception objects;
- exception-to-problem mapping;
- abstract action helper methods;
- API surface route registration, dispatch, handler shapes, result normalization, and failure responses.

## Fixtures

Test fixtures live under `tests/Fixtures`. They are intentionally small and exercise the handler shapes supported by `ApiSurface`.

## Adding Tests

Prefer focused unit tests for new public methods. If a change affects dispatch behavior, add at least one `ApiSurfaceTest` case that runs through `handle()` and asserts the final response status, headers, and body.
