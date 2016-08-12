<?php

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
     * @param mixed           $requestCode
     * @param string          $requestMessage
     * @param \Exception|null $prev
     */
    public function __construct($requestCode, $requestMessage, \Exception $prev = null)
    {
        $this->requestCode = $requestCode;
        $this->requestMessage = $requestMessage;

        parent::__construct($requestCode.': '.$requestMessage, 0, $prev);
    }

    public function getRequestCode()
    {
        return $this->requestCode;
    }

    public function getRequestMessage()
    {
        return $this->requestMessage;
    }

    public function __toString()
    {
        return $this->requestCode.': '.$this->requestMessage;
    }
}
