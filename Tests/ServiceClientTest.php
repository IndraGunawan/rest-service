<?php

namespace IndraGunawan\RestService\Tests;

use IndraGunawan\RestService\ServiceClient;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ServiceClientTest extends \PHPUnit_Framework_TestCase
{
    public function testAction()
    {
        $serviceClient = new ServiceClient(__DIR__.'/fixtures/api-specification.php');

        $this->assertInstanceOf(\GuzzleHttp\Psr7\Stream::class, $serviceClient->getStream()->getBody());
        $this->assertInstanceOf(\IndraGunawan\RestService\Result::class, $serviceClient->getJson());
    }

    /**
     * @expectedException \IndraGunawan\RestService\Exception\BadResponseException
     */
    public function testErrorAction()
    {
        $serviceClient = new ServiceClient(__DIR__.'/fixtures/api-specification.php');
        $stream = $serviceClient->getStreamError();
    }
}
