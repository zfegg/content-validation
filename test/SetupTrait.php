<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Opis\JsonSchema\Resolvers\FilterResolver;
use Opis\JsonSchema\Validator;
use Zfegg\ContentValidation\ContentValidationMiddleware;

trait SetupTrait
{

    private ServiceManager $container;

    public function setUp(): void
    {
        $config = new \ArrayObject(ArrayUtils::merge(
            [
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
            ],
            ArrayUtils::merge(
                (new \Zfegg\ContentValidation\ConfigProvider())(),
                (new \Laminas\Filter\ConfigProvider())()
            )
        ));
        $container = new ServiceManager($config['dependencies']);
        $container->setService('fooFilter', fn() => true);
        $container->setService('barFilter', fn() => true);
        $container->setService('config', $config);

        $this->container = $container;
    }
}
