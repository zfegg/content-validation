<?php

namespace Zfegg\ContentValidation;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterPluginManager;

/**
 * Class ContentValidation
 * @package Zfegg\Psr7Middleware
 */
class ContentValidationMiddleware
{
    const INPUT_FILTER_NAME = 'Zfegg\ContentValidation\InputFilter';
    use ContentValidationTrait;

    protected $inputFilter;

    /**
     * @return InputFilterInterface
     */
    public function getInputFilter()
    {
        return $this->inputFilter;
    }

    /**
     * @param InputFilterInterface $inputFilter
     * @return $this
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        $this->inputFilter = $inputFilter;
        return $this;
    }

    public function __construct(InputFilterPluginManager $inputFilters = null, callable $invalidHandler = null)
    {
        $defaultInvalidHandler = function ($self, $request, ResponseInterface $response, $next) {
            $response->withStatus(422);
            $response->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(json_encode([
                'status' => 422,
                'detail' => 'Failed Validation',
                'validation_messages' => $this->getInputFilter()->getMessages()
            ]));

            return $response;
        };

        $this->setInvalidHandler($invalidHandler ?: $defaultInvalidHandler);

        if ($inputFilters) {
            $this->setInputFilterManager($inputFilters);
        }
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $inputFilterName = $request->getAttribute(self::INPUT_FILTER_NAME);

        if (!$inputFilterName) {
            return $next($request, $response);
        }

        $inputFilters = $this->getInputFilterManager();
        if (!$inputFilters->has($inputFilterName)) {
            return $next($request, $response);
        }

        $inputFilter = $inputFilters->get($inputFilterName);

        $this->setInputFilter($inputFilter);

        if ($request->getMethod() == 'GET') {
            $data = $request->getQueryParams();
        } elseif (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $data = $request->getParsedBody();
        } else {
            return $next($request, $response);
        }

        $inputFilter->setData($data);

        if (!$inputFilter->isValid()) {
            $invalidHandler = $this->getInvalidHandler();
            return $invalidHandler($this, $request, $response, $next);
        }

        return $next($request, $response);
    }

    protected $invalidHandler;

    /**
     *
     * @return callable return ResponseInterface
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
}
