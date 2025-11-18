<?php

namespace ArchiPro\Silverstripe\DbEnum\Tests;

use ArchiPro\Silverstripe\DBEnum\JsonFilter;
use ArchiPro\Silverstripe\DbEnum\Tests\Fixtures\TestDataObject;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;

/**
 * Tests for the JsonFilter.
 *
 * @covers \ArchiPro\Silverstripe\DBEnum\JsonFilter
 */
class JsonFilterTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        TestDataObject::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test objects with various JSON data
        $obj1 = TestDataObject::create();
        $obj1->Title = 'Product 1';
        $obj1->Settings = [
            'author' => 'John Doe',
            'price' => 100,
            'meta' => [
                'title' => 'Product One',
                'category' => 'Electronics',
            ],
        ];
        $obj1->write();

        $obj2 = TestDataObject::create();
        $obj2->Title = 'Product 2';
        $obj2->Settings = [
            'author' => 'Jane Smith',
            'price' => 200,
            'meta' => [
                'title' => 'Product Two',
                'category' => 'Books',
            ],
        ];
        $obj2->write();

        $obj3 = TestDataObject::create();
        $obj3->Title = 'Product 3';
        $obj3->Settings = [
            'author' => 'John Doe',
            'price' => 150,
            'meta' => [
                'title' => 'Product Three',
                'category' => 'Electronics',
            ],
        ];
        $obj3->write();

        $obj4 = TestDataObject::create();
        $obj4->Title = 'Product 4';
        $obj4->Settings = null;
        $obj4->write();
    }

    public function testFilterBySinglePath(): void
    {
        $list = TestDataObject::get()->filter('Settings:Json', ['$.author' => 'John Doe']);

        $this->assertCount(2, $list, 'Should find 2 objects with author John Doe');
        $this->assertEquals('Product 1', $list->first()->Title);
        $this->assertEquals('Product 3', $list->last()->Title);
    }

    public function testFilterByNestedPath(): void
    {
        $list = TestDataObject::get()->filter('Settings:Json', ['$.meta.category' => 'Electronics']);

        $this->assertCount(2, $list, 'Should find 2 objects in Electronics category');
    }

    public function testFilterByComplexObject(): void
    {
        $list = TestDataObject::get()->filter('Settings:Json', [
            '$.meta' => ['title' => 'Product One', 'category' => 'Electronics']
        ]);

        $this->assertCount(1, $list, 'Should find 1 object matching the complex filter');
        $this->assertEquals('Product 1', $list->first()->Title);
    }

    public function testFilterWithNotModifier(): void
    {
        $list = TestDataObject::get()->filter('Settings:Json:not', ['$.author' => 'John Doe']);

        $this->assertCount(1, $list, 'Should find 1 object where author is not John Doe');
        $this->assertEquals('Product 2', $list->first()->Title);
    }

    public function testFilterByMultiplePaths(): void
    {
        $list = TestDataObject::get()->filter('Settings:Json', [
            '$.author' => 'John Doe',
            '$.meta.category' => 'Electronics',
        ]);

        // This uses whereAny, so it should match any of the conditions
        $this->assertGreaterThanOrEqual(2, $list->count());
    }

    public function testFilterWithEmptyArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filtering by an empty array is not supported');

        TestDataObject::get()->filter('Settings:Json', [])->toArray();
    }

    public function testFilterWithNonArrayValueThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must provide value as array when filtering by JSON_CONTAINS');

        TestDataObject::get()->filter('Settings:Json', 'invalid')->toArray();
    }

    public function testExcludeWithEmptyArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filtering by an empty array is not supported');

        TestDataObject::get()->exclude('Settings:Json', [])->toArray();
    }

    public function testExcludeWithNonArrayValueThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Must provide value as array when filtering by JSON_CONTAINS');

        TestDataObject::get()->exclude('Settings:Json', 'invalid')->toArray();
    }

    public function testGetSupportedModifiers(): void
    {
        $filter = new JsonFilter();
        $modifiers = $filter->getSupportedModifiers();

        $this->assertContains('not', $modifiers);
    }

    public function testIsEmpty(): void
    {
        $filter = new JsonFilter();
        $filter->setValue(null);

        $this->assertTrue($filter->isEmpty());

        $filter->setValue(['$.author' => 'John Doe']);
        $this->assertFalse($filter->isEmpty());
    }

    public function testFilterWithNumericValue(): void
    {
        $list = TestDataObject::get()->filter('Settings:Json', ['$.price' => 100]);

        $this->assertCount(1, $list);
        $this->assertEquals('Product 1', $list->first()->Title);
    }

    public function testFilterWithNullData(): void
    {
        // Objects with null Settings should not match
        $list = TestDataObject::get()->filter('Settings:Json', ['$.author' => 'John Doe']);

        // Should only find objects with actual JSON data, not the one with null
        $this->assertCount(2, $list);
    }
}
