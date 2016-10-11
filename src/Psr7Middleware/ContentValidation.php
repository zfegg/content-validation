<?php

namespace Zfegg\Psr7Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\ServiceManager\ServiceManager;
use Zfegg\ContentValidation\ContentValidationTrait;


/**
 * Class ContentValidation
 * @package Zfegg\Psr7Middleware
 */
class ContentValidation
{
    use ContentValidationTrait;

    public function __construct(array $options = [])
    {

    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $request->getAttribute('validator_name');
    }
}
