# Validation Errors

Actions can return `ValidationResult` directly. API turns failed results into `422` problem responses.

```php
<?php

declare(strict_types=1);

use CommonPHP\API\ApiRequest;
use CommonPHP\API\ApiSurface;
use CommonPHP\Validation\Validator;

$api = new ApiSurface();

$api->post('/users', static function (ApiRequest $request): mixed {
    $result = Validator::make()->input($request->payload(), [
        'name' => 'required|string',
        'email' => 'required|email',
    ]);

    if ($result->fails()) {
        return $result;
    }

    return [
        'created' => true,
        'email' => $request->input('email'),
    ];
});
```

An invalid request produces a response like:

```json
{
  "type": "about:blank",
  "title": "Validation Failed",
  "status": 422,
  "detail": "The submitted data did not pass validation.",
  "code": "validation_failed",
  "errors": [
    {
      "field": "email",
      "message": "The email field must be a valid email address.",
      "code": "email",
      "value": "not-an-email",
      "context": []
    }
  ]
}
```
