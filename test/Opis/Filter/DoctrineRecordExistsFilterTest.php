<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation\Opis\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\ValidationContext;
use PHPUnit\Framework\TestCase;
use Zfegg\ContentValidation\Opis\Filter\DoctrineRecordExistsFilter;
use ZfeggTest\ContentValidation\Entity\Foo;

class DoctrineRecordExistsFilterTest extends TestCase
{
    const SQL = <<<SQL
create table foo
(
    id     INTEGER     not null primary key autoincrement,
    key    VARCHAR(32) not null,
    value  VARCHAR(32) not null
);
SQL;
    private EntityManager $em;

    protected function setUp(): void
    {
        $isDevMode = true;
        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . "/../../Entity"], $isDevMode);
        $em = EntityManager::create(['url' => 'sqlite:///:memory:',], $config);
        $em->getConnection()->prepare(self::SQL)->executeStatement();

        $this->em = $em;
    }

    public function testValidate(): void
    {
        $filter = new DoctrineRecordExistsFilter($this->em);

        $context = new ValidationContext('test', new SchemaLoader());
        $schema = $this->createMock(Schema::class);
        $rs = $filter->validate($context, $schema, ['entity' => Foo::class]);
        $this->assertTrue($rs);

        $rs = $filter->validate($context, $schema, ['entity' => Foo::class, 'exists' => true]);
        $this->assertFalse($rs);

        $rs = $filter->validate(
            $context,
            $schema,
            ['entity' => Foo::class, 'field' => 'key', 'criteria' => ['value' => 123]]
        );
        $this->assertTrue($rs);
    }
}
