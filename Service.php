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

use IndraGunawan\RestService\Parser\SpecificationParser;

class Service implements ServiceInterface
{
    /**
     * @var array
     */
    private $service;

    /**
     * @param string $specificationFile
     * @param array  $defaults
     * @param string $cacheDir
     * @param bool   $debug
     *
     * @throws \IndraGunawan\RestService\Exception\InvalidSpecificationException
     */
    public function __construct($specificationFile, array $defaults = [], $cacheDir = null, $debug = false)
    {
        $parser = new SpecificationParser();
        $this->service = $parser->parse($specificationFile, $defaults ?: [], $cacheDir, $debug);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->service['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint()
    {
        return $this->service['endpoint'];
    }

    /**
     * {@inheritdoc}
     */
    public function getOperations()
    {
        return $this->service['operations'];
    }

    /**
     * {@inheritdoc}
     */
    public function hasOperation($name)
    {
        return array_key_exists($name, $this->getOperations());
    }

    /**
     * {@inheritdoc}
     */
    public function getOperation($name)
    {
        if ($this->hasOperation($name)) {
            return $this->getOperations()[$name];
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getShapes()
    {
        return $this->service['shapes'];
    }

    /**
     * {@inheritdoc}
     */
    public function hasShape($name)
    {
        return array_key_exists($name, $this->getShapes());
    }

    /**
     * {@inheritdoc}
     */
    public function getShape($name)
    {
        if ($this->hasShape($name)) {
            return $this->getShapes()[$name];
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorShapes()
    {
        return $this->service['errorShapes'];
    }

    /**
     * {@inheritdoc}
     */
    public function hasErrorShape($name)
    {
        return array_key_exists($name, $this->getErrorShapes());
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorShape($name)
    {
        if ($this->hasErrorShape($name)) {
            return $this->getErrorShapes()[$name];
        }

        return;
    }
}
