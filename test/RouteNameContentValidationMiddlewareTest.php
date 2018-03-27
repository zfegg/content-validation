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
    public function testProcessExpressive()
    {
        $middleware = new RouteNameContentValidationMiddleware();
        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->getMatchedRouteName()->willReturn('test')->shouldBeCalled();
        $req2 = $this->prophesize(ServerRequestInterface::class);
        $req2->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)->shouldBeCalled();
        $req = $this->prophesize(ServerRequestInterface::class);
        $req->getAttribute('Zend\Expressive\Router\RouteResult')
            ->willReturn($routeResult->reveal())
            ->shouldBeCalled();
        $req->withAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME, 'test')
            ->willReturn($req2->reveal())
            ->shouldBeCalled();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        $middleware->process($req->reveal(), $handler);
    }
    public function testProcessSlim()
    {
        $middleware = new RouteNameContentValidationMiddleware();

        $req2 = $this->prophesize(ServerRequestInterface::class);
        $req2->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)->shouldBeCalled();
        $req = $this->createMock(ServerRequestInterface::class);
        $req->method('getAttribute')->willReturnCallback(function ($name) {
            $routeResult = $this->createMock(Route::class);
            $routeResult->method('getName')
                ->willReturn('test');
            if ($name == 'route') {
                return $routeResult;
            }
        });
        $req->method('withAttribute')
            ->willReturn($req2->reveal());

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        $middleware->process($req, $handler);
    }
}
