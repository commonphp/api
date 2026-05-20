# Action Class

```php
<?php

declare(strict_types=1);

use CommonPHP\API\ApiRequest;
use CommonPHP\API\ApiSurface;
use CommonPHP\API\Contracts\AbstractAction;
use CommonPHP\Router\RouteMatch;

final class ShowUserAction extends AbstractAction
{
    public function handle(ApiRequest $request, RouteMatch $match): mixed
    {
        return $this->ok([
            'id' => $request->routeParameter('id'),
            'include' => $request->query('include', 'profile'),
        ]);
    }
}

$api = new ApiSurface();
$api->get('/users/{id}', ShowUserAction::class);
```

Requesting `/api/users/42` returns a success envelope:

```json
{
  "status": "success",
  "data": {
    "id": "42",
    "include": "profile"
  }
}
```

Use plain `ActionInterface` when you do not need response helpers. Use `AbstractAction` when the helpers make the action easier to read.
