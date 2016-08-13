<?php

namespace IndraGunawan\RestService\Tests\Validator;

use IndraGunawan\RestService\Validator\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
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
}
