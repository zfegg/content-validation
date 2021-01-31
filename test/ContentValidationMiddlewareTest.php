<?php declare(strict_types = 1);

namespace ZfeggTest\ContentValidation;

use Laminas\InputFilter\InputFilterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\UploadedFile;
use Laminas\InputFilter\Factory;
use Laminas\InputFilter\InputFilterPluginManager;
use Laminas\InputFilter\InputFilterPluginManagerFactory;
use Laminas\ServiceManager\ServiceManager;
use Zfegg\ContentValidation\ContentValidationMiddleware;
use Zfegg\ContentValidation\ContentValidationMiddlewareFactory;

class ContentValidationMiddlewareTest extends TestCase
{
    /**
     * @var \Psr\Container\ContainerInterface
     */
    public $container;


    public function setUp(): void
    {
        $sl = new ServiceManager();
        $sl->configure([
            'factories' => [
                InputFilterPluginManager::class => InputFilterPluginManagerFactory::class,
                ContentValidationMiddleware::class => ContentValidationMiddlewareFactory::class,
                ResponseInterface::class => function () {
                    return function () {
                        return new Response();
                    };
                }
            ],
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

        $file = [
            'name'     => 'DM4C2738.jpg',
            'type'     => 'image/jpeg',
            'tmp_name' => tmpfile(),
            'error'    => 0,
            'size'     => 1031601,
        ];
        $uploaded = new UploadedFile(
            $file['tmp_name'],
            $file['size'],
            $file['error'],
            $file['name'],
            $file['type']
        );

        $postFileReq = new ServerRequest(
            [],
            ['file' => $uploaded,],
            null,
            'POST',
            'php://input',
            [],
            [],
            [],
            ['age' => 101]
        );

        return [
            'NotFoundInputFilterNameWithAttribute' => [
                '',
                new ServerRequest(),
            ],
            'NotFoundInputFilterWithInputFilters'  => [
                'not_found',
                new ServerRequest(),
            ],
            'HttpGetValid'                         => [
                'test',
                new ServerRequest(
                    [],
                    [],
                    null,
                    'GET',
                    'php://input',
                    [],
                    [],
                    ['age' => 11]
                ),
                'success',
            ],
            'HttpGetInvalid'                       => [
                'test',
                new ServerRequest(
                    [],
                    [],
                    null,
                    'GET',
                    'php://input',
                    [],
                    [],
                    ['age' => 101]
                ),
                [
                    'status'              => 422,
                    'detail'              => 'Failed Validation',
                    'validation_messages' => [],
                ],
            ],
            'HttpPostValid'                        => [
                'test',
                new ServerRequest(
                    [],
                    [],
                    null,
                    'POST',
                    'php://input',
                    [],
                    [],
                    [],
                    ['age' => 11]
                ),
                'success',
            ],
            'HttpPostInvalid'                      => [
                'test',
                new ServerRequest(
                    [],
                    [],
                    null,
                    'POST',
                    'php://input',
                    [],
                    [],
                    [],
                    ['age' => 101]
                ),
                [
                    'status'              => 422,
                    'detail'              => 'Failed Validation',
                    'validation_messages' => [],
                ],
            ],
            'IgnoreWithCustomMethod'               => [
                'test',
                new ServerRequest(
                    [],
                    [],
                    null,
                    'CUSTOM',
                    'php://input',
                    [],
                    [],
                    [],
                    ['age' => 101]
                ),
                'success',
            ],
            'PostFiles'                            => [
                'post.file',
                $postFileReq,
                'success',
            ],
        ];
    }

    /**
     *
     * @dataProvider invokeProvider
     *
     * @param               $inputFilterName
     * @param null          $responseBody
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function testInvoke(
        string $inputFilterName,
        ServerRequest $request,
        $responseBody = null
    ): void {

        $middleware = $this->container->get(ContentValidationMiddleware::class);
        $request = $request->withAttribute(
            ContentValidationMiddleware::INPUT_FILTER_NAME,
            $inputFilterName
        );

        $response = $middleware->process(
            $request,
            new class($this, $middleware) implements RequestHandlerInterface
            {
                /** @var MiddlewareInterface  */
                protected $middleware;

                /** @var self  */
                protected $parent;

                public function __construct(TestCase $self, MiddlewareInterface $middleware)
                {
                    $this->middleware = $middleware;
                    $this->parent = $self;
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {

                    $inputFilter = $request->getAttribute(ContentValidationMiddleware::INPUT_FILTER);
                    $inputFilterName = $request->getAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME);

                    if ($this->parent->container->get(InputFilterPluginManager::class)->has($inputFilterName)) {
                        $this->parent->assertInstanceOf(
                            InputFilterInterface::class,
                            $inputFilter
                        );
                    }

                    $this->parent->assertInstanceOf(
                        ServerRequestInterface::class,
                        $request
                    );
                    $response = new Response();
                    $response->getBody()->write('success');

                    return $response;
                }
            }
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);

        if ($responseBody) {
            if (is_string($responseBody)) {
                $this->assertEquals(
                    $responseBody,
                    (string)$response->getBody()
                );
            } elseif (is_array($responseBody)) {
//                echo (string)$response->getBody(), "\n";
                $this->assertArrayHasKey(
                    'validation_messages',
                    json_decode((string)$response->getBody(), true)
                );
            }
        }
    }
}
