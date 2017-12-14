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

use IndraGunawan\RestService\Exception\InvalidSpecificationException;
use IndraGunawan\RestService\Parser\SpecificationParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class SpecificationParserTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    public function testInvalidSpecification()
    {
        $this->expectException(InvalidSpecificationException::class);
        $this->expectExceptionMessage('The child node "endpoint" at path "rest_service" must be configured.');

        $specification = [
            'endpoint' => '{endpoint}',
        ];
        $configCacheMock = \Mockery::mock('overload:Symfony\Component\Config\ConfigCache')
            ->shouldReceive('isFresh')->andReturn(false)
            ->shouldReceive('write')->andReturn(null)
        ;

        $processorMock = \Mockery::mock('overload:Symfony\Component\Config\Definition\Processor')
            ->shouldReceive('processConfiguration')->andThrow(InvalidConfigurationException::class, 'The child node "endpoint" at path "rest_service" must be configured.');

        $validatorMock = \Mockery::mock('overload:IndraGunawan\RestService\Validator\Validator')
            ->shouldReceive('add')->andReturn(null)
            ->shouldReceive('isValid')->andReturn(true)
            ->shouldReceive('getDatas')->andReturn(['endpoint' => 'http://httpbin.org'])
        ;

        $specificationParser = new SpecificationParser();
        $specificationParser->parse(__DIR__.'/../fixtures/api-specification.php');
    }

    public function testInvalidParseDefault()
    {
        $this->expectException(InvalidSpecificationException::class);

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

    public function testDefaultNotValid()
    {
        $this->expectException(InvalidSpecificationException::class);

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
