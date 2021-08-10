<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis;

use Opis\JsonSchema\Info\SchemaInfo;
use Opis\JsonSchema\Keyword;
use Opis\JsonSchema\Parsers\KeywordParser;
use Opis\JsonSchema\Parsers\SchemaParser;
use Opis\JsonSchema\Parsers\VariablesTrait;
use Zfegg\ContentValidation\Opis\Keyword\TransformerKeyword;

class TransformersParser extends KeywordParser
{
    use VariablesTrait;
    private Resolver\ResolverInterface $resolver;
    private string $type;

    public function __construct(
        Resolver\ResolverInterface $resolver,
        string $keyword = '$transformers',
        string $type = self::TYPE_BEFORE
    ) {
        parent::__construct($keyword);
        $this->resolver = $resolver;
        $this->type = $type;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function parse(SchemaInfo $info, SchemaParser $parser, object $shared): ?Keyword
    {
        if (! $this->keywordExists($info)) {
            return null;
        }

        $transformers = $this->parseTransformers($parser, $this->keywordValue($info), $info);
        if (! $transformers) {
            return null;
        }

        return new TransformerKeyword($transformers);
    }

    /**
     * @param string|array|object $transformers
     */
    protected function parseTransformers(
        SchemaParser $parser,
        $transformers,
        SchemaInfo $info
    ): array {
        if (is_string($transformers) || is_object($transformers)) {
            $transformers = [$transformers];
        }

        if (is_array($transformers)) {
            $list = [];
            foreach ($transformers as $transformer) {
                if ($transformer = $this->parseTransformer($parser, $transformer, $info)) {
                    $list[] = $transformer;
                }
            }

            return $list;
        }

        throw $this->keywordException(
            '{keyword} can be a non-empty string, an object or an array of string and objects',
            $info
        );
    }

    /**
     * @param string|array|object $transformer
     */
    protected function parseTransformer(
        SchemaParser $parser,
        $transformer,
        SchemaInfo $info
    ): ?object {
        $vars = null;
        if (is_object($transformer)) {
            if (! property_exists($transformer, '$func') || ! is_string($transformer->{'$func'})) {
                throw $this->keywordException('$func (for {keyword}) must be a non-empty string', $info);
            }

            $vars = get_object_vars($transformer);
            unset($vars['$func']);

            if (property_exists($transformer, '$vars')) {
                if (! is_object($transformer->{'$vars'})) {
                    throw $this->keywordException('$vars (for {keyword}) must be a string', $info);
                }
                unset($vars['$vars']);
                $vars = get_object_vars($transformer->{'$vars'}) + $vars;
            }

            $transformer = $transformer->{'$func'};
        } elseif (! is_string($transformer) || ! $transformer) {
            throw $this->keywordException(
                '{keyword} can be a non-empty string, an object or an array of string and objects',
                $info
            );
        }

        $list = $this->resolver->resolveAll($transformer);
        if (! $list) {
            throw $this->keywordException("{keyword}: {$transformer} doesn't exists", $info);
        }

        return (object)[
            'name' => $transformer,
            'args' => $vars ? $this->createVariables($parser, $vars) : null,
            'types' => $list,
        ];
    }
}
