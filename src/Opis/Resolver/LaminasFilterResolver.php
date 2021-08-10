<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Resolver;

use Laminas\Filter\FilterPluginManager;
use Zfegg\ContentValidation\Opis\Transformer\LaminasTransformer;
use Zfegg\ContentValidation\Opis\Transformer\TransformerInterface;

class LaminasFilterResolver implements ResolverInterface
{
    private FilterPluginManager $filterManager;

    public function __construct(FilterPluginManager $filterManager)
    {
        $this->filterManager = $filterManager;
    }

    public function resolve(string $name, string $type): TransformerInterface
    {
        return new LaminasTransformer($this->filterManager, $name);
    }

    /**
     * @return TransformerInterface[]|null
     */
    public function resolveAll(string $name): ?array
    {
        $types = ['string', 'number', 'boolean', 'integer'];
        return array_fill_keys(
            $types,
            new LaminasTransformer($this->filterManager, $name)
        );
    }
}
