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
use PHPUnit\Framework\TestCase;

class BadResponseExceptionTest extends TestCase
{
    public function testValidatorException()
    {
        $request = $this->getMockBuilder(\GuzzleHttp\Psr7\Request::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $badResponseException = new BadResponseException(400, 'Bad Request.', '400 Bad Request.', $request);
        $this->assertSame(400, $badResponseException->getStatusCode());
        $this->assertSame('Bad Request.', $badResponseException->getStatusMessage());
        $this->assertSame('400: Bad Request.', (string) $badResponseException);
    }
}
