<?php

namespace ZfeggTest\ContentValidation;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\UploadedFile;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\InputFilter\InputFilterPluginManagerFactory;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;
use Zfegg\ContentValidation\ContentValidationMiddleware;
use Zfegg\ContentValidation\ContentValidationMiddlewareFactory;

class ContentValidationMiddlewareTest extends TestCase
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
                ContentValidationMiddleware::class => ContentValidationMiddlewareFactory::class,
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
     * @param ServerRequest $request
     * @param null          $responseBody
     */
    public function testInvoke(
        $inputFilterName,
        ServerRequest $request,
        $responseBody = null
    ) {

        $response = new Response();

        $middleware = $this->container->get(ContentValidationMiddleware::class);

        $request = $request->withAttribute(
            ContentValidationMiddleware::INPUT_FILTER_NAME,
            $inputFilterName
        );

        $response = $middleware(
            $request,
            $response,
            function (
                ServerRequestInterface $request,
                Response $response
            ) use (
                $middleware
            ) {
                $inputFilter = $request->getAttribute(
                    ContentValidationMiddleware::INPUT_FILTER
                );
                $this->assertEquals(
                    $middleware->getInputFilter(),
                    $inputFilter
                );
                $this->assertInstanceOf(
                    ServerRequestInterface::class,
                    $request
                );
                $this->assertInstanceOf(ResponseInterface::class, $response);

                $response->getBody()->write('success');

                return $response;
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
                $this->assertArraySubset(
                    $responseBody,
                    json_decode((string)$response->getBody(), true)
                );
            }
        }
    }

}
