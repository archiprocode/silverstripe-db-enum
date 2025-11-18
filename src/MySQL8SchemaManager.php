<?php

namespace ArchiPro\Silverstripe\DBEnum;

use SilverStripe\ORM\Connect\MySQLSchemaManager;

/**
 * Extends the default MySQLSchemaManager to add support for JSON fields.
 *
 * This schema manager provides native MySQL 8+ JSON column support,
 * allowing DBJson fields to be properly created and managed in the database.
 *
 * The schema manager is automatically registered via YAML configuration,
 * but can be extended if you need custom database schema management:
 *
 * ```yaml
 * SilverStripe\Core\Injector\Injector:
 *   MySQLSchemaManager:
 *     class: App\Project\MyCustomSchemaManager
 * ```
 *
 * If extending this class, make sure to include the JsonDatabaseFieldDefinition trait:
 *
 * ```php
 * class MyCustomSchemaManager extends MySQLSchemaManager
 * {
 *     use JsonDatabaseFieldDefinition;
 * }
 * ```
 */
class MySQL8SchemaManager extends MySQLSchemaManager
{
    use JsonDatabaseFieldDefinition;
}
