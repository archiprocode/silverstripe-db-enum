<?php

namespace ArchiPro\Silverstripe\DbEnum\Tests\Fixtures;

use ArchiPro\Silverstripe\DBEnum\DBJson;
use ArchiPro\Silverstripe\DBEnum\DBSmartDatetime;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Test DataObject with custom accessors and mutators.
 *
 * Used to test that SmartFieldsExtension respects custom accessors/mutators.
 *
 * @property array|null $Config
 * @property DBSmartDatetime|null $CustomDate
 */
class TestDataObjectWithCustomAccessors extends DataObject implements TestOnly
{
    private static $table_name = 'TestDataObjectWithCustomAccessors';

    private static $db = [
        'Config' => DBJson::class,
        'CustomDate' => DBSmartDatetime::class,
    ];

    /**
     * Custom accessor that wraps the config in a 'data' key.
     */
    public function getConfig(): ?array
    {
        $value = $this->getField('Config');
        if ($value === null) {
            return null;
        }

        return ['data' => $value];
    }

    /**
     * Custom mutator that extracts the 'data' key.
     */
    public function setConfig($value): self
    {
        if (is_array($value) && isset($value['data'])) {
            $this->setField('Config', $value['data']);
        } else {
            $this->setField('Config', $value);
        }

        return $this;
    }

    /**
     * Custom accessor that always returns yesterday.
     */
    public function getCustomDate(): ?\DateTime
    {
        return new \DateTime('yesterday');
    }
}
