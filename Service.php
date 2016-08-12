<?php

namespace IndraGunawan\RestService;

use IndraGunawan\RestService\Parser\SpecificationParser;

class Service implements ServiceInterface
{
    /**
     * @var ServiceInterface
     */
    private $service;

    /**
     * @param string  $specificationFile
     * @param array   $defaults
     * @param string  $cacheDir
     * @param boolean $debug
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
