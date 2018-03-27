<?php


namespace Zfegg\ContentValidation\Exception;

class InvalidRequestException extends \RuntimeException
{
    protected $inputFilter;

    public function __construct($msg, $code, $inputFilter, $previous = null)
    {
        parent::__construct($msg, $code, $previous);

        $this->inputFilter = $inputFilter;
    }

    public function getInputFilter()
    {
        return $this->inputFilter;
    }
}
