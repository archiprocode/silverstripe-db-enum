<?php

namespace ArchiPro\Silverstripe\DbEnum\Tests;

use ArchiPro\Silverstripe\DBEnum\DBSmartEnum;
use ArchiPro\Silverstripe\DbEnum\Tests\Fixtures\PriorityEnum;
use ArchiPro\Silverstripe\DbEnum\Tests\Fixtures\StatusEnum;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the DBSmartEnum field type.
 *
 * @covers \ArchiPro\Silverstripe\DBEnum\DBSmartEnum
 */
class DBSmartEnumTest extends SapphireTest
{
    public function testConstructorWithStringEnum(): void
    {
        $field = new DBSmartEnum(
            'Status',
            StatusEnum::class,
            StatusEnum::Active->value
        );

        $this->assertEquals(
            ['pending', 'active', 'inactive', 'completed'],
            $field->getEnum()
        );
        $this->assertEquals('active', $field->getDefault());
        $this->assertEquals('Status', $field->getName());
    }

    public function testConstructorWithIntEnum(): void
    {
        $field = new DBSmartEnum(
            'Priority',
            PriorityEnum::class,
            PriorityEnum::High->value
        );

        $this->assertEquals([1, 2, 3, 4], $field->getEnum());
        $this->assertEquals(3, $field->getDefault());
    }

    public function testConstructorWithEnumInstanceAsDefault(): void
    {
        $field = new DBSmartEnum(
            'Status',
            StatusEnum::class,
            StatusEnum::Pending
        );

        $this->assertEquals('pending', $field->getDefault());
    }

    public function testConstructorWithNullValues(): void
    {
        // SilverStripe sometimes calls constructor with null values
        $field = new DBSmartEnum(null, null, null);

        $this->assertNull($field->getName());
        $this->assertNull($field->getEnumClass());
    }

    public function testConstructorWithNonBackedEnumThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a BackedEnum');

        // Create a non-backed enum for testing
        eval('namespace ArchiPro\Silverstripe\DbEnum\Tests; enum NonBackedEnum { case Foo; case Bar; }');

        new DBSmartEnum(
            'Test',
            'ArchiPro\\Silverstripe\\DbEnum\\Tests\\NonBackedEnum',
            null
        );
    }

    public function testSetValueWithString(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);
        $field->setValue('active');

        // getValue returns the enum instance
        $value = $field->getValue();
        $this->assertInstanceOf(StatusEnum::class, $value);
        $this->assertEquals(StatusEnum::Active, $value);
    }

    public function testSetValueWithEnumInstance(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);
        $field->setValue(StatusEnum::Completed);

        $value = $field->getValue();
        $this->assertInstanceOf(StatusEnum::class, $value);
        $this->assertEquals(StatusEnum::Completed, $value);
    }

    public function testSetValueWithNull(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);
        $field->setValue(null);

        $this->assertNull($field->getValue());
    }

    public function testGetEnum(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);
        $field->setValue('active');

        $enum = $field->getEnum();

        $this->assertInstanceOf(StatusEnum::class, $enum);
        $this->assertEquals(StatusEnum::Active, $enum);
    }

    public function testGetEnumWithNull(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);
        $field->setValue(null);

        $this->assertNull($field->getEnum());
    }

    public function testGetEnumWithInvalidValueThrowsException(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);
        // Force set an invalid value by using parent setValue
        $field->DBField::setValue('invalid_status');

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('not a valid case');

        $field->getEnum();
    }

    public function testGetEnumCases(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);

        $cases = $field->getEnumCases();

        $this->assertCount(4, $cases);
        $this->assertContainsOnlyInstancesOf(StatusEnum::class, $cases);
        $this->assertEquals(StatusEnum::Pending, $cases[0]);
        $this->assertEquals(StatusEnum::Active, $cases[1]);
        $this->assertEquals(StatusEnum::Inactive, $cases[2]);
        $this->assertEquals(StatusEnum::Completed, $cases[3]);
    }

    public function testGetEnumClass(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);

        $this->assertEquals(StatusEnum::class, $field->getEnumClass());
    }

    public function testPrepValueForDBWithEnum(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);

        $result = $field->prepValueForDB(StatusEnum::Active);

        $this->assertEquals('active', $result);
    }

    public function testPrepValueForDBWithString(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);

        $result = $field->prepValueForDB('active');

        $this->assertEquals('active', $result);
    }

    public function testPrepValueForDBWithNull(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);

        $result = $field->prepValueForDB(null);

        $this->assertNull($result);
    }

    public function testScaffoldFormField(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);

        $formField = $field->scaffoldFormField('Test Status');

        $this->assertInstanceOf(\SilverStripe\Forms\DropdownField::class, $formField);
        $source = $formField->getSource();

        $this->assertArrayHasKey('pending', $source);
        $this->assertArrayHasKey('active', $source);
        $this->assertArrayHasKey('inactive', $source);
        $this->assertArrayHasKey('completed', $source);
    }

    public function testScaffoldFormFieldWithLabelMethod(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);

        $formField = $field->scaffoldFormField('Test Status');
        $source = $formField->getSource();

        // StatusEnum has a label() method, so it should use those labels
        $this->assertEquals('Pending', $source['pending']);
        $this->assertEquals('Active', $source['active']);
        $this->assertEquals('Inactive', $source['inactive']);
        $this->assertEquals('Completed', $source['completed']);
    }

    public function testIntEnumHandling(): void
    {
        $field = new DBSmartEnum('Priority', PriorityEnum::class);

        $field->setValue(3);

        $enum = $field->getEnum();
        $this->assertInstanceOf(PriorityEnum::class, $enum);
        $this->assertEquals(PriorityEnum::High, $enum);
    }

    public function testIntEnumPrepValueForDB(): void
    {
        $field = new DBSmartEnum('Priority', PriorityEnum::class);

        $result = $field->prepValueForDB(PriorityEnum::Critical);

        $this->assertEquals('4', $result); // DBEnum converts to string
    }

    public function testGetValueReturnsEnumInstance(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);
        $field->setValue('completed');

        $value = $field->getValue();

        $this->assertInstanceOf(StatusEnum::class, $value);
        $this->assertEquals(StatusEnum::Completed, $value);
    }

    public function testGetValueWithInvalidValueReturnsRawValue(): void
    {
        $field = new DBSmartEnum('Status', StatusEnum::class);
        // Force set an invalid value
        $field->DBField::setValue('invalid');

        $value = $field->getValue();

        // Should return the raw value when it can't be converted to enum
        $this->assertEquals('invalid', $value);
    }
}
