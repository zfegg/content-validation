<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Keyword;

use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Keyword;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\ValidationContext;

class TypeCast implements Keyword
{
    use SetValueTrait;

    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function validate(ValidationContext $context, Schema $schema): ?ValidationError
    {
        $this->setValue($context, [$this, 'castValue']);

        return null;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function castValue($value)
    {
        if ($value !== null && ! is_scalar($value)) {
            return $value;
        }

        switch ($this->type) {
            case 'integer':
                return intval($value);
            case 'number':
                return floatval($value);
            case 'string':
                return (string) $value;
            case 'boolean':
                return (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            default:
                return $value;
        }
    }
}
