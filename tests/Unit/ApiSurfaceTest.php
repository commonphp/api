<?php

declare(strict_types=1);

namespace CommonPHP\API\Tests\Unit;

use CommonPHP\API\ApiProblem;
use CommonPHP\API\ApiRequest;
use CommonPHP\API\ApiResponseFactory;
use CommonPHP\API\ApiSurface;
use CommonPHP\API\Exceptions\ApiException;
use CommonPHP\API\Tests\Fixtures\ControllerAction;
use CommonPHP\API\Tests\Fixtures\EnvelopeAction;
use CommonPHP\API\Tests\Fixtures\InvokableAction;
use CommonPHP\API\Tests\Fixtures\PlainAction;
use CommonPHP\API\Tests\Fixtures\RecordingRouteHandler;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\Router\Enums\RouteMethod;
use CommonPHP\Router\Route;
use CommonPHP\Router\RouteGroup;
use CommonPHP\Router\Router;
use CommonPHP\Validation\ValidationError;
use CommonPHP\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

final class ApiSurfaceTest extends TestCase
{
    public function testItReportsPathPrefixAndDependencies(): void
    {
        $router = new Router();
        $responses = new ApiResponseFactory();
        $surface = new ApiSurface($router, '/service/', $responses);

        self::assertSame($router, $surface->router());
        self::assertSame($responses, $surface->responses());
        self::assertSame('/service', $surface->pathPrefix());
        self::assertTrue($surface->supports(new Request(RequestMethod::GET, '/service/users')));
        self::assertTrue($surface->supports(new Request(RequestMethod::GET, '/service')));
        self::assertFalse($surface->supports(new Request(RequestMethod::GET, '/api/users')));
    }

    public function testItHandlesCallableRoutesWithApiRequests(): void
    {
        $surface = new ApiSurface();
        $surface->get('/ping', static fn (ApiRequest $request): array => [
            'pong' => true,
            'request_class' => $request::class,
        ]);

        $response = $surface->handle(new Request(RequestMethod::GET, '/api/ping'));

        self::assertSame(200, $response->statusCode());
        self::assertSame([
            'pong' => true,
            'request_class' => ApiRequest::class,
        ], $this->decode($response));
    }

    public function testItPrefixesRouteHelpersAndGroups(): void
    {
        $surface = new ApiSurface();

        self::assertSame('/api/any', $surface->any('/any', static fn (): array => [])->path());
        self::assertSame('/api/get', $surface->get('/get', static fn (): array => [])->path());
        self::assertSame('/api/post', $surface->post('/post', static fn (): array => [])->path());
        self::assertSame('/api/put', $surface->put('/put', static fn (): array => [])->path());
        self::assertSame('/api/patch', $surface->patch('/patch', static fn (): array => [])->path());
        self::assertSame('/api/delete', $surface->delete('/delete', static fn (): array => [])->path());
        self::assertSame('/api/options', $surface->options('/options', static fn (): array => [])->path());
        self::assertSame('/api/custom', $surface->route(RouteMethod::GET, '/custom', static fn (): array => [])->path());

        $group = $surface->group('/v1', static function (RouteGroup $group): void {
            $group->get('/status', static fn (): array => ['ready' => true], 'status');
        }, 'v1.');

        self::assertSame('/api/v1', $group->prefix());
        self::assertSame('v1.status', $surface->router()->named('v1.status')->name());

        $response = $surface->handle(new Request(RequestMethod::GET, '/api/v1/status'));
        self::assertSame(['ready' => true], $this->decode($response));
    }

    public function testItCanAddAlreadyBuiltRoutes(): void
    {
        $surface = new ApiSurface();
        $surface->add(new Route(RouteMethod::GET, '/api/manual', static fn (): array => ['manual' => true]));

        self::assertSame(['manual' => true], $this->decode(
            $surface->handle(new Request(RequestMethod::GET, '/api/manual')),
        ));
    }

    public function testItSupportsRootMounts(): void
    {
        $surface = new ApiSurface(pathPrefix: '/');
        $surface->get('/status', static fn (): array => ['root' => true]);

        self::assertTrue($surface->supports(new Request(RequestMethod::GET, '/anything')));
        self::assertSame(['root' => true], $this->decode(
            $surface->handle(new Request(RequestMethod::GET, '/status')),
        ));
    }

