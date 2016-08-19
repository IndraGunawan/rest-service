<?php

namespace IndraGunawan\RestService\Tests;

use IndraGunawan\RestService\Exception\BadResponseException;

class BadResponseExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testValidatorException()
    {
        $request = $this->getMockBuilder(\GuzzleHttp\Psr7\Request::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $badResponseException = new BadResponseException(400, 'Bad Request.', '400 Bad Request.', $request);
        $this->assertEquals(400, $badResponseException->getStatusCode());
        $this->assertEquals('Bad Request.', $badResponseException->getStatusMessage());
        $this->assertEquals('400: Bad Request.', (string) $badResponseException);
    }
}
