<?php declare(strict_types = 1);

namespace Zfegg\ContentValidation;

class ConfigProvider
{

    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => [
                    ContentValidationMiddleware::class => ContentValidationMiddlewareFactory::class,
                    RouteNameContentValidationMiddleware::class => ContentValidationMiddlewareFactory::class,
                ]
            ]
        ];
    }
}
