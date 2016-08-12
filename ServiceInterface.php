<?php

namespace IndraGunawan\RestService;

interface ServiceInterface
{
    /**
     * Get name of the service.
     *
     * @return string
     */
    public function getName();

    /**
     * Get endpoint of the service.
     *
     * @return string
     */
    public function getEndpoint();

    /**
     * Get all operations of the service.
     *
     * @return array
     */
    public function getOperations();

    /**
     * Check if the service has an operation by name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasOperation($name);

    /**
     * Get an operation of the service by name.
     *
     * @param string $name
     *
     * @return null|array
     */
    public function getOperation($name);

    /**
     * Get all shapes of the service.
     *
     * @return array
     */
    public function getShapes();

    /**
     * Check if the service has an shape by name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasShape($name);

    /**
     * Get a shape of the service by name.
     *
     * @param string $name
     *
     * @return null|array
     */
    public function getShape($name);

    /**
     * Get all errorShapes of the service.
     *
     * @return array
     */
    public function getErrorShapes();

    /**
     * Check if the service has an errorShape by name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasErrorShape($name);

    /**
     * Get an errorShape of the service by name.
     *
     * @param string $name
     *
     * @return null|array
     */
    public function getErrorShape($name);
}
