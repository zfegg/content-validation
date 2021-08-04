<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation;

use Laminas\ServiceManager\ServiceManager;
use Opis\JsonSchema\Resolvers\FilterResolver;
use Opis\JsonSchema\Validator;
use Zfegg\ContentValidation\ConfigProvider;
use Zfegg\ContentValidation\ContentValidationMiddleware;

trait SetupTrait
{

    private ServiceManager $container;

    public function setUp(): void
    {
        $config = (new ConfigProvider())();
        $container = new ServiceManager($config['dependencies']);
        $container->setService('fooFilter', fn() => true);
        $container->setService('barFilter', fn() => true);
        $container->setService(
            'config',
            new \ArrayObject([
                'zfegg' => [ContentValidationMiddleware::class => ['transform_object_to_array' => true]],
                Validator::class => [
                    'resolvers' => [
                        'prefix' => [
                            ['test:test/', __DIR__]
                        ],
                    ],
                    'filters' => [
                        'fooFilter' => 'fooFilter',
                        'barFilter' => ['filter' => 'barFilter'],
                    ],
                    'filterNs' => [
                        'fooNs' => new FilterResolver()
                    ]
                ]
            ])
        );

        $this->container = $container;
    }
}
