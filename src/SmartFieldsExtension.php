<?php

namespace ArchiPro\Silverstripe\DBEnum;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBDatetime;

/**
 * DataExtension that provides automatic type casting for smart field types.
 *
 * This extension intercepts field access on DataObjects and automatically
 * casts values to and from the appropriate types based on the field's DB type.
 *
 * Features:
 * - DBJson fields return arrays and accept arrays/JSON strings
 * - DBSmartEnum fields return enum instances and accept enum instances/strings
 * - DBSmartDatetime fields return DBDatetime objects and accept timestamps/strings/DateTime objects
 *
 * The extension respects any custom accessors/mutators defined on the DataObject,
 * only providing automatic casting when no custom method exists.
 *
 * To use, add to your config:
 * ```yaml
 * SilverStripe\ORM\DataObject:
 *   extensions:
 *     - ArchiPro\Silverstripe\DBEnum\SmartFieldsExtension
 * ```
 *
 * @property \SilverStripe\ORM\DataObject $owner
 */
class SmartFieldsExtension extends DataExtension
{
    /**
     * Intercept field access to provide smart casting.
     *
     * This method is called when a field is accessed via __get() and no
     * custom accessor method exists.
     *
     * @param string $fieldName The name of the field being accessed
     * @param mixed $value The current value of the field
     * @param array<string,mixed> $cachedFieldValues Cached field values
     * @return mixed The potentially modified value
     */
    public function updateFieldValue(string $fieldName, $value, array $cachedFieldValues)
    {
        // Check if this is a smart field type
        $dbConfig = $this->owner->config()->get('db');

        if (!isset($dbConfig[$fieldName])) {
            return $value;
        }

        $fieldType = $dbConfig[$fieldName];

        // Handle DBJson fields
        if ($this->isFieldType($fieldType, DBJson::class)) {
            return $this->castToArray($value);
        }

        // Handle DBSmartEnum fields
        if ($this->isFieldType($fieldType, DBSmartEnum::class)) {
            return $this->castToEnum($fieldName, $value, $fieldType);
        }

        // Handle DBSmartDatetime fields
        if ($this->isFieldType($fieldType, DBSmartDatetime::class)) {
            return $this->castToSmartDatetime($value);
        }

        return $value;
    }

    /**
     * Check if a field type string matches a class.
     *
     * @param string $fieldType The field type from the db config
     * @param string $className The class name to check against
     * @return bool
     */
    protected function isFieldType(string $fieldType, string $className): bool
    {
        // Direct class match
        if ($fieldType === $className) {
            return true;
        }

        // Class with parameters (e.g., "SmartEnum(...)")
        $shortName = substr($className, strrpos($className, '\\') + 1);
        if (strpos($fieldType, $shortName . '(') === 0) {
            return true;
        }

        // Full class name with parameters
        if (strpos($fieldType, $className . '(') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Cast a value to an array for DBJson fields.
     *
     * @param mixed $value
     * @return array<mixed>|null
     */
    protected function castToArray($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Cast a value to an enum for DBSmartEnum fields.
     *
     * @param string $fieldName
     * @param mixed $value
     * @param string $fieldType
     * @return \BackedEnum|string|null
     */
    protected function castToEnum(string $fieldName, $value, string $fieldType)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // If already an enum, return it
        if ($value instanceof \BackedEnum) {
            return $value;
        }

        // Extract the enum class from the field type
        $enumClass = $this->extractEnumClass($fieldType);

        if (!$enumClass || !enum_exists($enumClass)) {
            return $value;
        }

        try {
            /** @var \BackedEnum $enumClass */
            return $enumClass::from($value);
        } catch (\ValueError $e) {
            // If the value is invalid, return the raw value
            return $value;
        }
    }

    /**
     * Extract the enum class from a field type string.
     *
     * @param string $fieldType e.g., 'SmartEnum("My\\Namespace\\MyEnum", "Default")'
     * @return class-string<\BackedEnum>|null
     */
    protected function extractEnumClass(string $fieldType): ?string
    {
        // Match the pattern: SmartEnum("ClassName", ...)
        if (preg_match('/SmartEnum\s*\(\s*"([^"]+)"/', $fieldType, $matches)) {
            // The class name is double-escaped in the config, so unescape it
            return str_replace('\\\\', '\\', $matches[1]);
        }

        return null;
    }

    /**
     * Cast a value to a DBSmartDatetime.
     *
     * @param mixed $value
     * @return DBSmartDatetime|null
     */
    protected function castToSmartDatetime($value): ?DBSmartDatetime
    {
        if ($value === null || $value === '') {
            return null;
        }

        // If already a DBSmartDatetime, return it
        if ($value instanceof DBSmartDatetime) {
            return $value;
        }

        // Create a new DBSmartDatetime and set the value
        $field = DBSmartDatetime::create();
        try {
            $field->setValue($value);
            return $field;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Intercept field setting to provide smart type conversion.
     *
     * This is called before the value is set on the field.
     *
     * @param string $fieldName The name of the field being set
     * @param mixed $value The value being set
     * @return void
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // Get all DB fields
        $dbConfig = $this->owner->config()->get('db');

        if (!$dbConfig) {
            return;
        }

        foreach ($dbConfig as $fieldName => $fieldType) {
            // Skip if there's a custom mutator
            $mutatorMethod = 'set' . $fieldName;
            if (method_exists($this->owner, $mutatorMethod)) {
                continue;
            }

            // Check if this field has been changed
            if (!$this->owner->isChanged($fieldName)) {
                continue;
            }

            // Get the current value
            $value = $this->owner->getField($fieldName);

            // Handle DBJson fields - ensure they're stored as JSON strings
            if ($this->isFieldType($fieldType, DBJson::class)) {
                if (is_array($value)) {
                    $this->owner->setField($fieldName, json_encode($value));
                }
            }

            // Handle DBSmartEnum fields - ensure enum instances are converted to values
            if ($this->isFieldType($fieldType, DBSmartEnum::class)) {
                if ($value instanceof \BackedEnum) {
                    $this->owner->setField($fieldName, $value->value);
                }
            }

            // Handle DBSmartDatetime fields - ensure various formats are normalized
            if ($this->isFieldType($fieldType, DBSmartDatetime::class)) {
                if ($value instanceof \DateTimeInterface) {
                    $this->owner->setField($fieldName, $value->format('Y-m-d H:i:s'));
                } elseif (is_int($value) || (is_string($value) && ctype_digit($value))) {
                    $timestamp = (int) $value;
                    if ($timestamp >= 0 && $timestamp <= 4102444800) {
                        $dateTime = new \DateTime('@' . $timestamp);
                        $dateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                        $this->owner->setField($fieldName, $dateTime->format('Y-m-d H:i:s'));
                    }
                }
            }
        }
    }
}
