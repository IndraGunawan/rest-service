<?php

namespace IndraGunawan\RestService\Tests;

use IndraGunawan\RestService\ValueFormatter;

class ValueFormatterTest extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals('foo', $formatter->getValue(null, 'foo'));
        $this->assertEquals('bar', $formatter->getValue('bar', 'foo'));
    }

    public function testFormat()
    {
        $formatter = new ValueFormatter();
        $this->assertEquals('foo', $formatter->format('undefined', null, 'foo'));

        $int = $formatter->format('integer', null, '10');
        $this->assertEquals(10, $int);
        $this->assertInternalType('integer', $int);

        $float = $formatter->format('float', null, '10.1');
        $this->assertEquals(10.1, $float);
        $this->assertInternalType('float', $float);

        $string = $formatter->format('string', null, 'foo');
        $this->assertEquals('foo', $string);
        $this->assertInternalType('string', $string);
        $this->assertEquals('foo_bar', $formatter->format('string', '%s_bar', 'foo'));

        $bool = $formatter->format('boolean', null, 'true');
        $this->assertTrue($bool);
        $this->assertInternalType('boolean', $bool);
        $this->assertFalse($formatter->format('boolean', null, 0));

        $number = $formatter->format('number', '2|,|.', 30000.012);
        $this->assertInternalType('string', $number);
        $this->assertEquals('30.000,01', $number);
        $this->assertEquals('3000', $formatter->format('number', null, 3000));

        $date = new \DateTime();
        $formatDate = $date->format('Y-m-d\TH:i:s\Z');
        $this->assertNull($formatter->format('datetime', null, null));
        $this->assertEquals($formatDate, $formatter->format('datetime', 'Y-m-d\TH:i:s\Z', $date));
        $this->assertInstanceOf(\DateTime::class, $formatter->format('datetime', 'Y-m-d\TH:i:s\Z', $formatDate));
        $this->assertInstanceOf(\DateTime::class, $formatter->format('datetime', null, $formatDate));
    }
}
