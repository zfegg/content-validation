<?php

namespace ZfeggTest\ContentValidation\Factory;

use Opis\JsonSchema\Format;

class ExampleFormat implements Format
{

    /**
     * @inheritdoc
     */
    public function validate($data): bool
    {
        return true;
    }
}