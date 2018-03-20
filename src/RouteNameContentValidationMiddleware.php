<?php

namespace Zfegg\ContentValidation;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class RouteNameContentValidationMiddleware extends ContentValidationMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Set expressive route name or slim route name
        if ($route = $request->getAttribute('Zend\Expressive\Router\RouteResult')) {
            $request = $request->withAttribute(
                ContentValidationMiddleware::INPUT_FILTER_NAME,
                $route->getMatchedRouteName()
            );
        } elseif ($route = $request->getAttribute('route')) {
            $request = $request->withAttribute(
                ContentValidationMiddleware::INPUT_FILTER_NAME,
                $route->getName() ? : $route->getIdentifier()
            );
        }

        return parent::process($request, $handler);
    }
}
