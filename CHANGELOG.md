# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **DBJson**: Native MySQL 8+ JSON column support with helper methods
  - `setValue()` accepts arrays and JSON strings
  - `getValue()` returns arrays
  - Helper methods: `getKey()`, `setKey()`, `removeKey()`, `hasKey()`, `exists()`
  - Automatic JSON encoding/decoding

- **DBSmartEnum**: PHP 8.1+ enum support as database fields
  - Automatic enum value extraction from BackedEnum classes
  - Accepts both enum instances and string/int values
  - Returns enum instances when accessing fields
  - Support for custom labels via `label()` or `getLabel()` methods
  - Scaffolds dropdown form fields with proper labels

- **DBSmartDatetime**: Intelligent datetime field with multiple input formats
  - Accepts Unix timestamps (int)
  - Accepts ISO 8601 strings
  - Accepts SQL datetime strings
  - Accepts DateTime/DateTimeInterface objects
  - Accepts relative date strings (e.g., "tomorrow", "+1 week")
  - Helper methods: `isPast()`, `isFuture()`, `isToday()`, `getRelativeTime()`
  - Format conversion: `format()`, `getTimestamp()`, `getDateTime()`
  - Date manipulation: `add()`, `sub()`

- **SmartFieldsExtension**: Automatic type casting for all smart field types
  - Applied to all DataObjects by default
  - Respects custom accessors and mutators
  - Automatically casts DBJson fields to/from arrays
  - Automatically casts DBSmartEnum fields to/from enum instances
  - Automatically casts DBSmartDatetime fields to/from various formats

- **Comprehensive test suite** with 100% code coverage
- **PHPStan level 8** static analysis configuration with [silverstan](https://github.com/Cambis/silverstan) for SilverStripe-specific type checking
- **Full documentation** with examples and API reference
- **GitHub Actions CI pipeline** with:
  - Multi-version PHP testing (8.1, 8.2, 8.3)
  - Multi-version SilverStripe testing (4.13, 5.0)
  - PHPStan static analysis
  - PHP_CodeSniffer code style checks
  - Code coverage reporting

### Requirements
- PHP 8.1 or higher
- SilverStripe Framework 4.13+ or 5.0+
- MySQL 8.0+ (for DBJson support)

[Unreleased]: https://github.com/archiprocode/silverstripe-db-enum/compare/main...HEAD
