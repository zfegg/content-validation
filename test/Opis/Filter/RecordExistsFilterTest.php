<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation\Opis\Filter;

use Opis\JsonSchema\Schema;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\ValidationContext;
use Opis\JsonSchema\Validator;
use Zfegg\ContentValidation\Opis\Filter\RecordExistsFilter;
use PHPUnit\Framework\TestCase;
use ZfeggTest\ContentValidation\SetupTrait;

class RecordExistsFilterTest extends TestCase
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
    private $db;

    protected function setUp(): void
    {
        $this->setUpContainer();
        $db = new \PDO('sqlite::memory:');
        $db->query(self::SQL);
        $db->query('INSERT INTO foo VALUES(NULL, "exists","123")');
        $this->container->setService('db', $db);
    }

    public function testValidate(): void
    {
        $filter = new RecordExistsFilter($this->container);

        $context = new ValidationContext('test', new SchemaLoader());
        $schema = $this->createMock(Schema::class);
        $rs = $filter->validate($context, $schema, ['sql' => 'SELECT count(*) FROM foo where key=?']);
        $this->assertTrue($rs);

        $rs = $filter->validate($context, $schema, ['sql' => 'SELECT count(*) FROM foo where key=?', 'exists' => true]);
        $this->assertFalse($rs);

        $rs = $filter->validate($context, $schema, ['table' => 'foo', 'field' => 'key']);
        $this->assertTrue($rs);
    }


    public function testInValidator(): void
    {
        $validator = $this->container->get(Validator::class);
        $data = <<<'JSON'
{"key": "exists"}
JSON;
        $data = json_decode($data);
        $result = $validator->validate($data, 'test:test/test-db-filter.json');

        $this->assertTrue($result->isValid());
    }
}
