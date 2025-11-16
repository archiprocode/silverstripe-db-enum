<?php

namespace ArchiPro\Silverstripe\DbEnum\Tests;

use ArchiPro\Silverstripe\DBEnum\DBJson;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the DBJson field type.
 *
 * @covers \ArchiPro\Silverstripe\DBEnum\DBJson
 */
class DBJsonTest extends SapphireTest
{
    public function testSetValueWithArray(): void
    {
        $field = DBJson::create('TestField');
        $data = ['key' => 'value', 'nested' => ['foo' => 'bar']];

        $field->setValue($data);

        $this->assertEquals($data, $field->getValue());
    }

    public function testSetValueWithJsonString(): void
    {
        $field = DBJson::create('TestField');
        $data = ['key' => 'value', 'nested' => ['foo' => 'bar']];
        $json = json_encode($data);

        $field->setValue($json);

        $this->assertEquals($data, $field->getValue());
    }

    public function testSetValueWithNull(): void
    {
        $field = DBJson::create('TestField');

        $field->setValue(null);

        $this->assertNull($field->getValue());
    }

    public function testSetValueWithEmptyString(): void
    {
        $field = DBJson::create('TestField');

        $field->setValue('');

        $this->assertNull($field->getValue());
    }

    public function testSetValueWithInvalidJsonThrowsException(): void
    {
        $field = DBJson::create('TestField');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON string');

        $field->setValue('invalid json {');
    }

    public function testSetValueWithInvalidTypeThrowsException(): void
    {
        $field = DBJson::create('TestField');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DBJson field expects an array, JSON string, or null');

        $field->setValue(123);
    }

    public function testGetKey(): void
    {
        $field = DBJson::create('TestField');
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $field->setValue($data);

        $this->assertEquals('value1', $field->getKey('key1'));
        $this->assertEquals('value2', $field->getKey('key2'));
        $this->assertNull($field->getKey('nonexistent'));
    }

    public function testSetKey(): void
    {
        $field = DBJson::create('TestField');
        $field->setValue(['existing' => 'value']);

        $field->setKey('newKey', 'newValue');

        $this->assertEquals('newValue', $field->getKey('newKey'));
        $this->assertEquals('value', $field->getKey('existing'));
    }

    public function testSetKeyCreatesArrayIfNull(): void
    {
        $field = DBJson::create('TestField');
        $field->setValue(null);

        $field->setKey('key', 'value');

        $this->assertEquals(['key' => 'value'], $field->getValue());
    }

    public function testRemoveKey(): void
    {
        $field = DBJson::create('TestField');
        $field->setValue(['key1' => 'value1', 'key2' => 'value2']);

        $field->removeKey('key1');

        $this->assertNull($field->getKey('key1'));
        $this->assertEquals('value2', $field->getKey('key2'));
    }

    public function testHasKey(): void
    {
        $field = DBJson::create('TestField');
        $field->setValue(['key1' => 'value1', 'key2' => null]);

        $this->assertTrue($field->hasKey('key1'));
        $this->assertTrue($field->hasKey('key2')); // null value still counts as having the key
        $this->assertFalse($field->hasKey('nonexistent'));
    }

    public function testExists(): void
    {
        $field = DBJson::create('TestField');

        $field->setValue(null);
        $this->assertFalse($field->exists());

        $field->setValue([]);
        $this->assertFalse($field->exists());

        $field->setValue(['key' => 'value']);
        $this->assertTrue($field->exists());
    }

    public function testPrepValueForDB(): void
    {
        $field = DBJson::create('TestField');
        $data = ['key' => 'value', 'nested' => ['foo' => 'bar']];

        $result = $field->prepValueForDB($data);

        $this->assertIsString($result);
        $this->assertEquals($data, json_decode($result, true));
    }

    public function testPrepValueForDBWithJsonString(): void
    {
        $field = DBJson::create('TestField');
        $data = ['key' => 'value'];
        $json = json_encode($data);

        $result = $field->prepValueForDB($json);

        $this->assertEquals($json, $result);
    }

    public function testPrepValueForDBWithNull(): void
    {
        $field = DBJson::create('TestField');

        $result = $field->prepValueForDB(null);

        $this->assertNull($result);
    }

    public function testPrepValueForDBWithEmptyString(): void
    {
        $field = DBJson::create('TestField');

        $result = $field->prepValueForDB('');

        $this->assertNull($result);
    }

    public function testPrepValueForDBWithInvalidJsonThrowsException(): void
    {
        $field = DBJson::create('TestField');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON string');

        $field->prepValueForDB('invalid json {');
    }

    public function testForTemplate(): void
    {
        $field = DBJson::create('TestField');
        $data = ['key' => 'value', 'nested' => ['foo' => 'bar']];
        $field->setValue($data);

        $result = $field->forTemplate();

        $this->assertIsString($result);
        $this->assertStringContainsString('"key"', $result);
        $this->assertStringContainsString('"value"', $result);
    }

    public function testForTemplateWithNull(): void
    {
        $field = DBJson::create('TestField');
        $field->setValue(null);

        $result = $field->forTemplate();

        $this->assertEquals('', $result);
    }

    public function testJsonSerialize(): void
    {
        $field = DBJson::create('TestField');
        $data = ['key' => 'value', 'nested' => ['foo' => 'bar']];
        $field->setValue($data);

        $result = $field->jsonSerialize();

        $this->assertEquals($data, $result);
    }

    public function testComplexNestedData(): void
    {
        $field = DBJson::create('TestField');
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'John', 'tags' => ['admin', 'user']],
                ['id' => 2, 'name' => 'Jane', 'tags' => ['user']],
            ],
            'settings' => [
                'enabled' => true,
                'limit' => 100,
                'config' => [
                    'debug' => false,
                    'cache' => true,
                ],
            ],
        ];

        $field->setValue($data);
        $this->assertEquals($data, $field->getValue());

        // Test that it can be properly encoded for DB
        $encoded = $field->prepValueForDB($field->getValue());
        $this->assertIsString($encoded);

        // Test that it can be decoded back
        $field2 = DBJson::create('TestField2');
        $field2->setValue($encoded);
        $this->assertEquals($data, $field2->getValue());
    }
}
