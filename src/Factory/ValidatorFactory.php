<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation\Factory;

use Opis\JsonSchema\Validator;
use Psr\Container\ContainerInterface;
use Zfegg\ContentValidation\Opis\Filter\DbalRecordExistsFilter;
use Zfegg\ContentValidation\Opis\Filter\DoctrineRecordExistsFilter;
use Zfegg\ContentValidation\Opis\Filter\RecordExistsFilter;
use Zfegg\ContentValidation\Opis\RemoveAdditionalPropertiesParser;
use Zfegg\ContentValidation\Opis\Resolver\TransformerResolver;
use Zfegg\ContentValidation\Opis\TransformersParser;
use Zfegg\ContentValidation\Opis\TypeCastParser;

class ValidatorFactory
{
    public function __invoke(ContainerInterface $container): Validator
    {
        $config = $container->has('config') ? ($container->get('config')[Validator::class] ?? []) : [];

        $validator = new Validator();
        $parser = $validator->loader()->parser();

        foreach ($parser->supportedDrafts() as $draft) {
            $parser->draft($draft)
                ->prependKeyword(new TypeCastParser())
                ->prependKeyword(new RemoveAdditionalPropertiesParser())
                ->appendKeyword(new TransformersParser($container->get(TransformerResolver::class)))
            ;
        }

        if (isset($config['resolvers'])) {
            foreach ($config['resolvers'] as $key => $resolvers) {
                foreach ($resolvers as $resolver) {
                    $validator->resolver()->{'register' . ucfirst($key)}(...$resolver);
                }
            }
        }

        $filterResolver = $parser->getFilterResolver();
        if (isset($config['filters'])) {
            foreach ($config['filters'] as $name => $filter) {
                $filter = is_string($filter) ? ['filter' => $container->get($filter)] : $filter;
                $filter['filter'] = is_string($filter['filter'])
                    ? $container->get($filter['filter'])
                    : $filter['filter'];
                $filterResolver->registerMultipleTypes($name, $filter['filter'], $filter['types'] ?? null);
            }
        }
        if (isset($config['filterNs'])) {
            foreach ($config['filterNs'] as $ns => $resolver) {
                $filterResolver->registerNS($ns, is_string($resolver) ? $container->get($resolver) : $resolver);
            }
        }

        if (isset($config['formats'])) {
            $formatResolver = $parser->getFormatResolver();

            foreach ($config['formats'] as $type => $formats) {
                foreach ($formats as $name => $callable) {
                    if (is_callable($callable)) {
                        $formatResolver->registerCallable($type, $name, $callable);
                    } elseif (is_string($callable) && $container->has($callable)) {
                        $formatResolver->register($type, $name, $container->get($callable));
                    }
                }
            }
        }

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
        $parser->getFilterResolver()->registerMultipleTypes(
            'dbal-exists',
            new DbalRecordExistsFilter($container),
            $types
        );

        return $validator;
    }
}
