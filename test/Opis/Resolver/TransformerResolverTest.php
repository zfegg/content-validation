<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation\Opis\Resolver;

use Zfegg\ContentValidation\Opis\Resolver\TransformerResolver;
use PHPUnit\Framework\TestCase;

class TransformerResolverTest extends TestCase
{
    public function testRegister(): void
    {
        $resolver = new TransformerResolver();
        $obj = new \stdClass();
        $obj2 = new \stdClass();
        $resolver->register('string', 'test', $obj);
        $resolver->registerMultipleTypes('test2', $obj2);

        $resolver2 = new TransformerResolver();
        $foo = new \stdClass();
        $resolver->registerNS('resolver2', $resolver2);
        $resolver->registerNS('resolver3', $resolver2);
        $resolver->registerMultipleTypes('resolver2::foo', $foo);
        $resolver->register('integer', 'resolver2::foo2', $foo);

        $this->assertEquals($obj, $resolver->resolve('test', 'string'));
        $this->assertCount(7, $resolver->resolveAll('test2'));
        $this->assertEquals($obj2, $resolver->resolve('test2', 'string'));
        $this->assertNull($resolver->resolve('test', 'integer'));

        $this->assertEquals($foo, $resolver->resolve('resolver2::foo', 'integer'));

        $this->assertTrue($resolver->unregister('test'));
        $this->assertTrue($resolver->unregister('test2', 'string'));
        $this->assertTrue($resolver->unregister('resolver2::foo2'));
        $this->assertTrue($resolver->unregisterNS('resolver2'));
        $this->assertTrue($resolver->unregisterNS('resolver3'));

        $this->assertNull($resolver->resolveAll('test'));
        $this->assertNull($resolver->resolve('test2', 'string'));
        $this->assertNull($resolver->resolve('resolver2::foo', 'string'));

        $this->assertFalse($resolver->unregister('undefined'));       // call unregister undefined
        $this->assertFalse($resolver->unregister('test2', 'string')); // re-call unregister
        $this->assertFalse($resolver->unregisterNS('undefined resolver')); // re-call unregister
    }
}
