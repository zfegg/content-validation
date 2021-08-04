<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Opis\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Opis\JsonSchema\Filter;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\ValidationContext;

class DoctrineRecordExistsFilter implements Filter
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function validate(ValidationContext $context, Schema $schema, array $args = []): bool
    {
        $criteria = $args['criteria'] ?? [];
        $exists = $args['exists'] ?? false;

        if (isset($args['field'])) {
            $rs = $this->em->getRepository($args['entity'])->findOneBy(
                [$args['field'] => $context->currentData()] + $criteria
            );
        } else {
            $rs = $this->em->find($args['entity'], $context->currentData());
        }

        return ((bool) $rs) == $exists;
    }
}
