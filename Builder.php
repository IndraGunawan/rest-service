<?php

namespace IndraGunawan\RestService;

use GuzzleHttp;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Exception\BadResponseException as GuzzleBadResponseException;
use GuzzleHttp\Psr7\Request;
use IndraGunawan\RestService\Exception\BadResponseException;
use IndraGunawan\RestService\Exception\CommandException;
use IndraGunawan\RestService\Exception\ValidatorException;
use IndraGunawan\RestService\Validator\Validator;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Builder
{
    /**
     * @var ServiceInterface
     */
    private $service;

    /**
     * @param ServiceInterface $service
     */
    public function __construct(ServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Transform command to request.
     *
     * @return \Closure
     */
    public function commandToRequestTransformer()
    {
        return function (CommandInterface $command) {
            if (!$this->service->hasOperation($command->getName())) {
                throw new CommandException(
                    sprintf(
                        'Command "%s" not found',
                        $command->getName()
                    ),
                    $command
                );
            }

            $operation = $this->service->getOperation($command->getName());

            $result = $this->transformData($command, $command->toArray(), $operation ?: [], 'request');

            $uri = ltrim($operation['requestUri'], '/');
            if ($result['uri']) {
                // replace uri
                $patterns = [];
                $replacements = [];
                foreach ($result['uri'] as $key => $value) {
                    $patterns[] = '/{'.$key.'}/';
                    $replacements[] = $value;
                }

                $uri = preg_replace($patterns, $replacements, $uri);
            }

            $body = null;
            if ('rest_json' === $operation['requestProtocol']) {
                $body = GuzzleHttp\json_encode($result['body']);
                $result['header']['Content-Type'] = 'application/json';
            } elseif ('form_params' === $operation['requestProtocol']) {
                $body = http_build_query($result['body'], '', '&');
                $result['header']['Content-Type'] = 'application/x-www-form-urlencoded';
            }

            if ($result['query']) {
                $uri .= sprintf('?%s', http_build_query($result['query'], null, '&', PHP_QUERY_RFC3986));
            }

            return new Request(
                $operation['httpMethod'],
                $this->service->getEndpoint().$uri,
                $result['header'],
                $body
            );
        };
    }

    /**
     * Transform response to result.
     *
     * @return \Closure
     */
    public function responseToResultTransformer()
    {
        return function (
            ResponseInterface $response,
            RequestInterface $request,
            CommandInterface $command
        ) {
            $operation = $this->service->getOperation($command->getName());
            $this->processResponseError($operation ?: [], $request, $response);

            $body = [];
            if ('rest_json' === $operation['responseProtocol']) {
                $body = GuzzleHttp\json_decode($response->getBody(), true);
            }

            $result = $this->transformData($command, $body, $operation, 'response');

            foreach ($response->getHeaders() as $name => $header) {
                $result['header'][$name] = is_array($header) ? array_pop($header) : null;
            }

            return new Result($result['body'], $result['header']);
        };
    }

    public function badResponseExceptionParser()
    {
        return function (CommandInterface $command, GuzzleBadResponseException $e) {
            $operation = $this->service->getOperation($command->getName());

            return $this->processResponseError(
                $operation ?: [],
                $e->getRequest(),
                $e->getResponse(),
                $e
            );
        };
    }

    /**
     * Process response to check is error or not.
     *
     * @param array                           $operation
     * @param RequestInterface                $request
     * @param ResponseInterface               $response
     * @param GuzzleBadResponseException|null $e
     *
     * @return BadResponseException|null
     */
    private function processResponseError(
        array $operation,
        RequestInterface $request,
        ResponseInterface $response = null,
        GuzzleBadResponseException $e = null
    ) {
        $body = null;
        if ('rest_json' === $operation['responseProtocol']) {
            try {
                $body = GuzzleHttp\json_decode((!is_null($response)) ? $response->getBody() : '', true);
            } catch (\InvalidArgumentException $ex) {
                return new BadResponseException(
                    '',
                    $ex->getMessage(),
                    ($e ? $e->getMessage() : ''),
                    $request,
                    $response,
                    $e ? $e->getPrevious() : null
                );
            }
        }

        if ($body) {
            foreach ($operation['errors'] as $name => $error) {
                if ('field' === $error['type']) {
                    $responseCode = $this->parseError($body, $error['codeField']);

                    if (!$responseCode) {
                        continue;
                    }

                    // if no ifCode property then return exception
                    if (!$error['ifCode']) {
                        $responseMessage = $this->getErrorMessage($body, $error, $response);

                        return new BadResponseException(
                            $responseCode,
                            $responseMessage,
                            ($e ? $e->getMessage() : '').' code: '.$responseCode.', message: '.$responseMessage,
                            $request,
                            $response,
                            $e ? $e->getPrevious() : null
                        );
                    }
                } else {
                    $responseCode = $response->getStatusCode();
                }

                if ($error['ifCode'] == $responseCode) {
                    $responseMessage = $this->getErrorMessage($body, $error, $response);

                    return new BadResponseException(
                        $responseCode,
                        $responseMessage,
                        ($e ? $e->getMessage() : '').' code: '.$responseCode.', message: '.$responseMessage,
                        $request,
                        $response,
                        $e ? $e->getPrevious() : null
                    );
                }
            }
        }

        return;
    }

    /**
     * Parse error codeField from body.
     *
     * @param array  $body
     * @param string $path
     *
     * @return string|null
     */
    private function parseError(array $body, $path)
    {
        $tmp = $body;
        foreach (explode('.', $path) as $key) {
            if (!isset($tmp[$key])) {
                return;
            }

            $tmp = $tmp[$key];
        }

        return $tmp;
    }

    /**
     * Get error message from posible field.
     *
     * @param array                  $body
     * @param array                  $error
     * @param ResponseInterface|null $response
     *
     * @return string
     */
    private function getErrorMessage(array $body, array $error, ResponseInterface $response = null)
    {
        $message = $this->parseError($body, $error['messageField']);
        if (!$message) {
            $message = $error['defaultMessage'];
        }
        if (!$message && $response->getStatusCode() >= 400) {
            $message = $response->getReasonPhrase();
        }

        return $message;
    }

    /**
     * Transform and match data with shape.
     *
     * @param CommandInterface $command
     * @param array            $datas
     * @param array            $operation
     * @param string           $action
     *
     * @return array
     */
    private function transformData(CommandInterface $command, array $datas, array $operation, $action)
    {
        $result = [
            'header' => [],
            'uri' => [],
            'query' => [],
            'body' => [],
        ];

        $validator = new Validator();

        if (isset($operation[$action])) {
            $shape = $operation[$action];
        } else {
            $shape = [
                'type' => null,
                'members' => [],
            ];
        }

        $result['body'] = $this->createData(
            $validator,
            $datas,
            $shape,
            $command->getName(),
            $action,
            $result
        );

        if (!$validator->validate([$command->getName() => $datas])) {
            // validation failed
            throw $validator->createValidatorException();
        }

        return $result;
    }

    /**
     * Create request/response data and add validation rule.
     *
     * @param Validator $validator
     * @param array     $datas
     * @param array     $shape
     * @param string    $path
     * @param string    $action
     * @param array     &$result
     *
     * @return array
     */
    private function createData(
        Validator $validator,
        array $datas,
        array $shape,
        $path,
        $action,
        array &$result
    ) {
        $bodyResult = [];
        if ('list' === $shape['type']) {
            $path .= '[*]';
        }

        foreach ($shape['members'] as $name => $parameter) {
            $tmpPath = $path.'['.$name.']';
            $value = isset($datas[$name]) ? $datas[$name] : null;
            // set validator
            $validator->add($tmpPath, $parameter, $value);

            // if nested children
            if (isset($parameter['members']) && count($parameter['members']) > 0) {
                if (!is_null($value) && !is_array($value)) {
                    throw new ValidatorException($tmpPath, sprintf(
                        'Expected "%s", but got "%s"',
                        $parameter['type'],
                        gettype($value)
                    ));
                }
                if ('list' === $parameter['type']) {
                    $children = $value ?: [];
                    foreach ($children as $idx => $child) {
                        if (!is_array($child)) {
                            throw new ValidatorException($tmpPath, 'Expected "list", but got "map"');
                        }

                        $bodyResult[$parameter['locationName']][] = $this->createData($validator, $child, $parameter, $tmpPath, $action, $result);
                    }
                } elseif ('map' === $parameter['type']) {
                    if (is_null($value)) {
                        continue;
                    }
                    $children = $value ?: [];
                    foreach (array_keys($parameter['members']) as $key) {
                        if (!array_key_exists($key, $children)) {
                            $children[$key] = null;
                        }
                    }

                    foreach ($children as $parameterName => $child) {
                        if (is_array($child)) {
                            $bodyResult[$parameter['locationName']][$parameterName] = $this->createData(
                                $validator,
                                $child,
                                $parameter,
                                $tmpPath,
                                $action,
                                $result
                            );
                        }
                    }
                    $bodyResult[$parameter['locationName']] = $this->createData(
                        $validator,
                        $children,
                        $parameter,
                        $tmpPath,
                        $action,
                        $result
                    );
                }
            }

            $value = $this->getFormatedValue($value, $parameter, $action);
            if ('body' !== $parameter['location']) {
                $result[$parameter['location']][$parameter['locationName']] = $value;
            } else {
                if (!array_key_exists($parameter['locationName'], $bodyResult)) {
                    $bodyResult[$parameter['locationName']] = $value;
                }
            }
            unset($datas[$name]);
        }

        if (count($datas) > 0) {
            foreach ($datas as $name => $child) {
                $bodyResult[$name] = $child;
            }
        }

        return $bodyResult;
    }

    /**
     * Get formatted value.
     *
     * @param mixed  $value     [description]
     * @param array  $parameter [description]
     * @param string $action    request/response
     *
     * @return mixed
     */
    private function getFormatedValue($value, array $parameter, $action)
    {
        if (!$value) {
            $value = $parameter['defaultValue'];
        }

        switch ($parameter['type']) {
            case 'integer':
                return (int) (string) $value;
            case 'float':
                return (float) (string) $value;
            case 'string':
                $result = (string) $value;

                return sprintf($parameter['format'] ?: '%s', $result);
            case 'boolean':
                return ($value === 'true' || true === $value) ? true : false;
            case 'number':
                if ($parameter['format']) {
                    $format = explode('|', $parameter['format']);
                    $decimal = isset($format[0]) ? $format[0] : 0;
                    $decimalPoint = isset($format[1]) ? $format[1] : '.';
                    $thousandsSeparator = isset($format[2]) ? $format[2] : ',';

                    return number_format((float) (string) $value, $decimal, $decimalPoint, $thousandsSeparator);
                }

                return (string) $value;
            case 'datetime':
                if ('request' === $action) {
                    if (!$value) {
                        return;
                    }

                    if (!($value instanceof \DateTime)) {
                        $value = new \DateTime($value);
                    }

                    if ($parameter['format']) {
                        return $value->format($parameter['format']);
                    }

                    return $value->format('Y-m-d\TH:i:s\Z');
                } elseif ('response' === $action) {
                    if ($parameter['format']) {
                        return \DateTime::createFromFormat($parameter['format'], $value);
                    } else {
                        return new \DateTime($value);
                    }
                }
                //
            default:
                return;
        }
    }
}
