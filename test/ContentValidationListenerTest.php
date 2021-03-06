<?php declare(strict_types = 1);

namespace ZfeggTest\ContentValidation;

use Laminas\Stdlib\RequestInterface;
use PHPUnit\Framework\TestCase;
use Laminas\EventManager\EventManager;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\InputFilter\Factory;
use Laminas\InputFilter\InputFilterPluginManager;
use Laminas\InputFilter\InputFilterPluginManagerFactory;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Parameters;
use Zfegg\ContentValidation\ContentValidationListener;
use Zfegg\ContentValidation\ContentValidationListenerFactory;

class ContentValidationListenerTest extends TestCase
{
    /**
     * @var \Psr\Container\ContainerInterface
     */
    private $container;


    public function setUp(): void
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

    public function invokeProvider(): array
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
     * @param null|string|array $responseBody
     * @param array|Response|null $prepareReturnData
     */
    public function testInvoke(
        string $action,
        RequestInterface $request,
        $responseBody = null,
        $prepareReturnData = null
    ): void {

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
                $this->assertArrayHasKey('validation_messages', json_decode((string)$response->getContent(), true));
            }
        } else {
            $this->assertEquals(200, $response->getStatusCode(), 'Body' . $response->getContent());
        }
    }

    /**
     * @param array|Response|null $data
     */
    private function attachPreValidation(EventManager $events, $data): void
    {
        $events->attach(ContentValidationListener::EVENT_VALIDATE_PREPARE, function (MvcEvent $e) use ($data) {
            return $data;
        });
    }
}
