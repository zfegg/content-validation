<?php


namespace Zfegg\ContentValidation\Opis;


use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Info\SchemaInfo;
use Opis\JsonSchema\Keyword;
use Opis\JsonSchema\Keywords\TypeKeyword;
use Opis\JsonSchema\Parsers\KeywordParser;
use Opis\JsonSchema\Parsers\SchemaParser;

class TypeCastParser extends KeywordParser
{
    public function __construct()
    {
        parent::__construct('type');
    }

    public function type(): string
    {
        return self::TYPE_PREPEND;
    }

    public function parse(SchemaInfo $info, SchemaParser $parser, object $shared): ?Keyword
    {
        $schema = $info->data();

        if (! $this->keywordExists($schema)) {
            return null;
        }

        $type = $this->keywordValue($schema);

        if (! is_string($type) || $type === 'object') {
            return null;
        }

        return new TypeCast($type);
    }
}