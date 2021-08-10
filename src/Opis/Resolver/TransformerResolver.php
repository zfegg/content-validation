<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Resolver;

use Zfegg\ContentValidation\Opis\Helper;

/**
 * Class TransformerResolver
 * @method \Zfegg\ContentValidation\Opis\Transformer\TransformerInterface resolve(string $name, string $type)
 */
class TransformerResolver implements ResolverInterface
{
    use RegisterTrait {
        resolveAll as private resolveAll2;
    }

    /**
     * @return \Zfegg\ContentValidation\Opis\Transformer\TransformerInterface[]|null
     */
    public function resolveAll(string $name): ?array
    {
        if (is_callable($name)) {
            return array_fill_keys(Helper::JSON_ALL_TYPES, $name);
        }

        return $this->resolveAll2($name);
    }
}
