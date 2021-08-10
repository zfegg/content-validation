<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation\Opis\Resolver;

use Opis\JsonSchema\Info\SchemaInfo;
use Opis\JsonSchema\ValidationContext;
use PHPUnit\Framework\TestCase;
use Zfegg\ContentValidation\Opis\Resolver\TransformerResolver;
use ZfeggTest\ContentValidation\SetupTrait;

class LaminasFilterResolverTest extends TestCase
{
    use SetupTrait;

    public function testResolver(): void
    {
        /** @var TransformerResolver $resolver */
        $resolver = $this->container->get(TransformerResolver::class);
        $transformer = $resolver->resolve('laminas::toInt', 'integer');
        $rs = $transformer->transform(
            '123',
            $this->createMock(ValidationContext::class),
            $this->createMock(SchemaInfo::class),
            []
        );
        $this->assertIsInt($rs);
    }
}
