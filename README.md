# CommonPHP API

CommonPHP API provides the JSON/API surface layer for CommonPHP applications. It defines the structure for API-focused request handling, response generation, and integration with runtime, HTTP, routing, and action packages.

The package is intended for application endpoints that return structured data rather than rendered pages, keeping API behavior explicit, predictable, and separate from traditional web page rendering.

## Requirements

- PHP `^8.5`
- `comphp/runtime:^0.3`
- `comphp/http:^0.3`

## Installation

Once this package is available through your Composer repositories, install it with:

```bash
composer require comphp/api
```

## Usage

```php
<?php

// TODO: Write usage
```

## Package Notes

This package should focus on API-specific request handling, JSON responses, API actions, and API surface integration. General HTTP behavior belongs in `comphp/http`, and routing belongs in `comphp/router`.

## Error Handling

API failures should use CommonPHP package exceptions and should return structured error responses through the HTTP/API layer when used in a web request.

## Documentation

- [Usage](docs/usage.md)
- [Testing](TESTING.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## License

MIT. See [LICENSE.md](LICENSE.md).
