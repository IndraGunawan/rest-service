<?php

namespace IndraGunawan\RestService;

use GuzzleHttp\Command\Result as BaseResult;

class Result extends BaseResult
{
    /**
     * @var array
     */
    private $headers;

    /**
     * @param array $data
     * @param array $headers
     */
    public function __construct(array $data = [], array $headers = [])
    {
        $this->headers = $headers;
        parent::__construct($data);
    }

    /**
     * Get all headers of the result.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Check if the result has a header by name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader($name)
    {
        return array_key_exists($name, $this->getHeaders());
    }

    /**
     * Get an header of the result by name.
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function getHeader($name)
    {
        if ($this->hasHeader($name)) {
            return $this->getHeaders()[$name];
        }

        return;
    }
}
