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

use IndraGunawan\RestService\Validator\SpecificationConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SpecificationConfigurationTest extends TestCase
{
    public function testInvalidConfiguration()
    {
        $this->expectException(InvalidConfigurationException::class);

        $specificationArray = [];
        $processor = new Processor();
        $specification = $processor->processConfiguration(
            new SpecificationConfiguration(),
            $specificationArray
        );
    }

    public function testValidConfiguration()
    {
        $specificationArray = [
            'rest_service' => [
                'name' => 'Foo Rest Service',
                'endpoint' => 'http://httpbin.org',
                'operations' => [
                    'getFoo' => [
                        'httpMethod' => 'GET',
                        'requestUri' => '/get',
                    ],
                ],
            ],
        ];

        $processor = new Processor();
        $specification = $processor->processConfiguration(
            new SpecificationConfiguration(),
            $specificationArray
        );

        $this->assertSame('http://httpbin.org', $specification['endpoint']);
        $this->assertSame('Foo Rest Service', $specification['name']);
        $this->assertSame(1, count($specification['operations']));
    }

    public function testInvalidShape()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "shape"');

        $specificationArray = [
            'rest_service' => [
                'endpoint' => 'http://httpbin.org',
                'operations' => [
                    'getFoo' => [
                        'httpMethod' => 'GET',
                        'requestUri' => '/get',
                    ],
                ],
                'shapes' => [
                    'AnotherShape' => [
                    ],
                    'GetShape' => [
                        'members' => [
                            'id' => [
                                'location' => 'uri',
                            ],
                        ],
                        'shape' => 'AnotherShape',
                    ],
                ],
            ],
        ];
        $processor = new Processor();
        $specification = $processor->processConfiguration(
            new SpecificationConfiguration(),
            $specificationArray
        );
    }

    public function testValidShape()
    {
        $specificationArray = [
            'rest_service' => [
                'endpoint' => 'http://httpbin.org',
                'operations' => [
                    'getFoo' => [
                        'httpMethod' => 'GET',
                        'requestUri' => '/get',
                    ],
                ],
                'shapes' => [
                    'AnotherShape' => [
                        'members' => [
                            'id' => [
                                'location' => 'uri',
                            ],
                        ],
                    ],
                    'AnotherShape1' => [
                        'extends' => 'AnotherShape',
                    ],
                ],
            ],
        ];
        $processor = new Processor();
        $specification = $processor->processConfiguration(
            new SpecificationConfiguration(),
            $specificationArray
        );

        $this->assertSame(2, count($specification['shapes']));
    }

    public function testInvalidErrorShape()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "errorShape"');

        $specificationArray = [
            'rest_service' => [
                'endpoint' => 'http://httpbin.org',
                'operations' => [
                    'getFoo' => [
                        'httpMethod' => 'GET',
                        'requestUri' => '/get',
                    ],
                ],
                'errorShapes' => [
                    'AnotherErrorShape' => [
                    ],
                    'GetErrorShape' => [
                        'type' => 'httpStatusCode',
                        'ifCode' => 400,
                        'operator' => '>=',
                        'errorShape' => 'AnotherShape',
                    ],
                ],
            ],
        ];
        $processor = new Processor();
        $specification = $processor->processConfiguration(
            new SpecificationConfiguration(),
            $specificationArray
        );
    }

    public function testValidErrorShape()
    {
        $specificationArray = [
            'rest_service' => [
                'endpoint' => 'http://httpbin.org',
                'operations' => [
                    'getFoo' => [
                        'httpMethod' => 'GET',
                        'requestUri' => '/get',
                    ],
                ],
                'errorShapes' => [
                    'AnotherErrorShape' => [
                        'type' => 'httpStatusCode',
                        'ifCode' => 400,
                        'operator' => '>=',
                    ],
                ],
            ],
        ];
        $processor = new Processor();
        $specification = $processor->processConfiguration(
            new SpecificationConfiguration(),
            $specificationArray
        );

        $this->assertSame(1, count($specification['errorShapes']));
    }
}
