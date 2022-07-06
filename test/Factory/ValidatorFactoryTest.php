<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation\Factory;

use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use ZfeggTest\ContentValidation\SetupTrait;

class ValidatorFactoryTest extends TestCase
{
    use SetupTrait;

    public static function isUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    public function testFactory(): void
    {
        $validator = $this->container->get(Validator::class);
        $data = <<<'JSON'
{
    "name": "<b>John Doe</b>",
    "transformer-string": "John Doe",
    "transformer-array": "John Doe",
    "transformer-object": "John Doe",
    "age": "18abcd",
    "unchecked": "sdf",
    "state": "0",
    "sub" : {
      "foo": "123"
    },
    "format-url":"http://localhost",
    "format-example":"sdfsdf",
    "list": [["1a"]]
}
JSON;
        $data = json_decode($data);
        $result = $validator->validate($data, 'test:test/test.json');

        $this->assertTrue($result->isValid());
        $this->assertTrue(18 === $data->age);
        $this->assertFalse($data->state);
        $this->assertEquals('JOHN DOE', $data->name);
        $this->assertEquals('JOHN DOE', $data->{"transformer-string"});
        $this->assertEquals('JOHN DOE', $data->{"transformer-array"});
        $this->assertEquals('JOHN DOE', $data->{"transformer-object"});
        $this->assertEquals('bar', $data->sub->bar);
        $this->assertEquals(1, $data->list[0][0]);
        $this->assertObjectNotHasAttribute('unchecked', $data);
    }
}
