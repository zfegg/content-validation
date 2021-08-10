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
        switch ($this->type) {
            case 'integer':
                return $this->toInteger($value);
            case 'number':
                return $this->toNumber($value);
            case 'string':
                return $this->toString($value);
            case 'boolean':
                return $this->toBoolean($value);
        }

        return $value;
    }


    /**
     * @param mixed $value
     */
    private function toInteger($value): ?int
    {
        if ($value === null) {
            return 0;
        }

        return is_scalar($value) ? intval($value) : null;
    }

    /**
     * @param mixed $value
     */
    private function toNumber($value): ?float
    {
        if ($value === null) {
            return 0.0;
        }

        return is_scalar($value) ? floatval($value) : null;
    }

    /**
     * @param mixed $value
     */
    private function toString($value): ?string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private function toBoolean($value): bool
    {
        return (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
