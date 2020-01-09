<?php
namespace Zfegg\ContentValidation;

use Laminas\InputFilter\InputFilterPluginManager;
use Laminas\ServiceManager\ServiceManager;

trait ContentValidationTrait
{

    protected $inputFilterManager;

    /**
     * @return InputFilterPluginManager
     */
    public function getInputFilterManager()
    {
        if (null === $this->inputFilterManager) {
            $this->inputFilterManager = new InputFilterPluginManager(new ServiceManager());
        }

        return $this->inputFilterManager;
    }

    /**
     * @param InputFilterPluginManager $inputFilterManager
     * @return $this
     */
    public function setInputFilterManager(InputFilterPluginManager $inputFilterManager)
    {
        $this->inputFilterManager = $inputFilterManager;
        return $this;
    }
}
