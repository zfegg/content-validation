<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Resolver;

interface ResolverInterface
{
    /**
     * @return object|callable|null
     */
    public function resolve(string $name, string $type);

    /**
     * @return object[]|callable[]|null
     */
    public function resolveAll(string $name): ?array;
}
