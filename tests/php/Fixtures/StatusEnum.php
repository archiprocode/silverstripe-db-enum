<?php

namespace ArchiPro\Silverstripe\DbEnum\Tests\Fixtures;

/**
 * Test enum for status values.
 */
enum StatusEnum: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
    case Completed = 'completed';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Completed => 'Completed',
        };
    }
}
