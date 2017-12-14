<?php declare(strict_types=1);

/*
 * This file is part of indragunawan/rest-service package.
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /**
     * Get Status Code.
     *
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->responseCode;
    }

    /**
     * Get Status Message.
     *
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->responseMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->responseCode.': '.$this->responseMessage;
    }
}
