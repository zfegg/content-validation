<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation\Opis\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\ValidationContext;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Zfegg\ContentValidation\Opis\Filter\DoctrineRecordExistsFilter;
use ZfeggTest\ContentValidation\Entity\Foo;
use ZfeggTest\ContentValidation\SetupTrait;

class DoctrineRecordExistsFilterTest extends TestCase
{
    use SetupTrait {
        setUp as setUpContainer;
    }

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
        $this->setUpContainer();
        $isDevMode = true;
        $config = Setup::createAnnotationMetadataConfiguration([__DIR__ . "/../../Entity"], $isDevMode);
        $em = EntityManager::create(['url' => 'sqlite:///:memory:',], $config);
        $em->getConnection()->prepare(self::SQL)->executeStatement();
        $em->getConnection()->prepare('INSERT INTO foo VALUES(NULL, "exists","123")')->executeStatement();
        $this->container->setService(EntityManagerInterface::class, $em);
    }

    public function testValidate(): void
    {
        $filter = new DoctrineRecordExistsFilter($this->container);
        $entity = Foo::class;

        $context = new ValidationContext('test', new SchemaLoader());
        $schema = $this->createMock(Schema::class);
        $rs = $filter->validate($context, $schema, ['entity' => $entity]);
        $this->assertTrue($rs);

        $rs = $filter->validate($context, $schema, ['entity' => $entity, 'exists' => true]);
        $this->assertFalse($rs);

        $rs = $filter->validate(
            $context,
            $schema,
            ['dql' => "SELECT COUNT(o) FROM {$entity} o WHERE o.key=?1"]
        );
        $this->assertTrue($rs);
    }

    public function testInValidator(): void
    {
        $validator = $this->container->get(Validator::class);
        $data = <<<'JSON'
{"key": "exists"}
JSON;
        $data = json_decode($data);
        $result = $validator->validate($data, 'test:test/test-doctrine-filter.json');

        $this->assertTrue($result->isValid());
    }
}
