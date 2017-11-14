<?php
namespace ZfeggTest\ContentValidation;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\EventManager\EventManager;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\InputFilter\InputFilterPluginManagerFactory;
use Zend\Mvc\ApplicationInterface;
use Zend\Mvc\MvcEvent;
use Zend\Router\Http\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\Parameters;
use Zfegg\ContentValidation\ContentValidationListener;
use Zfegg\ContentValidation\ContentValidationListenerFactory;

class ContentValidationListenerTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;


    public function setUp()
    {
        $sl = new ServiceManager();
        $sl->configure([
            'factories' => [
                InputFilterPluginManager::class => InputFilterPluginManagerFactory::class,
                ContentValidationListener::class => ContentValidationListenerFactory::class,
            ],
            'aliases' => [
                'InputFilterManager' => InputFilterPluginManager::class
            ]
        ]);
        $inputFilterPluginManager = $sl->get(InputFilterPluginManager::class);
        $inputFilterPluginManager->configure(
            [
                'factories' => [
                    'test'      => function () {
                        return (new Factory())->createInputFilter(
                            [
                                [
                                    'name'       => 'age',
                                    'filters'    => [
                                        ['name' => 'ToInt'],
                                    ],
                                    'validators' => [
                                        [
                                            'name'    => 'LessThan',
                                            'options' => ['max' => 100],
                                        ],
                                    ],
                                ],
                            ]
                        );
                    },
                    'post.file' => function () {
                        return (new Factory())->createInputFilter(
                            [
                                [
                                    'name'       => 'file',
                                    'validators' => [
                                        [
                                            'name'    => 'FileExtension',
                                            'options' => ['extension' => 'jpg'],
                                        ],
                                    ],
                                ],
                            ]
                        );
                    },
                ],
                'aliases'   => [
                    'test::test' => 'test',
                ],
            ]
        );

        $this->container = $sl;
    }

    public function invokeProvider()
    {
        return [
            'NotFoundInputFilterWithInputFilters' => [
                'not_found',
                new Request()
            ],
            'HttpGetValid' => [
                'test',
                (new Request)->setQuery(new Parameters(['age' => 11])),
            ],
            'HttpGetInvalid' => [
                'test',
                (new Request)->setQuery(new Parameters(['age' => 101])),
                ['status' => 422, 'detail' => 'Failed Validation', 'validation_messages' => []]
            ],
            'HttpPostValid' => [
                'test',
                (new Request)->setMethod('POST')->setPost(new Parameters(['age' => 11])),
            ],
            'HttpPostInvalid' => [
                'test',
                (new Request)->setMethod('POST')->setPost(new Parameters(['age' => 101])),
                ['status' => 422, 'detail' => 'Failed Validation', 'validation_messages' => []]
            ],
            'DataWithValidatePrepare' => [
                'test',
                (new Request)->setMethod('POST')->setPost(new Parameters(['age' => 101])),
                null,
                ['age' => 11]
            ],
            'ResponseWithValidatePrepare' => [
                'test',
                (new Request)->setMethod('POST')->setPost(new Parameters(['age' => 101])),
                'testtest',
                (new Response())->setContent('testtest'),
            ],
        ];
    }

    /**
     *
     * @dataProvider invokeProvider
     */
    public function testInvoke($action, $request, $responseBody = null, $prepareReturnData = null)
    {

        $events = new EventManager();
        $mockApplication = $this->createMock(ApplicationInterface::class);
        $mockApplication->method('getEventManager')->willReturn($events);

        $routeMatch = new RouteMatch(['controller' => 'test', 'action' => $action]);
        $event = new MvcEvent();
        $event->setParam('test_name', $this->getName());
        $event->setRequest($request);
        $event->setResponse($prepareReturnData instanceof Response ? $prepareReturnData : new Response());
        $event->setRouteMatch($routeMatch);
        $event->setApplication($mockApplication);

        /** @var ContentValidationListener $listener */
        $listener = $this->container->get(ContentValidationListener::class);
        $listener->attach($events);

        $this->attachPreValidation($events, $prepareReturnData);

        $event->setName(MvcEvent::EVENT_DISPATCH);
        $events->triggerEvent($event);
        $response = $event->getResponse();

        if ($action == 'test') {
            $this->assertNotEmpty($event->getParam(ContentValidationListener::INPUT_FILTER_NAME));
        }

        if ($responseBody) {
            if (is_string($responseBody)) {
                $this->assertEquals($responseBody, (string)$response->getContent());
            } elseif (is_array($responseBody)) {
//                echo (string)$response->getBody(), "\n";
                $this->assertArraySubset($responseBody, json_decode((string)$response->getContent(), true));
            }
        } else {
            $this->assertEquals(200, $response->getStatusCode(), 'Body' . $response->getContent());
        }
    }

    private function attachPreValidation(EventManager $events, $data)
    {
        $events->attach(ContentValidationListener::EVENT_VALIDATE_PREPARE, function (MvcEvent $e) use ($data) {
            return $data;
        });
    }
}
