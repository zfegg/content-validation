<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation\Opis;

use Opis\JsonSchema\Exceptions\InvalidKeywordException;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use ZfeggTest\ContentValidation\SetupTrait;

class TransformersParserTest extends TestCase
{

    use SetupTrait;

    public function json()
    {
        return [
            ['123'],
            ['[123]'],
            ['{"$func": 123}'],
            ['[{"$func": 123}]'],
            ['"not-exists"',]
        ];
    }

    /**
     * @dataProvider json
     */
    public function testErrorTransformersFormat(string $errorSchema): void
    {
        $this->expectException(InvalidKeywordException::class);
        $validator = $this->container->get(Validator::class);
        $data = <<<'JSON'
{
    "error-transformers": "John Doe"
}
JSON;
        $data = json_decode($data);
        $validator->validate(
            $data,
            <<<JSON
{
  "type": "object",
  "properties": {
    "error-transformers": {
      "type": "string",
      "\$transformers": $errorSchema
    }
  }
}
JSON
        );
    }

    public function testTransformerVars(): void
    {
        $validator = $this->container->get(Validator::class);
        $data = <<<'JSON'
{
    "name": "John Doe"
}
JSON;
        $data = json_decode($data);
        $result = $validator->validate(
            $data,
            <<<JSON
{
  "type": "object",
  "properties": {
    "name": {
      "type": "string",
      "\$transformers": {
        "\$func": "md5",
        "\$vars": {
          "binary": true
        }
      }
    }
  }
}
JSON
        );

        $this->assertTrue($result->isValid());
        $this->assertEquals(md5("John Doe", true), $data->name);
    }
}
