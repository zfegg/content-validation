<?php declare(strict_types = 1);

namespace Zfegg\ContentValidation;

use Interop\Container\ContainerInterface;

/**
 * Class ContentValidatioinListenerFactory
 */
class ContentValidationListenerFactory
{

    public function __invoke(ContainerInterface $container): ContentValidationListener
    {
        return new ContentValidationListener($container->get('InputFilterManager'));
    }
}
