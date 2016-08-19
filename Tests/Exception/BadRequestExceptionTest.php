<?php

namespace IndraGunawan\RestService\Tests;

use IndraGunawan\RestService\Exception\BadRequestException;

class BadRequestExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testBadRequestException()
    {
        $badRequestException = new BadRequestException('Foo', 'This field is required.');
        $this->assertEquals('Foo', $badRequestException->getRequestCode());
        $this->assertEquals('This field is required.', $badRequestException->getRequestMessage());
        $this->assertEquals('Foo: This field is required.', (string) $badRequestException);
    }
}
