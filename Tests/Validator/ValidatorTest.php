<?php declare(strict_types=1);

/*
 * This file is part of indragunawan/rest-service package.
 *
 * (c) Indra Gunawan <hello@indra.my.id>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IndraGunawan\RestService\Tests\Validator;

use IndraGunawan\RestService\Exception\ValidatorException;
use IndraGunawan\RestService\Validator\Validator;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ValidatorTest extends TestCase
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

        $this->assertSame(
            [
                'RestService[email]' => 'required | email',
                'RestService[website]' => 'url',
            ],
            $validator->getRules()
        );

        $this->assertSame(
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

        $this->assertSame(
            [
                'RestService[email]' => 'required | email',
                'RestService[website]' => 'url',
            ],
            $validator->getRules()
        );

        $this->assertSame(
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
