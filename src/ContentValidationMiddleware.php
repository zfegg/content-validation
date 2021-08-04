<?php

declare(strict_types = 1);

namespace Zfegg\ContentValidation;

use Laminas\Diactoros\Response\JsonResponse;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\ValidationResult;
use Opis\JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ContentValidationMiddleware implements MiddlewareInterface
{
    const SCHEMA = 'schema';

    /** @var callable  */
    protected $invalidHandler;
    private Validator $validator;
    private bool $routeNameWithMethod;
    private bool $transformObjectToArray;

    public function __construct(
        Validator $validator,
        ?callable $invalidHandler = null,
        bool $routeNameWithMethod = true,
        bool $transformObjectToArray = true
    ) {
        $this->validator = $validator;
        $this->invalidHandler = $invalidHandler ?: \Closure::fromCallable([__CLASS__, 'defaultInvalidHandler']);
        $this->routeNameWithMethod = $routeNameWithMethod;
        $this->transformObjectToArray = $transformObjectToArray;
    }

    /**
     * @return string|object|null
     */
    private function getSchema(ServerRequestInterface $request)
    {
        $withMethod = $this->routeNameWithMethod ? ":{$request->getMethod()}" : '';
        if (($schema = $request->getAttribute(self::SCHEMA)) ||
            ($schema = $request->getAttribute(self::SCHEMA . $withMethod))
        ) {
            return $schema;
        }

        // Set Mezzio route name or slim route name
        if ($route = $request->getAttribute('Mezzio\Router\RouteResult')) {
            /** @var \Mezzio\Router\RouteResult $route */
            $options = $route->getMatchedRoute()->getOptions();
            return $options[self::SCHEMA . $withMethod] ?? null;
        } elseif ($route = $request->getAttribute('route')) {
            /** @var \Slim\Routing\Route $route */
            return $route->getArgument(
                self::SCHEMA . $withMethod,
                $route->getArgument(self::SCHEMA . $withMethod)
            );
        }

        return null;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $schema = $this->getSchema($request);

        if (! $schema) {
            return $handler->handle($request);
        }

        $fromParsedBody = false;
        if ($request->getMethod() == 'GET') {
            $data = $request->getQueryParams();
        } elseif (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $fromParsedBody = true;
            $data = $request->getParsedBody();
        } else {
            return $handler->handle($request);
        }

        $data = json_decode(json_encode($data));
        $result = $this->validator->validate($data, $schema);

        if (! $result->isValid()) {
            return ($this->invalidHandler)(
                $result,
                $request,
                $handler
            );
        }

        $data = $this->transformObjectToArray ? self::object2Array($data) : $data;
        if ($fromParsedBody) {
            $request = $request->withParsedBody($data);
        } else {
            $request = $request->withAttribute('query', $data);
        }

        return $handler->handle($request);
    }

    public static function defaultInvalidHandler(ValidationResult $result): ResponseInterface
    {
        return new JsonResponse([
            'status'              => 422,
            'detail'              => 'Failed Validation',
            'validation_messages' => (new ErrorFormatter())->format(
                $result->error(),
                true,
                null,
                function (ValidationError $error) {
                    $sub = '';
                    if ($error->keyword() == 'required') {
                        $sub = $error->args()['missing'][0];
                    }

                    return implode('/', $error->data()->fullPath()) . $sub;
                }
            ),
        ], 422);
    }

    private static function object2Array(object $data): array
    {
        $data = (array) $data;
        foreach ($data as $key => $value) {
            if (is_object($value)) {
                $data[$key] = self::object2Array($value);
            }
        }

        return $data;
    }
}
