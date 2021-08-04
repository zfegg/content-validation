<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation;

use Opis\JsonSchema\Validator;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

class ContentValidationMiddlewareFactory
{

    public function __invoke(ContainerInterface $container): MiddlewareInterface
    {
        $config = $container->has('config')
            ? $container->get('config')['zfegg'][ContentValidationMiddleware::class] ?? []
            : [];
        $container->get(Validator::class);

        return new ContentValidationMiddleware(
            $container->get(Validator::class),
            null,
            $config['route_name_with_method'] ?? true,
            $config['transform_object_to_array'] ?? true,
        );
    }
}
