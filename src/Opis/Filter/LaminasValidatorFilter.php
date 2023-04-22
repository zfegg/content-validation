<?php

namespace Zfegg\ContentValidation\Opis\Filter;

use Laminas\Validator\ValidatorPluginManager;
use Opis\JsonSchema\Errors\CustomError;
use Opis\JsonSchema\Filter;
use Opis\JsonSchema\Schema;
use Opis\JsonSchema\ValidationContext;
use Opis\JsonSchema\Validator;

class LaminasValidatorFilter implements Filter
{

    private ValidatorPluginManager $validators;

    public function __construct(
        ValidatorPluginManager $validators
    ) {
        $this->validators = $validators;
    }

    public function validate(ValidationContext $context, Schema $schema, array $args = []): bool
    {

        $validator = new Validator();
        $validator->
        /** @var SmsCode $validator */
        $validator = $this->validators->get(SmsCode::class, $args);
        $result = $validator->isValid($context->currentData(), (array)$context->rootData());

        if (! $result) {
            throw new CustomError(current($validator->getMessages()));
        }

        return true;
    }
}