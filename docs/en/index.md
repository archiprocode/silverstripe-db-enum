# SilverStripe Smart DB Types Documentation

Welcome to the SilverStripe Smart DB Types module documentation.

## Table of Contents

1. [Installation](installation.md)
2. [DBJson](dbjson.md) - JSON field type
3. [DBSmartEnum](dbsmartenum.md) - PHP Enum field type
4. [DBSmartDatetime](dbsmartdatetime.md) - Smart datetime field type
5. [SmartFieldsExtension](smartfieldsextension.md) - Automatic type casting
6. [Examples](examples.md) - Real-world usage examples

## Quick Links

- [GitHub Repository](https://github.com/archiprocode/silverstripe-db-enum)
- [Issue Tracker](https://github.com/archiprocode/silverstripe-db-enum/issues)
- [Changelog](../../CHANGELOG.md)

## Overview

This module provides three smart database field types for SilverStripe CMS:

### DBJson

Store and query complex data structures using MySQL 8+ native JSON columns.

**Features:**
- Native MySQL 8 JSON column creation via MySQL8SchemaManager
- Helper methods for manipulating JSON data
- JsonFilter for querying JSON columns using MySQL's JSON_CONTAINS function
- Support for nested object filtering

### DBSmartEnum

Use PHP 8.1+ enums as database fields with automatic type casting and validation.

**Features:**
- Automatic enum value extraction from BackedEnum classes
- Bi-directional conversion between enum instances and database values
- Custom label support for form fields

### DBSmartDatetime

Intelligent datetime field that accepts multiple input formats (timestamps, strings, DateTime objects) and provides helper methods.

**Features:**
- Multiple input format support (timestamps, ISO 8601, SQL datetime, relative dates)
- Helper methods for date comparison and manipulation
- Human-readable relative time output

All three types work seamlessly with the included SmartFieldsExtension, which provides automatic type casting when getting and setting field values on DataObjects.
