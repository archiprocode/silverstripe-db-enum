<?php

namespace ArchiPro\Silverstripe\DBEnum;

use SilverStripe\ORM\FieldType\DBEnum;

/**
 * Allows the creation of a DBEnum from a native PHP enum.
 *
 * This field type automatically extracts enum values from a PHP 8.1+ enum class
 * and creates a database enum field with those values.
 *
 * When registering your smart enum on your `$db` array, you need to double escape
 * the enum class name.
 *
 * Example:
 * ```php
 * private static $db = [
 *     'Status' => 'SmartEnum("My\\\\Namespace\\\\StatusEnum", "Pending")'
 * ];
 * ```
 *
 * @phpstan-type EnumClass class-string<\BackedEnum>
 */
class DBSmartEnum extends DBEnum
{
    /**
     * @var class-string<\BackedEnum>|null
     */
    protected $enumClass;

    /**
     * Constructor for DBSmartEnum.
     *
     * @param string|null $name The name of the field
     * @param class-string<\BackedEnum>|null $enumClass The fully qualified enum class name
     * @param string|int|\BackedEnum|null $default The default value
     * @param array<string,mixed> $options Additional options for the field
     */
    public function __construct($name = null, $enumClass = null, $default = null, $options = [])
    {
        $values = null;

        // Store the enum class for later use
        $this->enumClass = $enumClass;

        // Make sure the class is an enum.
        // SilverStripe will occasionally call the constructor with null values,
        // so we can't throw an exception here.
        if ($enumClass && enum_exists($enumClass)) {
            if (!is_subclass_of($enumClass, \BackedEnum::class)) {
                throw new \InvalidArgumentException(
                    sprintf('Enum class %s must be a BackedEnum', $enumClass)
                );
            }

            $enumReflection = new \ReflectionEnum($enumClass);
            $cases = $enumReflection->getCases();

            $values = array_map(
                function ($case) {
                    /** @var \ReflectionEnumBackedCase $case */
                    return $case->getBackingValue();
                },
                $cases
            );

            // Convert default enum to its value
            if ($default instanceof \BackedEnum) {
                $default = $default->value;
            }
        }

        parent::__construct($name, $values, $default, $options);
    }

    /**
     * Get the enum class associated with this field.
     *
     * @return class-string<\BackedEnum>|null
     */
    public function getEnumClass(): ?string
    {
        return $this->enumClass;
    }

    /**
     * Set the value, accepting either an enum instance, string, or int.
     *
     * @param \BackedEnum|string|int|null $value
     * @param array<string,mixed>|null $record
     * @param bool $markChanged
     * @return $this
     */
    public function setValue($value, $record = null, $markChanged = true)
    {
        // Convert enum to its value
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        return parent::setValue($value, $record, $markChanged);
    }

    /**
     * Get the value as an enum instance.
     *
     * @return \BackedEnum|null
     */
    public function getEnum(): ?\BackedEnum
    {
        $value = $this->getValue();

        if ($value === null || $value === '') {
            return null;
        }

        if (!$this->enumClass) {
            throw new \RuntimeException('No enum class is set for this field');
        }

        if (!enum_exists($this->enumClass)) {
            throw new \RuntimeException(
                sprintf('Enum class %s does not exist', $this->enumClass)
            );
        }

        try {
            /** @var \BackedEnum $enumClass */
            $enumClass = $this->enumClass;
            return $enumClass::from($value);
        } catch (\ValueError $e) {
            throw new \ValueError(
                sprintf(
                    'Value "%s" is not a valid case for enum %s',
                    $value,
                    $this->enumClass
                )
            );
        }
    }

    /**
     * Get the value - returns the enum instance if an enum class is set.
     *
     * @return \BackedEnum|string|null
     */
    public function getValue()
    {
        $value = parent::getValue();

        // If we have an enum class, return the enum instance
        if ($this->enumClass && $value !== null && $value !== '') {
            try {
                return $this->getEnum();
            } catch (\ValueError $e) {
                // If the value is invalid, just return the raw value
                return $value;
            }
        }

        return $value;
    }

    /**
     * Get all possible enum cases.
     *
     * @return array<\BackedEnum>
     */
    public function getEnumCases(): array
    {
        if (!$this->enumClass) {
            return [];
        }

        if (!enum_exists($this->enumClass)) {
            return [];
        }

        /** @var \BackedEnum $enumClass */
        $enumClass = $this->enumClass;
        return $enumClass::cases();
    }

    /**
     * Prepare value for database.
     *
     * @param \BackedEnum|string|int|null $value
     * @return string|null
     */
    public function prepValueForDB($value): ?string
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        return parent::prepValueForDB($value);
    }

    /**
     * Scaffold a dropdown field for this enum.
     *
     * @param string|null $title
     * @param array<string,mixed>|null $params
     * @return \SilverStripe\Forms\DropdownField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        $field = parent::scaffoldFormField($title, $params);

        // If we have an enum class with a name() method, use it for labels
        if ($this->enumClass && enum_exists($this->enumClass)) {
            $source = [];
            foreach ($this->getEnumCases() as $case) {
                $label = $case->value;

                // If the enum has a name() method or similar, we could use it
                // For now, just use the value as the label
                if (method_exists($case, 'label')) {
                    /** @var \BackedEnum&object{label():string} $case */
                    $label = $case->label();
                } elseif (method_exists($case, 'getLabel')) {
                    /** @var \BackedEnum&object{getLabel():string} $case */
                    $label = $case->getLabel();
                } elseif (property_exists($case, 'label')) {
                    /** @var \BackedEnum&object{label:string} $case */
                    $label = $case->label;
                }

                $source[$case->value] = $label;
            }

            $field->setSource($source);
        }

        return $field;
    }
}
