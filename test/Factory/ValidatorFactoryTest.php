<?php

namespace ZfeggTest\ContentValidation\Factory;

use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use ZfeggTest\ContentValidation\SetupTrait;

class ValidatorFactoryTest extends TestCase
{
    use SetupTrait;

    public function testFactory(): void
    {
        $validator = $this->container->get(Validator::class);
        $data = <<<'JSON'
{
    "name": "John Doe",
    "age": "18",
    "unchecked": "sdf",
    "state": "0",
    "sub" : {
      "foo": "123"
    }
}
JSON;
        $data = json_decode($data);
        $result = $validator->validate($data, 'test:test/test.json');

        $this->assertTrue($result->isValid());
        $this->assertTrue(18 === $data->age);
        $this->assertFalse($data->state);
        $this->assertEquals('bar', $data->sub->bar);
        $this->assertObjectNotHasAttribute('unchecked', $data);
    }

}
