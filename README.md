# SilverStripe Smart DB Types

[![CI](https://github.com/archiprocode/silverstripe-db-enum/actions/workflows/ci.yml/badge.svg)](https://github.com/archiprocode/silverstripe-db-enum/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](LICENSE)

A comprehensive SilverStripe CMS module providing smart database field types with automatic type casting and intelligent value conversion.

## Features

- **DBJson**: Store and query JSON data using native MySQL 8+ JSON columns
- **DBSmartEnum**: Use PHP 8.1+ enums as database enum fields with automatic casting
- **DBSmartDatetime**: Intelligent datetime field that accepts timestamps, strings, and DateTime objects
- **SmartFieldsExtension**: Automatic type casting when accessing fields on DataObjects

## Requirements

- PHP 8.1 or higher
- SilverStripe Framework 4.13+ or 5.0+
- MySQL 8.0+ (for DBJson support)

## Installation

```bash
composer require archipro/silverstripe-db-enum
```

## Quick Start

### DBJson - JSON Field Type

Store complex data structures in native MySQL JSON columns:

```php
use ArchiPro\Silverstripe\DBEnum\DBJson;
use SilverStripe\ORM\DataObject;

class Product extends DataObject
{
    private static $db = [
        'Settings' => DBJson::class,
        'Metadata' => DBJson::class,
    ];
}

// Usage
$product = Product::create();
$product->Settings = [
    'enabled' => true,
    'features' => ['feature1', 'feature2'],
    'config' => [
        'debug' => false,
        'cache' => true,
    ],
];
$product->write();

// Access as array
$enabled = $product->Settings['enabled'];

// Or use helper methods
$product->Settings->setKey('newSetting', 'value');
$value = $product->Settings->getKey('enabled');
```

### DBSmartEnum - PHP Enum Support

Use native PHP enums as database fields:

```php
use ArchiPro\Silverstripe\DBEnum\DBSmartEnum;

// Define your enum
enum Status: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Completed = 'completed';
}

class Task extends DataObject
{
    private static $db = [
        // Note: Double-escape the namespace
        'Status' => 'SmartEnum("App\\\\Enum\\\\Status", "pending")',
    ];
}

// Usage - accepts both enum instances and strings
$task = Task::create();
$task->Status = Status::Active;  // Enum instance
$task->write();

// Returns enum instance when accessed
if ($task->Status === Status::Active) {
    // Do something
}

// Also accepts strings
$task->Status = 'completed';
$task->write();
```

### DBSmartDatetime - Intelligent DateTime Field

Accepts multiple date/time formats and provides helper methods:

```php
use ArchiPro\Silverstripe\DBEnum\DBSmartDatetime;

class Event extends DataObject
{
    private static $db = [
        'PublishDate' => DBSmartDatetime::class,
        'ExpiryDate' => DBSmartDatetime::class,
    ];
}

// Usage - accepts many formats
$event = Event::create();

$event->PublishDate = time();  // Unix timestamp
$event->PublishDate = 1705318200;  // Unix timestamp as int
$event->PublishDate = '2024-01-15 10:30:00';  // SQL datetime string
$event->PublishDate = '2024-01-15T10:30:00Z';  // ISO 8601
$event->PublishDate = new DateTime('tomorrow');  // DateTime object
$event->PublishDate = 'next Monday';  // Relative date string

// Helper methods
if ($event->PublishDate->isPast()) {
    echo "Published " . $event->PublishDate->getRelativeTime();
}

if ($event->ExpiryDate->isFuture()) {
    echo "Expires on " . $event->ExpiryDate->format('Y-m-d');
}

// Date manipulation
$event->ExpiryDate->add('7 days');
$event->ExpiryDate->sub('2 hours');
```

## Automatic Type Casting

The module automatically applies `SmartFieldsExtension` to all DataObjects, providing seamless type casting:

```php
class Article extends DataObject
{
    private static $db = [
        'Settings' => DBJson::class,
        'Status' => 'SmartEnum("App\\\\Enum\\\\Status", "pending")',
        'PublishDate' => DBSmartDatetime::class,
    ];
}

$article = Article::create();

// Set with various types - automatically converted
$article->Settings = ['key' => 'value'];  // Array
$article->Status = Status::Active;  // Enum instance
$article->PublishDate = time();  // Timestamp

$article->write();

// Get returns properly typed values
$settings = $article->Settings;  // Returns array
$status = $article->Status;  // Returns Status enum instance
$date = $article->PublishDate;  // Returns DBSmartDatetime instance

// Type-safe comparisons
if ($article->Status === Status::Active) {
    // Do something
}
```

## Advanced Usage

### DBJson Advanced Features

```php
// Check if a key exists
if ($product->Settings->hasKey('features')) {
    // ...
}

// Remove a key
$product->Settings->removeKey('oldSetting');

// Get/set individual keys
$product->Settings->setKey('debug', true);
$debug = $product->Settings->getKey('debug');

// Check if field has any data
if ($product->Settings->exists()) {
    // ...
}
```

### DBSmartEnum with Custom Labels

```php
enum Priority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;

    public function label(): string
    {
        return match($this) {
            self::Low => 'Low Priority',
            self::Medium => 'Medium Priority',
            self::High => 'High Priority',
            self::Critical => 'Critical Priority',
        };
    }
}

// The scaffolded form field will use the label() method if available
$field = $task->dbObject('Priority')->scaffoldFormField();
// Dropdown will show "Low Priority", "Medium Priority", etc.
```

### DBSmartDatetime Helper Methods

```php
// Check date status
$date->isPast();     // Is the date in the past?
$date->isFuture();   // Is the date in the future?
$date->isToday();    // Is the date today?

// Get relative time
$date->getRelativeTime();  // "2 hours ago", "in 3 days", etc.

// Format dates
$date->format('Y-m-d H:i:s');
$date->format('d/m/Y');

// Get as timestamp
$timestamp = $date->getTimestamp();

// Get as DateTime
$dateTime = $date->getDateTime();

// Manipulate dates
$date->add('1 week');
$date->add(new DateInterval('P1D'));
$date->sub('2 hours');
```

## Custom Accessors and Mutators

The SmartFieldsExtension respects custom accessors and mutators:

```php
class Article extends DataObject
{
    private static $db = [
        'Status' => 'SmartEnum("App\\\\Enum\\\\Status", "pending")',
    ];

    // Custom accessor - extension will not interfere
    public function getStatus(): Status
    {
        $value = $this->getField('Status');
        // Your custom logic here
        return Status::from($value);
    }

    // Custom mutator - extension will not interfere
    public function setStatus(Status|string $status): self
    {
        if (is_string($status)) {
            $status = Status::from($status);
        }
        // Your custom logic here
        return $this->setField('Status', $status->value);
    }
}
```

## Configuration

The module is configured to work out of the box. However, you can customize it:

### Disable Automatic Extension

If you want to manually control which DataObjects get the SmartFieldsExtension:

```yaml
# Remove from all DataObjects
SilverStripe\ORM\DataObject:
  extensions: []

# Apply to specific classes only
App\Model\MyDataObject:
  extensions:
    - ArchiPro\Silverstripe\DBEnum\SmartFieldsExtension
```

## Testing

Run tests with:

```bash
vendor/bin/phpunit
```

Run PHPStan analysis:

```bash
vendor/bin/phpstan analyse
```

Run code sniffer:

```bash
vendor/bin/phpcs src tests
```

## License

This module is released under the [BSD 3-Clause License](LICENSE).

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Add tests for your changes
4. Ensure all tests pass and PHPStan analysis succeeds
5. Submit a pull request

## Credits

Developed by [ArchiPro](https://github.com/archiprocode).
