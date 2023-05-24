<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Filter;

use Opis\JsonSchema\Errors\CustomError;
use Opis\JsonSchema\Filter;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\ValidationContext;
use Psr\Container\ContainerInterface;

class DbalRecordExistsFilter implements Filter
{

    private ContainerInterface $container;
    private string $defaultId;

    public function __construct(ContainerInterface $container, string $defaultId = 'db')
    {
        $this->container = $container;
        $this->defaultId = $defaultId;
    }

    public function validate(ValidationContext $context, Schema $schema, array $args = []): bool
    {
        /** @var \Doctrine\DBAL\Connection $db */
        $db = $this->container->get($args['db'] ?? $this->defaultId);

        if (isset($args['sql'])) {
            $sql = $args['sql'];
        } elseif (isset($args['table']) && isset($args['field'])) {
            $sql = sprintf('SELECT COUNT(*) FROM %s WHERE %s=?', $args['table'], $args['field']);
        } else {
            throw new \InvalidArgumentException('Invalid args.');
        }

        $exists = $args['exists'] ?? false;
        $sth = $db->prepare($sql);
        $row = $sth->executeQuery([$context->currentData()])->fetchNumeric();

        if ($row[0] == $exists) {
            return true;
        }

        if (empty($args["message"])) {
            return false;
        }

        throw new CustomError($args["message"], $args);
    }
}
