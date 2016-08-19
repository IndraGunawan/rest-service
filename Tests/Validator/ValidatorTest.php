<?php

namespace IndraGunawan\RestService\Tests\Validator;

use IndraGunawan\RestService\Exception\ValidatorException;
use IndraGunawan\RestService\Validator\Validator;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $siriusValidator = \Mockery::mock('overload:Sirius\Validation\Validator');
        $siriusValidator->shouldReceive('add')->andReturn(null);
        $siriusValidator->shouldReceive('validate')->andReturn(null);
        $siriusValidator->shouldReceive('getMessages')->andReturn(['foo' => ['This field is required.']]);
    }

    public function tearDown()
    {
        \Mockery::close();
    }

    public function testValidate()
    {
        $validator = new Validator();
        $validator->add('RestService[email]', ['rule' => 'required | email'], 'mail@example.com');
        $validator->add('RestService[website]', ['rule' => 'url', 'defaultValue' => 'http://example.com'], '');
        $validator->isValid();

        $this->assertEquals(
            [
                'RestService[email]' => 'required | email',
                'RestService[website]' => 'url',
            ],
            $validator->getRules()
        );

        $this->assertEquals(
            [
                'RestService[email]' => 'mail@example.com',
                'RestService[website]' => 'http://example.com',
            ],
            $validator->getDatas()
        );
    }

    public function testValidateWithInput()
    {
        $validator = new Validator();
        $validator->add('RestService[email]', ['rule' => 'required | email', 'defaultValue' => 'mail@example.com'], '');
        $validator->add('RestService[website]', ['rule' => 'url', 'defaultValue' => 'http://example.com'], '');
        $validator->validate([
            'RestService' => [
                'email' => 'newmail@example.com',
                'website' => '',
            ],
        ]);

        $this->assertEquals(
            [
                'RestService[email]' => 'required | email',
                'RestService[website]' => 'url',
            ],
            $validator->getRules()
        );

        $this->assertEquals(
            [
                'RestService' => [
                    'email' => 'newmail@example.com',
                    'website' => '',
                ],
            ],
            $validator->getDatas()
        );
    }

    public function testValidationMessages()
    {
        $validator = new Validator();
        $firstMessage = $validator->getFirstMessage();
        $validatorException = $validator->createValidatorException();

        $this->assertInternalType('array', $validator->getMessages());
        $this->assertInternalType('array', $firstMessage);
        $this->assertTrue(array_key_exists('field', $firstMessage));
        $this->assertTrue(array_key_exists('message', $firstMessage));
        $this->assertInstanceOf(ValidatorException::class, $validatorException);
    }
}
