<?php

namespace IndraGunawan\RestService\Tests\Validator;

use IndraGunawan\RestService\Parser\SpecificationParser;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use IndraGunawan\RestService\Exception\ValidatorException;

class SpecificationParserTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    /**
     * @expectedException \IndraGunawan\RestService\Exception\InvalidSpecificationException
     * @expectedExceptionMessage The child node "endpoint" at path "rest_service" must be configured.
     */
    public function testInvalidSpecification()
    {
        $specification = [
            'endpoint' => '{endpoint}',
        ];
        $configCacheMock = \Mockery::mock('overload:Symfony\Component\Config\ConfigCache')
            ->shouldReceive('isFresh')->andReturn(false)
            ->shouldReceive('write')->andReturn(null)
        ;

        $processorMock = \Mockery::mock('overload:Symfony\Component\Config\Definition\Processor')
            ->shouldReceive('processConfiguration')->andThrow(InvalidConfigurationException::class, 'The child node "endpoint" at path "rest_service" must be configured.');
        ;

        $validatorMock = \Mockery::mock('overload:IndraGunawan\RestService\Validator\Validator')
            ->shouldReceive('add')->andReturn(null)
            ->shouldReceive('isValid')->andReturn(true)
            ->shouldReceive('getDatas')->andReturn(['endpoint' => 'http://httpbin.org'])
        ;

        $specificationParser = new SpecificationParser();
        $specificationParser->parse(__DIR__.'/../fixtures/api-specification.php');
    }

    /**
     * @expectedException \IndraGunawan\RestService\Exception\InvalidSpecificationException
     */
    public function testInvalidParseDefault()
    {
        $specification = [
            'endpoint' => '{endpoint}',
            'defaults' => [
                'endpoint' => [
                    'defaultValue' => 'http://httpbin.org',
                ],
            ],
            'operations' => [],
            'shapes' => [],
            'errorShapes' => [],
        ];

        $configCacheMock = \Mockery::mock('overload:Symfony\Component\Config\ConfigCache')
            ->shouldReceive('isFresh')->andReturn(false)
            ->shouldReceive('write')->andReturn(null)
        ;

        $processorMock = \Mockery::mock('overload:Symfony\Component\Config\Definition\Processor')
            ->shouldReceive('processConfiguration')->andReturn($specification)
        ;

        $validatorMock = \Mockery::mock('overload:IndraGunawan\RestService\Validator\Validator')
            ->shouldReceive('add')->andReturn(null)
            ->shouldReceive('isValid')->andReturn(true)
            ->shouldReceive('getDatas')->andReturn(['endpoint' => 'http://httpbin.org'])
        ;

        $specificationParser = new SpecificationParser();
        $specificationParser->parse(__DIR__.'/../fixtures/api-specification.php', ['userKey' => 'foobar']);
    }

    /**
     * @expectedException \IndraGunawan\RestService\Exception\InvalidSpecificationException
     */
    public function testDefaultNotValid()
    {
        $specification = [
            'endpoint' => '{endpoint}',
            'defaults' => [
                'endpoint' => [
                    'defaultValue' => 'http://httpbin.org',
                ],
            ],
        ];

        $configCacheMock = \Mockery::mock('overload:Symfony\Component\Config\ConfigCache')
            ->shouldReceive('isFresh')->andReturn(false)
            ->shouldReceive('write')->andReturn(null)
        ;

        $processorMock = \Mockery::mock('overload:Symfony\Component\Config\Definition\Processor')
            ->shouldReceive('processConfiguration')->andReturn($specification)
        ;

        $validatorMock = \Mockery::mock('overload:IndraGunawan\RestService\Validator\Validator')
            ->shouldReceive('add')->andReturn(null)
            ->shouldReceive('isValid')->andReturn(false)
            ->shouldReceive('getDatas')->andReturn(['endpoint' => 'http://httpbin.org'])
        ;

        $specificationParser = new SpecificationParser();
        $specificationParser->parse(__DIR__.'/../fixtures/api-specification.php');
    }
}
