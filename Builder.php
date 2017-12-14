<?php declare(strict_types=1);

/*
 * This file is part of indragunawan/rest-service package.
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IndraGunawan\RestService;

use GuzzleHttp;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Exception\BadResponseException as GuzzleBadResponseException;
use GuzzleHttp\Psr7\Request;
use IndraGunawan\RestService\Exception\BadRequestException;
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
     * @var ValueFormatter
     */
    private $formatter;

    /**
     * @param ServiceInterface $service
     */
    public function __construct(ServiceInterface $service)
    {
        $this->service = $service;
        $this->formatter = new ValueFormatter();
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

            // GET method has no body, concat to uri
            if (in_array($operation['httpMethod'], ['GET'], true)) {
                // if uri has no query string, append '?' else '&'
                $uri .= (false === strpos($uri, '?')) ? '?' : '&';

                $uri .= http_build_query($result['body'], '', '&', PHP_QUERY_RFC3986);
                $result['body'] = null;
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
            if ('rest_json' === $operation['responseProtocol']) {
                $body = GuzzleHttp\json_decode($response->getBody(), true);

                $result = $this->transformData($command, $body, $operation, 'response');

                foreach ($response->getHeaders() as $name => $header) {
                    $result['header'][$name] = is_array($header) ? array_pop($header) : null;
                }

                return new Result($result['body'], $result['header']);
            } elseif ('stream' === $operation['responseProtocol']) {
                $streamResponse = new StreamResult(
                    $response->getStatusCode(),
                    $response->getHeaders(),
                    $response->getBody(),
                    $response->getProtocolVersion(),
                    $response->getReasonPhrase()
                );

                return $streamResponse;
            }
        };
    }

    public function badResponseExceptionParser()
    {
        return function (CommandInterface $command, GuzzleBadResponseException $e) {
            $operation = $this->service->getOperation($command->getName());

            $this->processResponseError(
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
     * @throws \IndraGunawan\RestService\Exception\BadResponseException|null
     */
    private function processResponseError(
        array $operation,
        RequestInterface $request,
        ResponseInterface $response = null,
        GuzzleBadResponseException $e = null
    ) {
        $body = null;
        try {
            if ('rest_json' === $operation['responseProtocol']) {
                $body = GuzzleHttp\json_decode((!is_null($response)) ? $response->getBody() : '', true);
            } elseif ('stream' === $operation['responseProtocol']) {
                $body = $response->getBody();
            }
        } catch (\InvalidArgumentException $ex) {
            throw new BadResponseException(
                '',
                $ex->getMessage(),
                ($ex ? $ex->getMessage() : ''),
                $request,
                $response,
                $ex ? $ex->getPrevious() : null
            );
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

                        throw new BadResponseException(
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

                if ($this->checkResponseCode($responseCode, $error['operator'], $error['ifCode'])) {
                    $responseMessage = $this->getErrorMessage($body, $error, $response);

                    throw new BadResponseException(
                        $responseCode,
                        $responseMessage,
                        ($e ? $e->getMessage() : '').'. code: '.$responseCode.', message: '.$responseMessage,
                        $request,
                        $response,
                        $e ? $e->getPrevious() : null
                    );
                }
            }
        }
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
        if (!$message) {
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
            $result,
            ('request' === $action) ? $operation['strictRequest'] : $operation['strictResponse'],
            $operation['sentEmptyField']
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
     * @param bool      $isStrict
     * @param bool      $isSentEmptyField
     *
     * @return array
     */
    private function createData(
        Validator $validator,
        array &$datas,
        array $shape,
        $path,
        $action,
        array &$result,
        $isStrict = false,
        $isSentEmptyField = true
    ) {
        $tmpData = $datas;
        $bodyResult = [];
        if ('list' === $shape['type']) {
            $path .= '[*]';
        }

        foreach ($shape['members'] as $name => $parameter) {
            $tmpPath = $path.'['.$name.']';
            $value = isset($datas[$name]) ? $datas[$name] : null;
            if (!$value) {
                $datas[$name] = $parameter['defaultValue'];
            }

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

                        $bodyResult[$parameter['locationName']][] = $this->createData(
                            $validator,
                            $datas[$name][$idx],
                            $parameter,
                            $tmpPath,
                            $action,
                            $result,
                            $isStrict,
                            $isSentEmptyField
                        );
                    }
                } elseif ('map' === $parameter['type']) {
                    if (is_null($value)) {
                        if (array_key_exists($name, $datas)) {
                            unset($tmpData[$name]);
                        }
                        continue;
                    }
                    $children = $value ?: [];
                    foreach (array_keys($parameter['members']) as $key) {
                        if (!array_key_exists($key, $children)) {
                            $children[$key] = null;
                            $datas[$name][$key] = null;
                        }
                    }

                    $bodyResult[$parameter['locationName']] = $this->createData(
                        $validator,
                        $datas[$name],
                        $parameter,
                        $tmpPath,
                        $action,
                        $result,
                        $isStrict,
                        $isSentEmptyField
                    );
                }
            }

            $formattedValue = $this->formatter->format($parameter['type'], $parameter['format'], $value, $parameter['defaultValue']);
            if ('body' !== $parameter['location']) {
                $result[$parameter['location']][$parameter['locationName']] = $formattedValue;
            } else {
                if (!array_key_exists($parameter['locationName'], $bodyResult)) {
                    if (!$value && !is_numeric($value)) {
                        $value = $parameter['defaultValue'];
                    }
                    if ($isSentEmptyField || ($value || is_numeric($value))) {
                        $bodyResult[$parameter['locationName']] = $formattedValue;
                    }
                }
            }
            unset($tmpData[$name]);
        }

        if (count($tmpData) > 0) {
            if ($isStrict) {
                throw new BadRequestException(ucwords($action), 'Undefined parameters "'.implode('", "', array_keys($tmpData)).'"');
            }
            foreach ($tmpData as $name => $child) {
                $bodyResult[$name] = $child;
            }
        }

        return $bodyResult;
    }

    /**
     * Check is responsecode is match with code.
     *
     * @param string $responseCode
     * @param string $operator
     * @param string $code
     *
     * @return bool
     */
    public function checkResponseCode($responseCode, $operator, $code)
    {
        switch ($operator) {
            case '===':
                return $responseCode === $code;
            case '!==':
                return $responseCode !== $code;
            case '!=':
                return $responseCode !== $code;
            case '<':
                return $responseCode < $code;
            case '<=':
                return $responseCode <= $code;
            case '>=':
                return $responseCode >= $code;
            case '>':
                return $responseCode > $code;
            default:
                return $responseCode === $code;
        }
    }
}
