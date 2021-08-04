<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Factory;

use Opis\JsonSchema\Validator;
use Psr\Container\ContainerInterface;
use Zfegg\ContentValidation\Opis\Filter\DoctrineRecordExistsFilter;
use Zfegg\ContentValidation\Opis\Filter\RecordExistsFilter;
use Zfegg\ContentValidation\Opis\RemoveAdditionalPropertiesParser;
use Zfegg\ContentValidation\Opis\TypeCastParser;

class ValidatorFactory
{
    public function __invoke(ContainerInterface $container): Validator
    {
        $validator = new Validator();
        $parser = $validator->loader()->parser();
        $parser->draft($parser->defaultDraftVersion())
            ->prependKeyword(new TypeCastParser())
            ->prependKeyword(new RemoveAdditionalPropertiesParser())
        ;

        $types = ['string', 'integer', 'number'];
        $parser->getFilterResolver()->registerMultipleTypes(
            'db-exists',
            new RecordExistsFilter($container),
            $types
        );
        $parser->getFilterResolver()->registerMultipleTypes(
            'orm-exists',
            new DoctrineRecordExistsFilter($container),
            $types
        );

        return $validator;
    }
}
