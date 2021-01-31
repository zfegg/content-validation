<?php declare(strict_types = 1);

namespace Zfegg\ContentValidation;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\InputFilter\InputFilterPluginManager;
use Psr\Http\Server\MiddlewareInterface;

class ContentValidationMiddlewareFactory
{

    public function __invoke(
        ContainerInterface $container,
        string $requestedName = null,
        array $options = null
    ): MiddlewareInterface {
        $config = $container->has('config')
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
            $config['overwrite_parsed_body'] ?? true,
            $config['route_name_with_method'] ?? false
        );
    }
}
