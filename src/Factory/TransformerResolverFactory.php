<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Factory;

use Laminas\Filter\FilterPluginManager;
use Psr\Container\ContainerInterface;
use Zfegg\ContentValidation\Opis\Resolver\LaminasFilterResolver;
use Zfegg\ContentValidation\Opis\Resolver\TransformerResolver;

class TransformerResolverFactory
{
    public function __invoke(ContainerInterface $container): TransformerResolver
    {
        $resolver = new TransformerResolver();

        if ($container->has(FilterPluginManager::class)) {
            $resolver->registerNS('laminas', new LaminasFilterResolver($container->get(FilterPluginManager::class)));
        }

        return $resolver;
    }
}
