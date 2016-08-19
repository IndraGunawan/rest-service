<?php

namespace IndraGunawan\RestService\Tests;

use IndraGunawan\RestService\Service;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ServiceTest extends \PHPUnit_Framework_TestCase
{
    private $service;

    public function setUp()
    {
        $serviceSpecification = [
            'name' => 'Foo',
            'endpoint' => 'http://httpbin.org/',
            'operations' => [
                'testGet' => [],
                'testPost' => [],
            ],
            'shapes' => [
                'TestGetShape' => [],
            ],
            'errorShapes' => [
                'NotFoundError' => [],
            ],
        ];

        $serviceMock = \Mockery::mock('overload:IndraGunawan\RestService\Parser\SpecificationParser');
        $serviceMock->shouldReceive('parse')->andReturn($serviceSpecification);

        $this->service = new Service(__DIR__);
    }

    // public function tearDown()
    // {
    //     \Mockery::close();
    // }

    public function testServiceName()
    {
        $this->assertEquals('Foo', $this->service->getName());
    }

    public function testServiceEndpoint()
    {
        $this->assertEquals('http://httpbin.org/', $this->service->getEndpoint());
    }

    public function testServiceOperation()
    {
        $this->assertInternalType('array', $this->service->getOperations());
        $this->assertEquals(2, count($this->service->getOperations()));
        $this->assertTrue($this->service->hasOperation('testGet'));
        $this->assertInternalType('array', $this->service->getOperation('testGet'));
        $this->assertNull($this->service->getOperation('testGet1'));
    }

    public function testServiceShape()
    {
        $this->assertInternalType('array', $this->service->getShapes());
        $this->assertTrue($this->service->hasShape('TestGetShape'));
        $this->assertInternalType('array', $this->service->getShape('TestGetShape'));
        $this->assertNull($this->service->getShape('TestGetShape1'));
    }

    public function testServiceErrorShape()
    {
        $this->assertInternalType('array', $this->service->getErrorShapes());
        $this->assertTrue($this->service->hasErrorShape('NotFoundError'));
        $this->assertInternalType('array', $this->service->getErrorShape('NotFoundError'));
        $this->assertNull($this->service->getErrorShape('NotFoundError1'));
    }
}
