<?php

namespace Zfegg\ContentValidation;

use Interop\Container\ContainerInterface;

/**
 * Class ContentValidatioinListenerFactory
 * @package Zfegg\ContentValidation
 */
class ContentValidationListenerFactory
{

    public function __invoke(ContainerInterface $container)
    {
        $listener = new ContentValidationListener();
        $listener->setInputFilterManager($container->get('InputFilterManager'));

        return $listener;
    }
}
