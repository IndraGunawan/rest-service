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

use IndraGunawan\RestService\Exception\BadResponseException;
use IndraGunawan\RestService\ServiceClient;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ServiceClientTest extends TestCase
{
    public function testAction()
    {
        $serviceClient = new ServiceClient(__DIR__.'/fixtures/api-specification.php');

        $this->assertInstanceOf(\GuzzleHttp\Psr7\Stream::class, $serviceClient->getStream()->getBody());
        $this->assertInstanceOf(\IndraGunawan\RestService\Result::class, $serviceClient->getJson());
    }

    public function testErrorAction()
    {
        $this->expectException(BadResponseException::class);

        $serviceClient = new ServiceClient(__DIR__.'/fixtures/api-specification.php');
        $stream = $serviceClient->getStreamError();
    }
}
