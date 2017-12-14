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

use IndraGunawan\RestService\Exception\ValidatorException;
use PHPUnit\Framework\TestCase;

class ValidatorExceptionTest extends TestCase
{
    public function testValidatorException()
    {
        $validatorException = new ValidatorException('Foo', 'This field is required.');
        $this->assertSame('Foo', $validatorException->getField());
        $this->assertSame('This field is required.', $validatorException->getErrorMessage());
    }
}
