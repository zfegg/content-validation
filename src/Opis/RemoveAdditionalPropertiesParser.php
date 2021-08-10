<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis;

use Opis\JsonSchema\Info\SchemaInfo;
use Opis\JsonSchema\Keyword;
use Opis\JsonSchema\Parsers\KeywordParser;
use Opis\JsonSchema\Parsers\SchemaParser;
use Zfegg\ContentValidation\Opis\Keyword\RemoveAdditionalProperties;

class RemoveAdditionalPropertiesParser extends KeywordParser
{
    public function __construct()
    {
        parent::__construct('additionalProperties');
    }

    public function type(): string
    {
        return self::TYPE_BEFORE;
    }

    public function parse(SchemaInfo $info, SchemaParser $parser, object $shared): ?Keyword
    {
        $schema = $info->data();

        if (! $this->keywordExists($schema)) {
            return null;
        }

        $value = $this->keywordValue($schema);

        if ($value !== false) {
            return null;
        }

        return new RemoveAdditionalProperties();
    }
}
