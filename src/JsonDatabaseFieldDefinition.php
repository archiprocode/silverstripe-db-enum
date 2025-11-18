<?php

namespace ArchiPro\Silverstripe\DBEnum;

/**
 * Apply this trait to your Database Schema manager so it knows how to define JSON fields.
 *
 * This trait provides MySQL 8+ JSON column support for database schema managers.
 *
 * Example:
 * ```php
 * class MyCustomSchemaManager extends MySQLSchemaManager
 * {
 *     use JsonDatabaseFieldDefinition;
 * }
 * ```
 */
trait JsonDatabaseFieldDefinition
{
    /**
     * Define a JSON field for MySQL 8+.
     *
     * @param array<string,mixed> $values Field configuration values
     * @return string The SQL field definition
     */
    public function json($values): string
    {
        $definition = "JSON {$values['null']}";
        return $definition;
    }
}
