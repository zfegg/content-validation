<?php
namespace ZfeggTest\ContentValidation;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\ServiceManager\ServiceManager;
use Zfegg\ContentValidation\ContentValidationMiddleware;

class ContentValidationMiddlewareTest extends \PHPUnit_Framework_TestCase
{

    public function invokeProvider()
    {
        return [
            'NotFoundInputFilterNameWithAttribute' => [
                '',
                new ServerRequest()
            ],
            'NotFoundInputFilterWithInputFilters' => [
                'not_found',
                new ServerRequest()
            ],
            'HttpGetValid' => [
                'test',
                new ServerRequest([], [], null, 'GET', 'php://input', [], [], ['age' => 11]),
                'success'
            ],
            'HttpGetInvalid' => [
                'test',
                new ServerRequest([], [], null, 'GET', 'php://input', [], [], ['age' => 101]),
                ['status' => 422, 'detail' => 'Failed Validation', 'validation_messages' => []]
            ],
            'HttpPostValid' => [
                'test',
                new ServerRequest([], [], null, 'POST', 'php://input', [], [], [], ['age' => 11]),
                'success'
            ],
            'HttpPostInvalid' => [
                'test',
                new ServerRequest([], [], null, 'POST', 'php://input', [], [], [], ['age' => 101]),
                ['status' => 422, 'detail' => 'Failed Validation', 'validation_messages' => []]
            ],
            'IgnoreWithCustomMethod' => [
                'test',
                new ServerRequest([], [], null, 'CUSTOM', 'php://input', [], [], [], ['age' => 101]),
                'success'
            ],
        ];
    }

    /**
     *
     * @dataProvider invokeProvider
     */
    public function testInvoke($inputFilterName, ServerRequest $request, $responseBody = null)
    {

        $response = new Response();
        $middleware = new ContentValidationMiddleware();

        $request = $request->withAttribute(ContentValidationMiddleware::INPUT_FILTER_NAME, $inputFilterName);

        $this->initInputFilters($middleware->getInputFilterManager());
        $response = $middleware($request, $response, function (ServerRequestInterface $request, Response $response)
 use ($middleware) {
            $inputFilter = $request->getAttribute(ContentValidationMiddleware::INPUT_FILTER);
            $this->assertEquals($middleware->getInputFilter(), $inputFilter);
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);

            $response->getBody()->write('success');
            return $response;
        });

        $this->assertInstanceOf(ResponseInterface::class, $response);

        if ($responseBody) {
            if (is_string($responseBody)) {
                $this->assertEquals($responseBody, (string)$response->getBody());
            } elseif (is_array($responseBody)) {
//                echo (string)$response->getBody(), "\n";
                $this->assertArraySubset($responseBody, json_decode((string)$response->getBody(), true));
            }
        }
    }

    public function testConstructSetInputFilters()
    {
        new ContentValidationMiddleware(new InputFilterPluginManager(new ServiceManager()));
    }

    public static function initInputFilters(InputFilterPluginManager $inputFilterPluginManager)
    {
        $inputFilterPluginManager->configure([
            'factories' => [
                'test' => function () {
                    return (new Factory())->createInputFilter([
                        [
                            'name' => 'age',
                            'filters' => [
                                ['name' => 'ToInt'],
                            ],
                            'validators' => [
                                ['name' => 'LessThan', 'options' => ['max' => 100]]
                            ]
                        ]
                    ]);
                }
            ],
            'aliases' => [
                'test::test' => 'test',
            ]
        ]);
    }
}
