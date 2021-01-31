<?php declare(strict_types = 1);

namespace Zfegg\ContentValidation\Exception;

use Laminas\InputFilter\InputFilterInterface;

class InvalidRequestException extends \RuntimeException
{
    /** @var InputFilterInterface   */
    protected $inputFilter;

    public function __construct(string $msg, int $code, InputFilterInterface $inputFilter, \Throwable $previous = null)
    {
        parent::__construct($msg, $code, $previous);

        $this->inputFilter = $inputFilter;
    }

    public function getInputFilter(): InputFilterInterface
    {
        return $this->inputFilter;
    }
}
