<?php declare(strict_types = 1);

namespace ZfeggTest\ContentValidation;

use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Route;
use Laminas\Diactoros\Response;
use Mezzio\Router\RouteResult;
use Zfegg\ContentValidation\ContentValidationMiddleware;
use Zfegg\ContentValidation\RouteNameContentValidationMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RouteNameContentValidationMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    public function testExistsInputFilterName(): void
    {
        $middleware = new RouteNameContentValidationMiddleware();

        $req = $this->prophesize(ServerRequestInterface::class);
        $req->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)
            ->willReturn('test')
            ->shouldBeCalledTimes(2);

        $middleware->process($req->reveal(), $this->getHandler());
    }

    public function testProcessMezzio(): void
    {
//        $middleware = new RouteNameContentValidationMiddleware();

//        $routeResult = $this->prophesize(RouteResult::class);
//        $routeResult->getMatchedRouteName()->willReturn('test')->shouldBeCalled();

//        $req = $this->prophesize(ServerRequestInterface::class);
//        $req->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)->willReturn(null);
//        $req->getAttribute('Mezzio\Router\RouteResult')
//            ->willReturn($routeResult->reveal())
//            ->shouldBeCalled();
        $middleware = new RouteNameContentValidationMiddleware(null, null, null, true, true);

        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->getMatchedRouteName()->willReturn('test')->shouldBeCalled();

        $req = $this->prophesize(ServerRequestInterface::class);
        $req->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)->willReturn(null);
        $req->getAttribute('Mezzio\Router\RouteResult')
            ->willReturn($routeResult->reveal())
            ->shouldBeCalled();
        $req->getMethod()->willReturn('GET')->shouldBeCalled();
        $this->withAttribute($req, 'test:GET');
        $req = $req->reveal();

        $middleware->process($req, $this->getHandler());
    }

    public function testProcessSlim(): void
    {
        $middleware = new RouteNameContentValidationMiddleware();

        $routeResult = $this->prophesize(Route::class);
        $routeResult->getName()->willReturn('test')->shouldBeCalled();

        $req = $this->prophesize(ServerRequestInterface::class);
        $req->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)->willReturn(null);
        $req->getAttribute('Mezzio\Router\RouteResult')->willReturn(null)->shouldBeCalled();
        $req->getAttribute('route')->willReturn($routeResult);
        $this->withAttribute($req);

        $middleware->process($req->reveal(), $this->getHandler());
    }

    private function withAttribute(ObjectProphecy $req, string $value = 'test'): void
    {
        $req2 = $this->prophesize(ServerRequestInterface::class);
        $req2->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)->shouldBeCalled();

        $req->withAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME, $value)
            ->willReturn($req2->reveal())
            ->shouldBeCalled();
    }

    private function getHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        return $handler;
    }
}
