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

use IndraGunawan\RestService\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testNoHeader()
    {
        $result = new Result();
        $this->assertSame([], $result->getHeaders());
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
        $this->assertSame($header, $result->getHeaders());
        $this->assertFalse($result->hasHeader('baz'));
        $this->assertTrue($result->hasHeader('first'));
        $this->assertNull($result->getHeader('baz'));
        $this->assertSame('bar', $result->getHeader('second'));
    }
}
