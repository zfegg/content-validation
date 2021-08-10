<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Transformer;

use Opis\JsonSchema\Info\SchemaInfo;
use Opis\JsonSchema\ValidationContext;

interface TransformerInterface
{
    /**
     * @param mixed $data
     * @return mixed
     */
    public function transform($data, ValidationContext $context, SchemaInfo $info, array $args);
}
