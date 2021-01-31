<?php declare(strict_types = 1);

namespace Zfegg\ContentValidation;

use Laminas\InputFilter\InputFilterPluginManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class RouteNameContentValidationMiddleware extends ContentValidationMiddleware
{
    /**
     * @var bool
     */
    private $routeNameWithMethod;

    public function __construct(
        ?InputFilterPluginManager $inputFilters = null,
        ?callable $invalidHandler = null,
        ?callable $responseFactory = null,
        bool $overwriteParsedBody = true,
        bool $routeNameWithMethod = false
    ) {
        parent::__construct($inputFilters, $invalidHandler, $responseFactory, $overwriteParsedBody);
        $this->routeNameWithMethod = $routeNameWithMethod;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ($request->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME)) {
            return parent::process($request, $handler);
        }

        // Set Mezzio route name or slim route name
        if ($route = $request->getAttribute('Mezzio\Router\RouteResult')) {
            $request = $request->withAttribute(
                ContentValidationMiddleware::INPUT_FILTER_NAME,
                $route->getMatchedRouteName() . ($this->routeNameWithMethod ? ":{$request->getMethod()}" : '')
            );
        } elseif ($route = $request->getAttribute('route')) {
            $request = $request->withAttribute(
                ContentValidationMiddleware::INPUT_FILTER_NAME,
                ($route->getName() ? : $route->getIdentifier()) .
                ($this->routeNameWithMethod ? ":{$request->getMethod()}" : '')
            );
        }

        return parent::process($request, $handler);
    }
}
