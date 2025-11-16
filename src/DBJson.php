<?php

namespace ArchiPro\Silverstripe\DBEnum;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\DB;

/**
 * A database field type for storing JSON data in MySQL 8+ JSON columns.
 *
 * This field type provides efficient JSON storage and querying capabilities
 * using native MySQL JSON column types.
 *
 * Example usage:
 * ```php
 * private static $db = [
 *     'Settings' => DBJson::class,
 *     'Metadata' => DBJson::class,
 * ];
 * ```
 *
 * @phpstan-type JsonValue scalar|array<mixed>|null
 */
class DBJson extends DBField
{
    /**
     * @var array<mixed>|null
     */
    protected $value = null;

    /**
     * Returns the field type for the database.
     *
     * @param string|null $values Not used for JSON fields
     * @return string
     */
    public function requireField(): string
    {
        $parts = [
            'datatype' => 'json',
            'arrayValue' => $this->arrayValue
        ];

        $values = [
            'type' => 'json',
            'parts' => $parts
        ];

        DB::require_field($this->tableName, $this->name, $values);
        return '';
    }

    /**
     * Set the value of this field.
     *
     * @param mixed $value Can be an array, a JSON string, or null
     * @param array<string,mixed>|null $record Not used
     * @param bool $markChanged Whether to mark the field as changed
     * @return $this
     */
    public function setValue($value, $record = null, $markChanged = true)
    {
        if ($value === null || $value === '') {
            $this->value = null;
        } elseif (is_string($value)) {
            // Decode JSON string
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid JSON string: %s', json_last_error_msg())
                );
            }
            $this->value = $decoded;
        } elseif (is_array($value)) {
            $this->value = $value;
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'DBJson field expects an array, JSON string, or null. Got: %s',
                    gettype($value)
                )
            );
        }

        if ($markChanged) {
            $this->isChanged = true;
        }

        return $this;
    }

    /**
     * Get the value as an array.
     *
     * @return array<mixed>|null
     */
    public function getValue(): ?array
    {
        return $this->value;
    }

    /**
     * Prepare the value for writing to the database.
     *
     * @return string|null JSON encoded string
     */
    public function prepValueForDB($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            // Validate it's valid JSON
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid JSON string: %s', json_last_error_msg())
                );
            }
            return $value;
        }

        if (is_array($value)) {
            $encoded = json_encode($value, JSON_THROW_ON_ERROR);
            if ($encoded === false) {
                throw new \InvalidArgumentException('Failed to encode value as JSON');
            }
            return $encoded;
        }

        throw new \InvalidArgumentException(
            sprintf(
                'DBJson field expects an array, JSON string, or null for DB storage. Got: %s',
                gettype($value)
            )
        );
    }

    /**
     * Check if the field exists in the database.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->value !== null && !empty($this->value);
    }

    /**
     * Get a specific key from the JSON data.
     *
     * @param string $key The key to retrieve
     * @return mixed|null
     */
    public function getKey(string $key)
    {
        if (!is_array($this->value)) {
            return null;
        }

        return $this->value[$key] ?? null;
    }

    /**
     * Set a specific key in the JSON data.
     *
     * @param string $key The key to set
     * @param mixed $value The value to set
     * @return $this
     */
    public function setKey(string $key, $value): self
    {
        if (!is_array($this->value)) {
            $this->value = [];
        }

        $this->value[$key] = $value;
        $this->isChanged = true;

        return $this;
    }

    /**
     * Remove a key from the JSON data.
     *
     * @param string $key The key to remove
     * @return $this
     */
    public function removeKey(string $key): self
    {
        if (is_array($this->value) && isset($this->value[$key])) {
            unset($this->value[$key]);
            $this->isChanged = true;
        }

        return $this;
    }

    /**
     * Check if a key exists in the JSON data.
     *
     * @param string $key The key to check
     * @return bool
     */
    public function hasKey(string $key): bool
    {
        return is_array($this->value) && array_key_exists($key, $this->value);
    }

    /**
     * Get the JSON data as a string.
     *
     * @return string
     */
    public function forTemplate(): string
    {
        if ($this->value === null) {
            return '';
        }

        return (string) json_encode($this->value, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * Get the raw JSON string suitable for saving.
     *
     * @return string|null
     */
    public function saveInto($dataObject)
    {
        $fieldName = $this->name;
        if ($fieldName) {
            $dataObject->__set(
                $fieldName,
                $this->value !== null ? json_encode($this->value, JSON_THROW_ON_ERROR) : null
            );
        }
    }

    /**
     * Get a JSON-serializable representation of this field.
     *
     * @return array<mixed>|null
     */
    public function jsonSerialize(): ?array
    {
        return $this->value;
    }

    /**
     * Scaffold a form field for this field type.
     *
     * @param string|null $title
     * @param array<string,mixed>|null $params
     * @return \SilverStripe\Forms\TextareaField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        return \SilverStripe\Forms\TextareaField::create($this->name, $title)
            ->setDescription('Enter valid JSON data')
            ->setRows(10);
    }
}
