<?php

namespace IndraGunawan\RestService\Tests;

use IndraGunawan\RestService\Exception\ValidatorException;

class ValidatorExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testValidatorException()
    {
        $validatorException = new ValidatorException('Foo', 'This field is required.');
        $this->assertEquals('Foo', $validatorException->getField());
        $this->assertEquals('This field is required.', $validatorException->getErrorMessage());
    }
}
