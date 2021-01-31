<?php declare(strict_types = 1);

namespace Zfegg\ContentValidation;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\InputFilter\InputFilterPluginManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface;

/**
 * Class ContentValidation
 * @package Zfegg\MvcListener
 */
class ContentValidationListener extends AbstractListenerAggregate
{
    use ContentValidationTrait;
    const EVENT_VALIDATE_PREPARE = 'content-validation.prepare';
    const EVENT_VALIDATE_INVALID = 'content-validation.invalid';
    const INPUT_FILTER_NAME = 'Zfegg\ContentValidation\InputFilter';

    public function __construct(?InputFilterPluginManager $inputFilterManager = null)
    {
        $this->inputFilterManager = $inputFilterManager;
    }

    /**
     * {@inheritdoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1000)
    {
        $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'validation'], $priority);
        $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'validation'], $priority);
        $events->attach(self::EVENT_VALIDATE_INVALID, [$this, 'onInvalid'], -$priority);
    }

    public function onInvalid(MvcEvent $e): ResponseInterface
    {
        /** @var \Laminas\Http\PhpEnvironment\Response $response */
        $response = $e->getResponse();

        $response->setStatusCode(422);
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'status' => 422,
            'detail' => 'Failed Validation',
            'validation_messages' => $e->getParam(self::INPUT_FILTER_NAME)->getMessages()
        ]));

        return $response;
    }

    /**
     * @return ResponseInterface|void
     */
    public function validation(MvcEvent $e)
    {
        /** @var \Laminas\Http\PhpEnvironment\Request $request */
        $request = $e->getRequest();
        $rm = $e->getRouteMatch();
        $controllerName = $rm->getParam('controller');
        $action = $rm->getParam('action', $request->getMethod());

        $inputFilterName = $controllerName . '::' . $action;

        $inputFilters = $this->getInputFilterManager();
        if (! $inputFilters->has($inputFilterName)) {
            return ;
        }

        $inputFilter = $inputFilters->get($inputFilterName);

        $e->setParam(self::INPUT_FILTER_NAME, $inputFilter);

        $data = [];

        if ($request->isGet()) {
            $data = $request->getQuery();
        } elseif ($request->isPost() || $request->isPut() || $request->isPatch()) {
            $data = $request->getPost();
        }

        $e->setParam('input_filter_data', $data);
        ;

        $e->setName(self::EVENT_VALIDATE_PREPARE);
        $events = $e->getApplication()->getEventManager();
        $results = $events->triggerEventUntil(function ($result) {
            return is_array($result) || $result instanceof ResponseInterface;
        }, $e);

        $last = $results->last();
        if (is_array($last)) {
            $data = $last;
            $e->setParam('input_filter_data', $data);
        } elseif ($last instanceof ResponseInterface) {
            return $last;
        }

        $inputFilter->setData($e->getParam('input_filter_data'));

        if (! $inputFilter->isValid()) {
            $e->setName(self::EVENT_VALIDATE_INVALID);
            $results = $events->triggerEventUntil(function ($result) {
                return $result instanceof ResponseInterface;
            }, $e);

            if (($last = $results->last()) && $last instanceof ResponseInterface) {
                return $last;
            }
        }
    }
}
