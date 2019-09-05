<?php

namespace Zfegg\ContentValidation;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\InputFilter\InputFilterPluginManager;

class ContentValidationMiddlewareFactory
{

    public function __invoke(
        ContainerInterface $container,
        $requestedName = null,
        array $options = null
    ) {
        $config =  $container->has('config')
            ? $container->get('config')['zfegg'][ContentValidationMiddleware::class] ?? []
            : [];
        $requestedName = $requestedName ?: ContentValidationMiddleware::class;

        $response = $container->has(ResponseInterface::class) ?
            $container->get(ResponseInterface::class) : null;

        $inputFilterManager = $container->has(InputFilterPluginManager::class) ?
            $container->get(InputFilterPluginManager::class) : null;

        return new $requestedName(
            $inputFilterManager,
            null,
            $response,
            $config['overwrite_parsed_body'] ?? false
        );
    }
}
