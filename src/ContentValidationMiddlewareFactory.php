<?php


namespace Zfegg\ContentValidation;

use Psr\Container\ContainerInterface;
use Zend\InputFilter\InputFilterPluginManager;

class ContentValidationMiddlewareFactory
{

    public function __invoke(ContainerInterface $container)
    {
        return new ContentValidationMiddleware(
            $container->get(InputFilterPluginManager::class)
        );
    }
}
