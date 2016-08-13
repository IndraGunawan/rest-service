<?php

namespace IndraGunawan\RestService\Validator;

use IndraGunawan\RestService\Exception\ValidatorException;
use Sirius\Validation\Validator as SiriusValidator;

class Validator
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var array
     */
    private $datas;

    /**
     * @var array
     */
    private $rules;

    public function __construct()
    {
        $this->validator = new SiriusValidator();
        $this->rules = [];
        $this->datas = [];
    }

    /**
     * Add validator rules.
     *
     * @param string $name
     * @param array  $detail
     * @param string $value
     */
    public function add($name, array $detail, $value = '')
    {
        if (!$value) {
            $value = $detail['defaultValue'];
        }
        $this->datas[$name] = $value;

        if (isset($detail['rule']) && $detail['rule']) {
            $this->rules[$name] = $detail['rule'];
        }
    }

    /**
     * Check is data valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->validate($this->datas);
    }

    /**
     * Check is input datas valid.
     *
     * @return bool
     */
    public function validate(array $datas)
    {
        foreach ($this->rules as $field => $rule) {
            $this->validator->add($field, $rule);
        }

        $this->datas = $datas;

        return $this->validator->validate($datas);
    }

    /**
     * Get datas.
     *
     * @return array
     */
    public function getDatas()
    {
        return $this->datas;
    }

    /**
     * Get rules.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Get all error messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->validator->getMessages();
    }

    /**
     * Get first error message.
     *
     * @return array
     */
    public function getFirstMessage()
    {
        $messages = $this->getMessages();
        reset($messages);
        $field = key($messages);

        return [
            'field' => $field,
            'message' => (string) $messages[$field][0],
        ];
    }

    /**
     * Create ValidatorException.
     *
     * @return ValidationException
     */
    public function createValidatorException()
    {
        $message = $this->getFirstMessage();

        return new ValidatorException($message['field'], $message['message']);
    }
}
