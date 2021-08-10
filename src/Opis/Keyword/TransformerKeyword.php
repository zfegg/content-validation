<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Keyword;

use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Exceptions\UnresolvedFilterException;
use Opis\JsonSchema\Keyword;
use Opis\JsonSchema\Keywords\ErrorTrait;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\ValidationContext;
use Zfegg\ContentValidation\Opis\Transformer\TransformerInterface;

class TransformerKeyword implements Keyword
{
    use ErrorTrait;
    use SetValueTrait;

    /** @var array|object[] */
    protected array $filters;

    /**
     * @param object[] $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * @inheritDoc
     */
    public function validate(ValidationContext $context, Schema $schema): ?ValidationError
    {
        $type = $context->currentDataType();
        $data = $context->currentData();

        foreach ($this->filters as $filter) {
            if (! isset($filter->types[$type])) {
                throw new UnresolvedFilterException($filter->name, $type, $schema, $context);
            }

            $func = $filter->types[$type];

            if ($filter->args) {
                $args = (array)$filter->args->resolve($context->rootData(), $context->currentDataPath());
                $args += $context->globals();
            } else {
                $args = $context->globals();
            }

            if ($func instanceof TransformerInterface) {
                $data = $func->transform($data, $context, $schema->info(), $args);
            } else {
                $data = $func($data, ...$args);
            }
        }

        $this->setValue($context, fn() => $data);

        return null;
    }
}
