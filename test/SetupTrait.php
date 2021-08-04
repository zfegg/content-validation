<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation;

use Laminas\ServiceManager\ServiceManager;
use Opis\JsonSchema\Validator;
use Psr\Container\ContainerInterface;
use Zfegg\ContentValidation\ConfigProvider;
use Zfegg\ContentValidation\ContentValidationMiddleware;

trait SetupTrait
{

    private ContainerInterface $container;

    public function setUp(): void
    {
        $config = (new ConfigProvider())();
        $container = new ServiceManager($config['dependencies']);
        $container->setService(
            'config',
            ['zfegg' => [ContentValidationMiddleware::class => ['transform_object_to_array' => true]]]
        );

        /** @var Validator $validator */
        $validator = $container->get(Validator::class);
        $validator->resolver()->registerPrefix('test:test/', __DIR__);
        $middleware = $container->get(ContentValidationMiddleware::class);

        $this->container = $container;
    }
}
