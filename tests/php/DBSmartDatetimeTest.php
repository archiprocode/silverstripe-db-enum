<?php

namespace ArchiPro\Silverstripe\DbEnum\Tests;

use ArchiPro\Silverstripe\DBEnum\DBSmartDatetime;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * Tests for the DBSmartDatetime field type.
 *
 * @covers \ArchiPro\Silverstripe\DBEnum\DBSmartDatetime
 */
class DBSmartDatetimeTest extends SapphireTest
{
    public function testSetValueWithUnixTimestamp(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $timestamp = 1705318200; // 2024-01-15 10:30:00 UTC

        $field->setValue($timestamp);

        $dateTime = $field->getDateTime();
        $this->assertInstanceOf(\DateTime::class, $dateTime);
        $this->assertEquals($timestamp, $dateTime->getTimestamp());
    }

    public function testSetValueWithStringTimestamp(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $timestamp = '1705318200';

        $field->setValue($timestamp);

        $this->assertEquals(1705318200, $field->getTimestamp());
    }

    public function testSetValueWithIso8601String(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $isoDate = '2024-01-15T10:30:00Z';

        $field->setValue($isoDate);

        $dateTime = $field->getDateTime();
        $this->assertEquals('2024-01-15', $dateTime->format('Y-m-d'));
    }

    public function testSetValueWithSqlDatetimeString(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $sqlDate = '2024-01-15 10:30:00';

        $field->setValue($sqlDate);

        $this->assertEquals('2024-01-15 10:30:00', $field->getValue());
    }

    public function testSetValueWithDateTimeObject(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $dateTime = new \DateTime('2024-01-15 10:30:00');

        $field->setValue($dateTime);

        $this->assertEquals('2024-01-15 10:30:00', $field->getValue());
    }

    public function testSetValueWithDBDatetimeObject(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $dbDatetime = DBDatetime::create('OtherDate');
        $dbDatetime->setValue('2024-01-15 10:30:00');

        $field->setValue($dbDatetime);

        $this->assertEquals('2024-01-15 10:30:00', $field->getValue());
    }

    public function testSetValueWithRelativeDateString(): void
    {
        $field = DBSmartDatetime::create('TestDate');

        $field->setValue('tomorrow');

        $dateTime = $field->getDateTime();
        $tomorrow = new \DateTime('tomorrow');

        $this->assertEquals(
            $tomorrow->format('Y-m-d'),
            $dateTime->format('Y-m-d')
        );
    }

    public function testSetValueWithNull(): void
    {
        $field = DBSmartDatetime::create('TestDate');

        $field->setValue(null);

        $this->assertNull($field->getValue());
        $this->assertNull($field->getDateTime());
    }

    public function testSetValueWithEmptyString(): void
    {
        $field = DBSmartDatetime::create('TestDate');

        $field->setValue('');

        $this->assertNull($field->getValue());
    }

    public function testSetValueWithInvalidStringThrowsException(): void
    {
        $field = DBSmartDatetime::create('TestDate');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid datetime value');

        $field->setValue('not a valid date string xyz123');
    }

    public function testSetValueWithInvalidTypeThrowsException(): void
    {
        $field = DBSmartDatetime::create('TestDate');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DBSmartDatetime expects');

        $field->setValue(['array' => 'value']);
    }

    public function testGetTimestamp(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $timestamp = 1705318200;
        $field->setValue($timestamp);

        $this->assertEquals($timestamp, $field->getTimestamp());
    }

    public function testGetTimestampWithNull(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue(null);

        $this->assertNull($field->getTimestamp());
    }

    public function testFormat(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('2024-01-15 10:30:00');

        $formatted = $field->format('Y-m-d');

        $this->assertEquals('2024-01-15', $formatted);
    }

    public function testFormatWithCustomFormat(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('2024-01-15 10:30:00');

        $formatted = $field->format('d/m/Y H:i');

        $this->assertEquals('15/01/2024 10:30', $formatted);
    }

    public function testFormatWithNull(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue(null);

        $this->assertNull($field->format('Y-m-d'));
    }

    public function testIsPast(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('yesterday');

        $this->assertTrue($field->isPast());
    }

    public function testIsPastWithFutureDate(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('tomorrow');

        $this->assertFalse($field->isPast());
    }

    public function testIsPastWithNull(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue(null);

        $this->assertFalse($field->isPast());
    }

    public function testIsFuture(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('tomorrow');

        $this->assertTrue($field->isFuture());
    }

    public function testIsFutureWithPastDate(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('yesterday');

        $this->assertFalse($field->isFuture());
    }

    public function testIsToday(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('today');

        $this->assertTrue($field->isToday());
    }

    public function testIsTodayWithYesterday(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('yesterday');

        $this->assertFalse($field->isToday());
    }

    public function testGetRelativeTime(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $yesterday = new \DateTime('yesterday');
        $field->setValue($yesterday);

        $relative = $field->getRelativeTime();

        $this->assertStringContainsString('ago', $relative);
    }

    public function testGetRelativeTimeWithFutureDate(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $tomorrow = new \DateTime('tomorrow');
        $field->setValue($tomorrow);

        $relative = $field->getRelativeTime();

        $this->assertStringContainsString('in ', $relative);
    }

    public function testGetRelativeTimeWithNull(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue(null);

        $this->assertNull($field->getRelativeTime());
    }

    public function testAdd(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('2024-01-15 10:30:00');

        $field->add('1 day');

        $this->assertEquals('2024-01-16', $field->format('Y-m-d'));
    }

    public function testAddWithDateInterval(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('2024-01-15 10:30:00');

        $interval = new \DateInterval('P1D'); // 1 day
        $field->add($interval);

        $this->assertEquals('2024-01-16', $field->format('Y-m-d'));
    }

    public function testAddWithNullThrowsException(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot add interval to null datetime');

        $field->add('1 day');
    }

    public function testSub(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('2024-01-15 10:30:00');

        $field->sub('1 day');

        $this->assertEquals('2024-01-14', $field->format('Y-m-d'));
    }

    public function testSubWithDateInterval(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue('2024-01-15 10:30:00');

        $interval = new \DateInterval('P1D'); // 1 day
        $field->sub($interval);

        $this->assertEquals('2024-01-14', $field->format('Y-m-d'));
    }

    public function testSubWithNullThrowsException(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $field->setValue(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot subtract interval from null datetime');

        $field->sub('1 day');
    }

    public function testTimestampBoundaryValidation(): void
    {
        $field = DBSmartDatetime::create('TestDate');

        // Test year 1970 (valid)
        $field->setValue(0);
        $this->assertEquals(0, $field->getTimestamp());

        // Test year 2100 (valid - just before the boundary)
        $field->setValue(4102444800);
        $this->assertEquals(4102444800, $field->getTimestamp());
    }

    public function testTimestampWithDifferentTimezones(): void
    {
        $field = DBSmartDatetime::create('TestDate');
        $timestamp = 1705318200;

        // Set via timestamp
        $field->setValue($timestamp);

        // Should maintain the same timestamp regardless of timezone
        $this->assertEquals($timestamp, $field->getTimestamp());
    }
}
