<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation;

use Opis\JsonSchema\Validator;

class ConfigProvider
{

    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => [
                    ContentValidationMiddleware::class => ContentValidationMiddlewareFactory::class,
                    Validator::class => Factory\ValidatorFactory::class,
                    Opis\Resolver\TransformerResolver::class => Factory\TransformerResolverFactory::class,
                ]
            ]
        ];
    }
}
