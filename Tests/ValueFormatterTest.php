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

use IndraGunawan\RestService\ValueFormatter;
use PHPUnit\Framework\TestCase;

class ValueFormatterTest extends TestCase
{
    public function testEmptyValueChecker()
    {
        $formatter = new ValueFormatter();
        $this->assertTrue($formatter->isValueEmpty(null));
        $this->assertTrue($formatter->isValueEmpty(''));
        $this->assertFalse($formatter->isValueEmpty('foo'));
        $this->assertFalse($formatter->isValueEmpty(0));
    }

    public function testGetValue()
    {
        $formatter = new ValueFormatter();
        $this->assertSame('foo', $formatter->getValue(null, 'foo'));
        $this->assertSame('bar', $formatter->getValue('bar', 'foo'));
    }

    public function testFormat()
    {
        $formatter = new ValueFormatter();
        $this->assertSame('foo', $formatter->format('undefined', null, 'foo'));

        $int = $formatter->format('integer', null, '10');
        $this->assertSame(10, $int);
        $this->assertInternalType('integer', $int);

        $float = $formatter->format('float', null, '10.1');
        $this->assertSame(10.1, $float);
        $this->assertInternalType('float', $float);

        $string = $formatter->format('string', null, 'foo');
        $this->assertSame('foo', $string);
        $this->assertInternalType('string', $string);
        $this->assertSame('foo_bar', $formatter->format('string', '%s_bar', 'foo'));

        $bool = $formatter->format('boolean', null, 'true');
        $this->assertTrue($bool);
        $this->assertInternalType('boolean', $bool);
        $this->assertFalse($formatter->format('boolean', null, 0));

        $number = $formatter->format('number', '2|,|.', 30000.012);
        $this->assertInternalType('string', $number);
        $this->assertSame('30.000,01', $number);
        $this->assertSame('3000', $formatter->format('number', null, 3000));

        $date = new \DateTime();
        $formatDate = $date->format('Y-m-d\TH:i:s\Z');
        $this->assertNull($formatter->format('datetime', null, null));
        $this->assertSame($formatDate, $formatter->format('datetime', 'Y-m-d\TH:i:s\Z', $date));
        $this->assertInstanceOf(\DateTime::class, $formatter->format('datetime', 'Y-m-d\TH:i:s\Z', $formatDate));
        $this->assertInstanceOf(\DateTime::class, $formatter->format('datetime', null, $formatDate));
    }
}
