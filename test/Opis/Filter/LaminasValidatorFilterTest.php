<?php

namespace ZfeggTest\ContentValidation\Opis\Filter;

use Laminas\Validator\ValidatorPluginManager;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\Schemas\BooleanSchema;
use Opis\JsonSchema\ValidationContext;
use Zfegg\ContentValidation\Opis\Filter\LaminasValidatorFilter;
use PHPUnit\Framework\TestCase;
use Zfegg\ContentValidation\Opis\Filter\RecordExistsFilter;
use ZfeggTest\ContentValidation\SetupTrait;

class LaminasValidatorFilterTest extends TestCase
{

    use SetupTrait;

    public function testValidate(): void
    {
        $filter = new LaminasValidatorFilter(new ValidatorPluginManager(), 'hex');

        $context = new ValidationContext('test', new SchemaLoader());
        $schema = $this->createMock(Schema::class);
        $result = $this->validator->validate($data, (object) [
            "type" => "string",
            '$filters' => [
                ''
            ]
        ]);

        $rs = $filter->validate($context, $schema);
        $this->assertTrue($rs);
    }
}
