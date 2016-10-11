<?php

namespace Zfegg\Psr7Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\ServiceManager\ServiceManager;


/**
 * Class ContentValidation
 * @package Zfegg\Psr7Middleware
 */
class ContentValidation
{

    protected $inputFilterManager;

    protected $invalidHandler;

    public function __construct(array $options = [])
    {

    }

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

    /**
     *
     * @return callable
     */
    public function getInvalidHandler()
    {
        return $this->invalidHandler;
    }

    /**
     * @param callable $invalidHandler
     * @return $this
     */
    public function setInvalidHandler(callable $invalidHandler)
    {
        $this->invalidHandler = $invalidHandler;
        return $this;
    }


    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $request->getAttribute('validator_name');
    }
}
