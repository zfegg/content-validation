<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Keyword;

use Opis\JsonSchema\ValidationContext;

trait SetValueTrait
{

    public function setValue(ValidationContext $context, callable $transform): void
    {
        $path = $context->currentDataPath();
        $data = $context->rootData();

        $target = $data;
        foreach ($path as $key) {
            if (is_object($target)) {
                $target = &$target->{$key};
            } else {
                $target = &$target[$key];
            }
        }

        $target = $transform($target);

        $resetPath = [];
        foreach (array_reverse($path) as $key) {
            array_unshift($resetPath, $key);
            $context->popDataPath();
            if ($context->currentDataType() != 'array') {
                break;
            }
        }

        foreach ($resetPath as $key) {
            $context->pushDataPath($key);
        }
    }
}
