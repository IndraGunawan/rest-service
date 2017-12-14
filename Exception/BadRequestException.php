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

class BadRequestException extends \RuntimeException
{
    /**
     * @var mixed
     */
    private $requestCode;

    /**
     * @var string
     */
    private $requestMessage;

    /**
     * @var \Exception
     */
    private $prev;

    /**
     * @param mixed           $requestCode
     * @param string          $requestMessage
     * @param \Exception|null $prev
     */
    public function __construct($requestCode, $requestMessage, \Exception $prev = null)
    {
        $this->requestCode = $requestCode;
        $this->requestMessage = $requestMessage;
        $this->prev = $prev;

        parent::__construct($requestCode.': '.$requestMessage, 0, $prev);
    }

    /**
     * Get Request Code.
     *
     * @return mixed
     */
    public function getRequestCode()
    {
        return $this->requestCode;
    }

    /**
     * Get Request Message.
     *
     * @return string
     */
    public function getRequestMessage()
    {
        return $this->requestMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->requestCode.': '.$this->requestMessage;
    }

    /**
     * Get body content if exists.
     */
    public function getBodyContent()
    {
        if ($this->prev instanceof \GuzzleHttp\Exception\BadResponseException) {
            return $this->prev->getResponse()->getBody();
        }

        return;
    }
}
