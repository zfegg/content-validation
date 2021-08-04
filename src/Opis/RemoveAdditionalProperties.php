<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis;

use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Keyword;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\ValidationContext;

class RemoveAdditionalProperties implements Keyword
{

    public function validate(ValidationContext $context, Schema $schema): ?ValidationError
    {
        $data = $context->currentData();

        if (! is_object($data)) {
            return null;
        }

        $keys = array_keys(get_object_vars($data));
        $allowKeys = array_keys(get_object_vars($schema->info()->data()->properties));

        $removeKeys = array_diff($keys, $allowKeys);
        foreach ($removeKeys as $key) {
            unset($data->{$key});
        }

        return null;
    }
}
