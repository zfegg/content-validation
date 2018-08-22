<?php

namespace ZfeggTest\ContentValidation;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Route;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Expressive\Router\RouteResult;
use Zfegg\ContentValidation\ContentValidationMiddleware;
use Zfegg\ContentValidation\RouteNameContentValidationMiddleware;
use PHPUnit\Framework\TestCase;

class RouteNameContentValidationMiddlewareTest extends TestCase
{
    public function testExistsInputFilterName()
    {
        $middleware = new RouteNameContentValidationMiddleware();

        $req = $this->prophesize(ServerRequestInterface::class);
        $req->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)
            ->willReturn('test')
            ->shouldBeCalledTimes(2);

        $middleware->process($req->reveal(), $this->getHandler());
    }

    public function testProcessExpressive()
    {
        $middleware = new RouteNameContentValidationMiddleware();

        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->getMatchedRouteName()->willReturn('test')->shouldBeCalled();

        $req = $this->prophesize(ServerRequestInterface::class);
        $req->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)->willReturn(null);
        $req->getAttribute('Zend\Expressive\Router\RouteResult')
            ->willReturn($routeResult->reveal())
            ->shouldBeCalled();
        $this->withAttribute($req);

        $middleware->process($req->reveal(), $this->getHandler());
    }

    public function testProcessSlim()
    {
        $middleware = new RouteNameContentValidationMiddleware();

        $routeResult = $this->prophesize(Route::class);
        $routeResult->getName()->willReturn('test')->shouldBeCalled();

        $req = $this->prophesize(ServerRequestInterface::class);
        $req->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)->willReturn(null);
        $req->getAttribute('Zend\Expressive\Router\RouteResult')->willReturn(null)->shouldBeCalled();
        $req->getAttribute('route')->willReturn($routeResult);
        $this->withAttribute($req);

        $middleware->process($req->reveal(), $this->getHandler());
    }

    private function withAttribute($req, $value = 'test')
    {
        $req2 = $this->prophesize(ServerRequestInterface::class);
        $req2->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)->shouldBeCalled();

        $req->withAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME, $value)
            ->willReturn($req2)
            ->shouldBeCalled();
    }

    private function getHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        return $handler;
    }
}
