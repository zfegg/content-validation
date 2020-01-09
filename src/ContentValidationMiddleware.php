<?php

namespace Zfegg\ContentValidation;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\InputFilter\InputFilterPluginManager;

/**
 * Class ContentValidation
 *
 * @package Zfegg\Psr7Middleware
 */
class ContentValidationMiddleware implements MiddlewareInterface
{
    use ContentValidationTrait;

    const INPUT_FILTER_NAME = 'Zfegg\ContentValidation\InputFilter';
    const INPUT_FILTER = 'input_filter';

    protected $inputFilter;

    protected $responseFactory;

    protected $overwriteParsedBody = false;

    /**
     * @return InputFilterInterface
     */
    public function getInputFilter()
    {
        return $this->inputFilter;
    }

    /**
     * @param InputFilterInterface $inputFilter
     *
     * @return $this
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        $this->inputFilter = $inputFilter;

        return $this;
    }

    public function __construct(
        InputFilterPluginManager $inputFilters = null,
        ?callable $invalidHandler = null,
        ?callable $responseFactory = null,
        bool $overwriteParsedBody = false
    ) {
        if ($inputFilters) {
            $this->setInputFilterManager($inputFilters);
        }

        $this->setInvalidHandler(
            $invalidHandler ?: $this->getDefaultInvalidHandler()
        );

        if ($responseFactory) {
            $this->setResponseFactory($responseFactory);
        }

        $this->overwriteParsedBody = $overwriteParsedBody;
    }

    /**
     * @param callable $responseFactory
     */
    public function setResponseFactory(callable $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $inputFilterName = $request->getAttribute(self::INPUT_FILTER_NAME);

        if (! $inputFilterName) {
            return $handler->handle($request);
        }

        $inputFilters = $this->getInputFilterManager();
        if (! $inputFilters->has($inputFilterName)) {
            return $handler->handle($request);
        }

        $inputFilter = $inputFilters->get($inputFilterName);

        $this->setInputFilter($inputFilter);
        $request = $request->withAttribute(self::INPUT_FILTER, $inputFilter);

        if ($request->getMethod() == 'GET') {
            $data = $request->getQueryParams();
        } elseif (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $data = $request->getParsedBody();
        } else {
            return $handler->handle($request);
        }

        /** @var UploadedFileInterface[] $files */
        $files = $request->getUploadedFiles();
        if (0 < count($files)) {
            // File uploads are not validated for collections; impossible to
            // match file fields to discrete sets
            $files = self::psr2ArrayFiles($files);
            $data = array_merge($data, $files);
        }

        $inputFilter->setData((array)$data);

        if (! $inputFilter->isValid()) {
            $invalidHandler = $this->getInvalidHandler();

            return $invalidHandler(
                $this,
                $request,
                $handler,
                ($this->responseFactory)()
            );
        }

        if ($this->overwriteParsedBody) {
            $request = $request->withParsedBody($inputFilter->getValues());
        }

        return $handler->handle($request);
    }


    /**
     * PSR Request UploadedFileInterface to `$_FILES` format
     *
     * @param array|UploadedFileInterface[] $psrFiles
     *
     * @return array
     */
    public static function psr2ArrayFiles(array $psrFiles)
    {
        $files = [];

        foreach ($psrFiles as $name => $file) {
            if ($file instanceof UploadedFileInterface) {
                $files[$name] = [
                    'name'     => $file->getClientFilename(),
                    'type'     => $file->getClientMediaType(),
                    'tmp_name' => $file->getStream()->getMetadata('uri'),
                    'error'    => $file->getError(),
                    'size'     => $file->getSize(),
                ];
            } elseif (is_array($file)) {
                $files[$name] = self::psr2ArrayFiles($file);
            }
        }

        return $files;
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
     *
     * @return $this
     */
    public function setInvalidHandler(callable $invalidHandler)
    {
        $this->invalidHandler = $invalidHandler;

        return $this;
    }

    public function getDefaultInvalidHandler()
    {
        return function (
            $self,
            $request,
            RequestHandlerInterface $handler,
            ResponseInterface $response = null
        ) {
            if (! $response) {
                throw new Exception\InvalidRequestException(
                    'Failed Validation.',
                    422,
                    $this->getInputFilter()
                );
            }

            $response = $response->withStatus(422);
            $response = $response->withHeader(
                'Content-Type',
                'application/json'
            );
            $response->getBody()->write(
                json_encode(
                    [
                        'status'              => 422,
                        'detail'              => 'Failed Validation',
                        'validation_messages' => $this->getInputFilter()
                            ->getMessages(),
                    ]
                )
            );

            return $response;
        };
    }
}
