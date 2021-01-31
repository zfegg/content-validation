<?php declare(strict_types = 1);

namespace Zfegg\ContentValidation;

use Laminas\InputFilter\InputFilterPluginManager;
use Laminas\ServiceManager\ServiceManager;

trait ContentValidationTrait
{

    /** @var InputFilterPluginManager|null */
    protected $inputFilterManager;

    /**
     */
    public function getInputFilterManager(): InputFilterPluginManager
    {
        if (null === $this->inputFilterManager) {
            $this->inputFilterManager = new InputFilterPluginManager(new ServiceManager());
        }

        return $this->inputFilterManager;
    }
}
