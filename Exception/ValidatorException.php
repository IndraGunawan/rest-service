<?php

namespace IndraGunawan\RestService\Exception;

class ValidatorException extends \RuntimeException
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @param string          $field
     * @param string          $errorMessage
     * @param \Exception|null $prev
     */
    public function __construct($field, $errorMessage, \Exception $prev = null)
    {
        $this->field = $field;
        $this->errorMessage = $errorMessage;

        parent::__construct($field.' : '.$errorMessage, 0, $prev);
    }

    public function getField()
    {
        return $this->field;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
