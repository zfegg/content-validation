<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Transformer;

use Laminas\Filter\FilterPluginManager;
use Opis\JsonSchema\Info\SchemaInfo;
use Opis\JsonSchema\ValidationContext;

class LaminasTransformer implements TransformerInterface
{
    private FilterPluginManager $filters;
    private string $name;

    public function __construct(FilterPluginManager $filterManager, string $name)
    {
        $this->filters = $filterManager;
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function transform($data, ValidationContext $context, SchemaInfo $info, array $args)
    {
        return $this->filters->get($this->name, $args)->filter($data);
    }
}
