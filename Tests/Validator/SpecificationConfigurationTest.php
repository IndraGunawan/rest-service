<?php

namespace IndraGunawan\RestService\Tests\Validator;

use IndraGunawan\RestService\Validator\SpecificationConfiguration;
use Symfony\Component\Config\Definition\Processor;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class SpecificationConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidConfiguration()
    {
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

        $this->assertEquals('http://httpbin.org', $specification['endpoint']);
        $this->assertEquals('Foo Rest Service', $specification['name']);
        $this->assertEquals(1, count($specification['operations']));
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unrecognized option "shape"
     */
    public function testInvalidShape()
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
                    ],
                    'GetShape' => [
                        'members' => [
                            'id' => [
                                'location' => 'uri',
                            ]
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
                            ]
                        ],
                    ],
                    'AnotherShape1' => [
                        'extends' => 'AnotherShape',
                    ]
                ],
            ],
        ];
        $processor = new Processor();
        $specification = $processor->processConfiguration(
            new SpecificationConfiguration(),
            $specificationArray
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unrecognized option "errorShape"
     */
    public function testInvalidErrorShape()
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
                ]
            ],
        ];
        $processor = new Processor();
        $specification = $processor->processConfiguration(
            new SpecificationConfiguration(),
            $specificationArray
        );
    }
}
