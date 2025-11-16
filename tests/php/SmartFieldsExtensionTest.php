<?php

namespace ArchiPro\Silverstripe\DbEnum\Tests;

use ArchiPro\Silverstripe\DbEnum\Tests\Fixtures\PriorityEnum;
use ArchiPro\Silverstripe\DbEnum\Tests\Fixtures\StatusEnum;
use ArchiPro\Silverstripe\DbEnum\Tests\Fixtures\TestDataObject;
use ArchiPro\Silverstripe\DbEnum\Tests\Fixtures\TestDataObjectWithCustomAccessors;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the SmartFieldsExtension.
 *
 * @covers \ArchiPro\Silverstripe\DBEnum\SmartFieldsExtension
 */
class SmartFieldsExtensionTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        TestDataObject::class,
        TestDataObjectWithCustomAccessors::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testJsonFieldReturnsArray(): void
    {
        $obj = TestDataObject::create();
        $data = ['key' => 'value', 'nested' => ['foo' => 'bar']];

        $obj->Settings = $data;
        $obj->write();

        // Reload from database
        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertIsArray($obj->Settings);
        $this->assertEquals($data, $obj->Settings);
    }

    public function testJsonFieldAcceptsJsonString(): void
    {
        $obj = TestDataObject::create();
        $data = ['key' => 'value'];
        $json = json_encode($data);

        $obj->Settings = $json;
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertIsArray($obj->Settings);
        $this->assertEquals($data, $obj->Settings);
    }

    public function testEnumFieldReturnsEnumInstance(): void
    {
        $obj = TestDataObject::create();
        $obj->Status = 'active';
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertInstanceOf(StatusEnum::class, $obj->Status);
        $this->assertEquals(StatusEnum::Active, $obj->Status);
    }

    public function testEnumFieldAcceptsEnumInstance(): void
    {
        $obj = TestDataObject::create();
        $obj->Status = StatusEnum::Completed;
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertEquals(StatusEnum::Completed, $obj->Status);
    }

    public function testEnumFieldAcceptsString(): void
    {
        $obj = TestDataObject::create();
        $obj->Status = 'inactive';
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertEquals(StatusEnum::Inactive, $obj->Status);
    }

    public function testIntEnumField(): void
    {
        $obj = TestDataObject::create();
        $obj->Priority = 3;
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertInstanceOf(PriorityEnum::class, $obj->Priority);
        $this->assertEquals(PriorityEnum::High, $obj->Priority);
    }

    public function testIntEnumFieldAcceptsEnumInstance(): void
    {
        $obj = TestDataObject::create();
        $obj->Priority = PriorityEnum::Critical;
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertEquals(PriorityEnum::Critical, $obj->Priority);
    }

    public function testDatetimeFieldAcceptsTimestamp(): void
    {
        $obj = TestDataObject::create();
        $timestamp = 1705318200; // 2024-01-15 10:30:00 UTC

        $obj->PublishDate = $timestamp;
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertNotNull($obj->PublishDate);
        $dateTime = $obj->PublishDate->getDateTime();
        $this->assertEquals($timestamp, $dateTime->getTimestamp());
    }

    public function testDatetimeFieldAcceptsString(): void
    {
        $obj = TestDataObject::create();
        $obj->PublishDate = '2024-01-15 10:30:00';
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertEquals('2024-01-15 10:30:00', $obj->PublishDate->getValue());
    }

    public function testDatetimeFieldAcceptsDateTimeObject(): void
    {
        $obj = TestDataObject::create();
        $dateTime = new \DateTime('2024-01-15 10:30:00');

        $obj->PublishDate = $dateTime;
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertEquals('2024-01-15 10:30:00', $obj->PublishDate->getValue());
    }

    public function testMultipleSmartFieldsTogether(): void
    {
        $obj = TestDataObject::create();

        $obj->Title = 'Test Object';
        $obj->Settings = ['enabled' => true, 'limit' => 100];
        $obj->Status = StatusEnum::Active;
        $obj->Priority = PriorityEnum::High;
        $obj->PublishDate = time();
        $obj->ExpiryDate = '2024-12-31 23:59:59';

        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertEquals('Test Object', $obj->Title);
        $this->assertIsArray($obj->Settings);
        $this->assertEquals(['enabled' => true, 'limit' => 100], $obj->Settings);
        $this->assertEquals(StatusEnum::Active, $obj->Status);
        $this->assertEquals(PriorityEnum::High, $obj->Priority);
        $this->assertNotNull($obj->PublishDate);
        $this->assertEquals('2024-12-31 23:59:59', $obj->ExpiryDate->getValue());
    }

    public function testCustomAccessorsAreRespected(): void
    {
        $obj = TestDataObjectWithCustomAccessors::create();
        $obj->Config = ['foo' => 'bar'];
        $obj->write();

        $obj = TestDataObjectWithCustomAccessors::get()->byID($obj->ID);

        // The custom accessor should wrap the value in a 'data' key
        $config = $obj->Config;
        $this->assertIsArray($config);
        $this->assertArrayHasKey('data', $config);
        $this->assertEquals(['foo' => 'bar'], $config['data']);
    }

    public function testCustomMutatorsAreRespected(): void
    {
        $obj = TestDataObjectWithCustomAccessors::create();

        // The custom mutator should extract the 'data' key
        $obj->Config = ['data' => ['foo' => 'bar']];
        $obj->write();

        // Access the raw field to see what was actually stored
        $rawValue = $obj->getField('Config');
        $decoded = json_decode($rawValue, true);

        $this->assertEquals(['foo' => 'bar'], $decoded);
    }

    public function testCustomDatetimeAccessorIsRespected(): void
    {
        $obj = TestDataObjectWithCustomAccessors::create();
        $obj->CustomDate = 'tomorrow';
        $obj->write();

        $obj = TestDataObjectWithCustomAccessors::get()->byID($obj->ID);

        // The custom accessor should always return yesterday
        $customDate = $obj->CustomDate;
        $this->assertInstanceOf(\DateTime::class, $customDate);
        $this->assertEquals(
            (new \DateTime('yesterday'))->format('Y-m-d'),
            $customDate->format('Y-m-d')
        );
    }

    public function testNullValuesHandling(): void
    {
        $obj = TestDataObject::create();
        $obj->Settings = null;
        $obj->Status = null;
        $obj->PublishDate = null;
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertNull($obj->Settings);
        $this->assertNull($obj->Status);
        $this->assertNull($obj->PublishDate);
    }

    public function testEmptyArrayForJson(): void
    {
        $obj = TestDataObject::create();
        $obj->Settings = [];
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        // Empty arrays should be preserved
        $this->assertEquals([], $obj->Settings);
    }

    public function testDefaultValues(): void
    {
        $obj = TestDataObject::create();
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        // Check default values are applied
        $this->assertEquals(StatusEnum::Pending, $obj->Status);
        $this->assertEquals(PriorityEnum::Medium, $obj->Priority);
    }

    public function testChangedFieldsDetection(): void
    {
        $obj = TestDataObject::create();
        $obj->Settings = ['initial' => 'value'];
        $obj->Status = StatusEnum::Pending;
        $obj->write();

        // Load fresh
        $obj = TestDataObject::get()->byID($obj->ID);

        // Make changes
        $obj->Settings = ['updated' => 'value'];
        $obj->Status = StatusEnum::Active;

        $this->assertTrue($obj->isChanged('Settings'));
        $this->assertTrue($obj->isChanged('Status'));
    }

    public function testEnumComparison(): void
    {
        $obj = TestDataObject::create();
        $obj->Status = StatusEnum::Active;
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        // Should be able to compare directly with enum instances
        $this->assertTrue($obj->Status === StatusEnum::Active);
        $this->assertFalse($obj->Status === StatusEnum::Pending);
    }

    public function testDatetimeHelperMethods(): void
    {
        $obj = TestDataObject::create();
        $obj->PublishDate = 'yesterday';
        $obj->ExpiryDate = 'tomorrow';
        $obj->write();

        $obj = TestDataObject::get()->byID($obj->ID);

        $this->assertTrue($obj->PublishDate->isPast());
        $this->assertTrue($obj->ExpiryDate->isFuture());
    }
}
