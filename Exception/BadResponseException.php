<?php

namespace IndraGunawan\RestService\Exception;

use GuzzleHttp\Exception\BadResponseException as BaseBadResponseException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BadResponseException extends BaseBadResponseException
{
    /**
     * @var mixed
     */
    private $responseCode;

    /**
     * @var string
     */
    private $responseMessage;

    /**
     * @param mixed                  $responseCode
     * @param string                 $responseMessage
     * @param string                 $message
     * @param RequestInterface       $request
     * @param ResponseInterface|null $response
     * @param \Exception|null        $previous
     */
    public function __construct(
        $responseCode,
        $responseMessage,
        $message,
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $previous = null
    ) {
        $this->responseCode = $responseCode;
        $this->responseMessage = $responseMessage;

        parent::__construct($message, $request, $response, $previous);
    }

    public function getStatusCode()
    {
        return $this->responseCode;
    }

    public function getStatusMessage()
    {
        return $this->responseMessage;
    }
}
