<?php

namespace IndraGunawan\RestService\Tests;

use IndraGunawan\RestService\Result;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testNoHeader()
    {
        $result = new Result();
        $this->assertEquals([], $result->getHeaders());
        $this->assertFalse($result->hasHeader('baz'));
        $this->assertNull($result->getHeader('baz'));
    }

    public function testWithHeader()
    {
        $header = [
            'first' => 'foo',
            'second' => 'bar',
        ];

        $result = new Result([], $header);
        $this->assertEquals($header, $result->getHeaders());
        $this->assertFalse($result->hasHeader('baz'));
        $this->assertTrue($result->hasHeader('first'));
        $this->assertNull($result->getHeader('baz'));
        $this->assertEquals('bar', $result->getHeader('second'));
    }
}
