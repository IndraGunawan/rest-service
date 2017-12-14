<?php declare(strict_types=1);

/*
 * This file is part of indragunawan/rest-service package.
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IndraGunawan\RestService\Tests;

use IndraGunawan\RestService\Service;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ServiceTest extends TestCase
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
        $this->assertSame('Foo', $this->service->getName());
    }

    public function testServiceEndpoint()
    {
        $this->assertSame('http://httpbin.org/', $this->service->getEndpoint());
    }

    public function testServiceOperation()
    {
        $this->assertInternalType('array', $this->service->getOperations());
        $this->assertSame(2, count($this->service->getOperations()));
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
