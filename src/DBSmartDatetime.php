<?php

namespace ArchiPro\Silverstripe\DBEnum;

use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * A smart datetime field that can automatically convert various input formats
 * to a proper DBDatetime value.
 *
 * Supported input formats:
 * - Unix timestamp (int)
 * - ISO 8601 string (e.g., "2024-01-15T10:30:00Z")
 * - SQL datetime string (e.g., "2024-01-15 10:30:00")
 * - DBDatetime object
 * - DateTime/DateTimeInterface object
 * - Relative date strings (e.g., "+1 day", "next Monday")
 *
 * Example usage:
 * ```php
 * private static $db = [
 *     'PublishDate' => DBSmartDatetime::class,
 *     'ExpiryDate' => DBSmartDatetime::class,
 * ];
 *
 * // Usage:
 * $obj->PublishDate = time(); // Unix timestamp
 * $obj->PublishDate = '2024-01-15T10:30:00Z'; // ISO 8601
 * $obj->PublishDate = new DateTime('tomorrow'); // DateTime object
 * $obj->PublishDate = 1705318200; // Unix timestamp
 * ```
 */
class DBSmartDatetime extends DBDatetime
{
    /**
     * Set the value, intelligently converting from various formats.
     *
     * @param \DateTimeInterface|DBDatetime|string|int|null $value
     * @param array<string,mixed>|null $record
     * @param bool $markChanged
     * @return $this
     */
    public function setValue($value, $record = null, $markChanged = true)
    {
        // Handle null or empty values
        if ($value === null || $value === '') {
            return parent::setValue(null, $record, $markChanged);
        }

        // Handle DBDatetime objects
        if ($value instanceof DBDatetime) {
            return parent::setValue($value->getValue(), $record, $markChanged);
        }

        // Handle DateTime/DateTimeInterface objects
        if ($value instanceof \DateTimeInterface) {
            return parent::setValue($value->format('Y-m-d H:i:s'), $record, $markChanged);
        }

        // Handle Unix timestamps (integers)
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $timestamp = (int) $value;
            // Validate timestamp is reasonable (between year 1970 and 2100)
            if ($timestamp >= 0 && $timestamp <= 4102444800) {
                $dateTime = new \DateTime('@' . $timestamp);
                $dateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                return parent::setValue($dateTime->format('Y-m-d H:i:s'), $record, $markChanged);
            }
        }

        // Handle string values
        if (is_string($value)) {
            $value = trim($value);

            // Try to parse the string as a date
            try {
                $dateTime = new \DateTime($value);
                return parent::setValue($dateTime->format('Y-m-d H:i:s'), $record, $markChanged);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid datetime value: "%s". Error: %s',
                        $value,
                        $e->getMessage()
                    )
                );
            }
        }

        throw new \InvalidArgumentException(
            sprintf(
                'DBSmartDatetime expects a DateTime, DBDatetime, string, int (timestamp), or null. Got: %s',
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }

    /**
     * Get the value as a DateTime object.
     *
     * @return \DateTime|null
     */
    public function getDateTime(): ?\DateTime
    {
        $value = $this->getValue();

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTime($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the value as a Unix timestamp.
     *
     * @return int|null
     */
    public function getTimestamp(): ?int
    {
        $dateTime = $this->getDateTime();

        if ($dateTime === null) {
            return null;
        }

        return $dateTime->getTimestamp();
    }

    /**
     * Format the datetime using a custom format string.
     *
     * @param string $format PHP date format string
     * @return ?string The date in the requested format
     */
    public function Format($format)
    {
        $dateTime = $this->getDateTime();

        if ($dateTime === null) {
            return '';
        }

        return $dateTime->format($format);
    }

    /**
     * Check if the datetime is in the past.
     *
     * @return bool
     */
    public function isPast(): bool
    {
        $dateTime = $this->getDateTime();

        if ($dateTime === null) {
            return false;
        }

        return $dateTime < new \DateTime();
    }

    /**
     * Check if the datetime is in the future.
     *
     * @return bool
     */
    public function isFuture(): bool
    {
        $dateTime = $this->getDateTime();

        if ($dateTime === null) {
            return false;
        }

        return $dateTime > new \DateTime();
    }

    /**
     * Check if the datetime is today.
     *
     * @return bool
     */
    public function isToday(): bool
    {
        $dateTime = $this->getDateTime();

        if ($dateTime === null) {
            return false;
        }

        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return $dateTime >= $today && $dateTime < $tomorrow;
    }

    /**
     * Get a human-readable relative time string (e.g., "2 hours ago", "in 3 days").
     *
     * @return string|null
     */
    public function getRelativeTime(): ?string
    {
        $dateTime = $this->getDateTime();

        if ($dateTime === null) {
            return null;
        }

        $now = new \DateTime();
        $diff = $now->diff($dateTime);

        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y . ' year' . ($diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
        }
        if ($diff->d > 0) {
            $parts[] = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
        }
        if (empty($parts)) {
            if ($diff->h > 0) {
                $parts[] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
            }
            if ($diff->i > 0) {
                $parts[] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
            }
        }

        if (empty($parts)) {
            return 'just now';
        }

        $relativeString = implode(', ', array_slice($parts, 0, 2));

        return $diff->invert ? $relativeString . ' ago' : 'in ' . $relativeString;
    }

    /**
     * Add an interval to the datetime.
     *
     * @param \DateInterval|string $interval DateInterval or string (e.g., "1 day", "2 weeks")
     * @return $this
     */
    public function add($interval): self
    {
        $dateTime = $this->getDateTime();

        if ($dateTime === null) {
            throw new \RuntimeException('Cannot add interval to null datetime');
        }

        if (is_string($interval)) {
            $interval = \DateInterval::createFromDateString($interval);
        }

        if (!$interval instanceof \DateInterval) {
            throw new \InvalidArgumentException('Interval must be a DateInterval or string');
        }

        $dateTime->add($interval);
        $this->setValue($dateTime);

        return $this;
    }

    /**
     * Subtract an interval from the datetime.
     *
     * @param \DateInterval|string $interval DateInterval or string (e.g., "1 day", "2 weeks")
     * @return $this
     */
    public function sub($interval): self
    {
        $dateTime = $this->getDateTime();

        if ($dateTime === null) {
            throw new \RuntimeException('Cannot subtract interval from null datetime');
        }

        if (is_string($interval)) {
            $interval = \DateInterval::createFromDateString($interval);
        }

        if (!$interval instanceof \DateInterval) {
            throw new \InvalidArgumentException('Interval must be a DateInterval or string');
        }

        $dateTime->sub($interval);
        $this->setValue($dateTime);

        return $this;
    }
}
