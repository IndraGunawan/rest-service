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

use IndraGunawan\RestService\Exception\BadRequestException;
use PHPUnit\Framework\TestCase;

class BadRequestExceptionTest extends TestCase
{
    public function testBadRequestException()
    {
        $badRequestException = new BadRequestException('Foo', 'This field is required.');
        $this->assertSame('Foo', $badRequestException->getRequestCode());
        $this->assertSame('This field is required.', $badRequestException->getRequestMessage());
        $this->assertSame('Foo: This field is required.', (string) $badRequestException);
    }
}