    public function testItDispatchesAllSupportedHandlerShapes(): void
    {
        $surface = new ApiSurface();
        $controller = new ControllerAction();

        $surface->get('/callable', static fn (): array => ['kind' => 'callable']);
        $surface->get('/action/{id}', new PlainAction());
        $surface->get('/abstract/{id}', new EnvelopeAction());
        $surface->get('/route-handler', new RecordingRouteHandler());
        $surface->get('/invokable', InvokableAction::class);
        $surface->get('/class-at', ControllerAction::class . '@show');
        $surface->get('/class-static-syntax', ControllerAction::class . '::show');
        $surface->get('/array-class', [ControllerAction::class, 'show']);
        $surface->get('/array-object', [$controller, 'show']);

        self::assertSame('callable', $this->decodePath($surface, '/api/callable')['kind']);
        self::assertSame('action', $this->decodePath($surface, '/api/action/42')['kind']);
        self::assertSame([
            'status' => 'success',
            'data' => ['id' => '42'],
            'meta' => ['route' => 'GET /api/abstract/{id}'],
        ], $this->decodePath($surface, '/api/abstract/42'));
        self::assertSame('route-handler', $this->decodePath($surface, '/api/route-handler')['kind']);
        self::assertSame('invokable', $this->decodePath($surface, '/api/invokable')['kind']);
        self::assertSame('controller', $this->decodePath($surface, '/api/class-at')['kind']);
        self::assertSame('controller', $this->decodePath($surface, '/api/class-static-syntax')['kind']);
        self::assertSame('controller', $this->decodePath($surface, '/api/array-class')['kind']);
        self::assertSame('controller', $this->decodePath($surface, '/api/array-object')['kind']);
    }

    public function testItNormalizesActionResults(): void
    {
        $surface = new ApiSurface();
        $surface->get('/response', static fn (): Response => new Response('plain', ResponseStatus::ACCEPTED));
        $surface->get('/problem', static fn (): ApiProblem => ApiProblem::forStatus(409, 'Already exists.'));
        $surface->get('/validation', static fn (): ValidationResult => new ValidationResult([
            new ValidationError('name', 'Name is required.', 'required'),
        ]));
        $surface->get('/empty', static fn (): null => null);

        self::assertSame('plain', $surface->handle(new Request(RequestMethod::GET, '/api/response'))->body());
        self::assertSame(409, $surface->handle(new Request(RequestMethod::GET, '/api/problem'))->statusCode());
        self::assertSame(422, $surface->handle(new Request(RequestMethod::GET, '/api/validation'))->statusCode());
        self::assertSame(204, $surface->handle(new Request(RequestMethod::GET, '/api/empty'))->statusCode());
    }

    public function testItReturnsProblemResponsesForRoutingFailures(): void
    {
        $surface = new ApiSurface();
        $surface->get('/ping', static fn (): array => ['pong' => true]);

        $outsidePrefix = $surface->handle(new Request(RequestMethod::GET, '/web'));
        $notFound = $surface->handle(new Request(RequestMethod::GET, '/api/missing'));
        $methodNotAllowed = $surface->handle(new Request(RequestMethod::POST, '/api/ping'));

        self::assertSame(404, $outsidePrefix->statusCode());
        self::assertSame('api_route_not_found', $this->decode($outsidePrefix)['code']);
        self::assertSame(404, $notFound->statusCode());
        self::assertSame('api_route_not_found', $this->decode($notFound)['code']);
        self::assertSame(405, $methodNotAllowed->statusCode());
        self::assertSame('GET, HEAD', $methodNotAllowed->header('Allow'));
    }

    public function testItReturnsProblemResponsesForActionFailures(): void
    {
        $surface = new ApiSurface();
        $surface->get('/api-exception', static fn (): never => throw new ApiException('No access.', 403, 'forbidden'));
        $surface->get('/runtime-exception', static fn (): never => throw new RuntimeException('boom'));
        $surface->get('/bad-handler', new stdClass());
        $surface->get('/missing-class', 'CommonPHP\\API\\Tests\\Fixtures\\MissingAction@show');

        $apiException = $surface->handle(new Request(RequestMethod::GET, '/api/api-exception'));
        $runtimeException = $surface->handle(new Request(RequestMethod::GET, '/api/runtime-exception'));
        $badHandler = $surface->handle(new Request(RequestMethod::GET, '/api/bad-handler'));
        $missingClass = $surface->handle(new Request(RequestMethod::GET, '/api/missing-class'));

        self::assertSame(403, $apiException->statusCode());
        self::assertSame('forbidden', $this->decode($apiException)['code']);
        self::assertSame(500, $runtimeException->statusCode());
        self::assertSame('api.action_failed', $this->decode($runtimeException)['code']);
        self::assertSame(500, $badHandler->statusCode());
        self::assertSame('api.invalid_action', $this->decode($badHandler)['code']);
        self::assertSame(500, $missingClass->statusCode());
        self::assertSame('api.action_not_found', $this->decode($missingClass)['code']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePath(ApiSurface $surface, string $path): array
    {
        return $this->decode($surface->handle(new Request(RequestMethod::GET, $path)));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $decoded = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
