<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zfegg\ContentValidation\ContentValidationMiddleware;

class ContentValidationMiddlewareTest extends TestCase
{
    use SetupTrait;

    public function invokeProvider(): array
    {
        $schema = (object) [
            'type' => 'object',
            'properties' => (object) [
                'age' => (object) [
                    'type' => 'integer'
                ]
            ],
            'required' => ['age']
        ];

        return [
            'NotFoundSchema' => [
                '',
                new ServerRequest(),
            ],
            'HttpGetValid' => [
                $schema,
                new ServerRequest(
                    [],
                    [],
                    null,
                    'GET',
                    'php://input',
                    [],
                    [],
                    ['age' => 11]
                ),
            ],
            'HttpGetInvalid' => [
                $schema,
                new ServerRequest(
                    [],
                    [],
                    null,
                    'GET',
                    'php://input',
                    [],
                    [],
                ),
                false,
            ],
            'HttpPostValid' => [
                'test:test/test.json',
                new ServerRequest(
                    [],
                    [],
                    null,
                    'POST',
                    'php://input',
                    [],
                    [],
                    [],
                    ['name' => 'foo', 'age' => '18', 'sub' => ['foo' => '123'], 'list-obj' => [[['id' => 1]]]]
                ),
            ],
            'HttpPostInvalid' => [
                'test:test/test.json',
                new ServerRequest(
                    [],
                    [],
                    null,
                    'POST',
                    'php://input',
                    [],
                    [],
                    [],
                    ['name' => 'foo']
                ),
                false,
            ],
            'IgnoreWithCustomMethod' => [
                'test',
                new ServerRequest(
                    [],
                    [],
                    null,
                    'CUSTOM',
                    'php://input',
                    [],
                    [],
                    [],
                    ['age' => 101]
                ),
            ],
        ];
    }

    /**
     *
     * @dataProvider invokeProvider
     * @param string|array|null $schema
     */
    public function testInvoke($schema, ServerRequest $request, bool $success = true): void
    {
        $middleware = $this->container->get(ContentValidationMiddleware::class);
        $request = $request->withAttribute(ContentValidationMiddleware::SCHEMA, $schema);

        $response = $middleware->process(
            $request,
            new class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new Response\TextResponse('success');
                }
            }
        );

        if ($success) {
            $this->assertEquals('success', (string)$response->getBody());
        } else {
            $this->assertArrayHasKey(
                'validation_messages',
                json_decode((string)$response->getBody(), true)
            );
        }
    }

    public function testMezzio(): void
    {
        $schema = (object) [
            'type' => 'object',
            'properties' => (object) [
                'age' => (object) [
                    'type' => 'integer'
                ]
            ],
            'required' => ['age']
        ];
        $route = $this->createMock(Route::class);
        $route->method('getOptions')->willReturn(['schema' => $schema]);
        $this->routeTest([
            RouteResult::class => RouteResult::fromRoute($route)
        ]);
    }

    public function testMezzioOptionsWithMethod(): void
    {
        $schema = (object) [
            'type' => 'object',
            'properties' => (object) [
                'age' => (object) [
                    'type' => 'integer'
                ]
            ],
            'required' => ['age']
        ];
        $route = $this->createMock(Route::class);
        $route->method('getOptions')->willReturn(['schema:POST' => $schema]);
        $this->routeTest([
            RouteResult::class => RouteResult::fromRoute($route)
        ]);
    }

    public function testSlim(): void
    {
        $route = $this->createMock(\Slim\Routing\Route::class);
        $route->method('getArgument')->willReturn('test:test/test.json');
        $this->routeTest([
            'route' => $route
        ]);
    }

    private function routeTest(array $attrs): void
    {
        /** @var ContentValidationMiddleware $middleware */
        $middleware = $this->container->get(ContentValidationMiddleware::class);
        $request = new ServerRequest(
            [],
            [],
            null,
            'POST',
            'php://input',
            [],
            [],
            [],
            ['name' => 'foo',]
        );

        foreach ($attrs as $key => $val) {
            $request = $request->withAttribute($key, $val);
        }

        $response = $middleware->process(
            $request,
            new class implements RequestHandlerInterface {
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return new Response\TextResponse('success');
                }
            }
        );

        $this->assertEquals(422, $response->getStatusCode());
    }
}
