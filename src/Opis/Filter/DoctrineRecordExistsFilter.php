<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Opis\JsonSchema\Errors\CustomError;
use Opis\JsonSchema\Filter;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\ValidationContext;
use Psr\Container\ContainerInterface;

class DoctrineRecordExistsFilter implements Filter
{
    private ContainerInterface $container;
    private string $defaultId;

    public function __construct(ContainerInterface $container, string $defaultId = EntityManagerInterface::class)
    {
        $this->container = $container;
        $this->defaultId = $defaultId;
    }

    public function validate(ValidationContext $context, Schema $schema, array $args = []): bool
    {
        /** @var EntityManagerInterface $em */
        $em = $this->container->get($args['db'] ?? $this->defaultId);

        if (isset($args['dql'])) {
            $dql = $args['dql'];
        } elseif (isset($args['entity'])) {
            $field = isset($args['field']) ? 'o.' . $args['field'] : 'o';
            $dql = sprintf('SELECT COUNT(o) FROM %s o WHERE %s=?1', $args['entity'], $field);
        } else {
            throw new \InvalidArgumentException('Invalid args.');
        }

        $exists = $args['exists'] ?? false;
        $query = $em->createQuery($dql);
        $query->setParameter(1, $context->currentData());
        $row = $query->getSingleScalarResult();

        if ($row == $exists) {
            return true;
        }

        if (empty($args["message"])) {
            return false;
        }

        throw new CustomError($args["message"], $args);
    }
}
