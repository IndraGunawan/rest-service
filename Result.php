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
