<?php

declare(strict_types = 1);

namespace ZfeggTest\ContentValidation\Opis\Filter;

use Opis\JsonSchema\Schema;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\ValidationContext;
use Zfegg\ContentValidation\Opis\Filter\RecordExistsFilter;
use PHPUnit\Framework\TestCase;

class RecordExistsFilterTest extends TestCase
{
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
        $db = new \PDO('sqlite::memory:');
        $db->query(self::SQL);
        $this->db = $db;
    }

    public function testValidate(): void
    {
        $filter = new RecordExistsFilter($this->db);

        $context = new ValidationContext('test', new SchemaLoader());
        $schema = $this->createMock(Schema::class);
        $rs = $filter->validate($context, $schema, ['sql' => 'SELECT count(*) FROM foo where key=?']);
        $this->assertTrue($rs);

        $rs = $filter->validate($context, $schema, ['sql' => 'SELECT count(*) FROM foo where key=?', 'exists' => true]);
        $this->assertFalse($rs);

        $rs = $filter->validate($context, $schema, ['table' => 'foo', 'field' => 'key']);
        $this->assertTrue($rs);
    }
}
