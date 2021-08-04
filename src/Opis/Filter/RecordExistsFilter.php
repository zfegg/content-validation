<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Filter;

use Opis\JsonSchema\Filter;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\ValidationContext;
use PDO;

class RecordExistsFilter implements Filter
{

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function validate(ValidationContext $context, Schema $schema, array $args = []): bool
    {
        if (isset($args['sql'])) {
            $sql = $args['sql'];
        } elseif (isset($args['table']) && isset($args['field'])) {
            $sql = sprintf('SELECT COUNT(*) FROM %s WHERE %s=?', $args['table'], $args['field']);
        } else {
            throw new \InvalidArgumentException('Invalid args.');
        }

        $exists = $args['exists'] ?? false;
        $sth = $this->db->prepare($sql);
        $sth->execute([$context->currentData()]);
        $row = $sth->fetch(PDO::FETCH_NUM);

        return $row[0] == $exists;
    }
}
