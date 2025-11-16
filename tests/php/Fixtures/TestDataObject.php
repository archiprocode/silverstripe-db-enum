<?php

namespace ArchiPro\Silverstripe\DbEnum\Tests\Fixtures;

use ArchiPro\Silverstripe\DBEnum\DBJson;
use ArchiPro\Silverstripe\DBEnum\DBSmartDatetime;
use ArchiPro\Silverstripe\DBEnum\DBSmartEnum;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Test DataObject for testing smart field types.
 *
 * @property string $Title
 * @property array|null $Settings
 * @property StatusEnum|string|null $Status
 * @property PriorityEnum|int|null $Priority
 * @property DBSmartDatetime|null $PublishDate
 * @property DBSmartDatetime|null $ExpiryDate
 */
class TestDataObject extends DataObject implements TestOnly
{
    private static $table_name = 'TestDataObject';

    private static $db = [
        'Title' => 'Varchar(255)',
        'Settings' => DBJson::class,
        'Status' => 'SmartEnum("ArchiPro\\\\Silverstripe\\\\DbEnum\\\\Tests\\\\Fixtures\\\\StatusEnum", "pending")',
        'Priority' => 'SmartEnum("ArchiPro\\\\Silverstripe\\\\DbEnum\\\\Tests\\\\Fixtures\\\\PriorityEnum", 2)',
        'PublishDate' => DBSmartDatetime::class,
        'ExpiryDate' => DBSmartDatetime::class,
    ];
}
