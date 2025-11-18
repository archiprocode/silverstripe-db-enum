<?php

namespace ArchiPro\Silverstripe\DBEnum;

use SilverStripe\ORM\DataQuery;
use InvalidArgumentException;
use SilverStripe\ORM\Filters\SearchFilter;

/**
 * Filter for searching JSON columns using MySQL's JSON_CONTAINS function.
 *
 * This filter allows you to search through JSON data stored in DBJson fields
 * using MySQL 8's native JSON functions.
 *
 * Usage:
 * ```php
 * // Find objects where the Author field in the meta object is 'John Doe'
 * $list = MyDataObject::get()->filter('Payload:Json', ['$.meta.Author' => 'John Doe']);
 *
 * // Find objects where the meta object contains specific fields
 * $list = MyDataObject::get()->filter(
 *     'Payload:Json',
 *     ['$.meta' => ['Title' => 'My page', 'Created' => '2024-01-01']]
 * );
 *
 * // Exclude objects with specific JSON values
 * $list = MyDataObject::get()->filter('Payload:Json:not', ['$.meta.Author' => 'John Doe']);
 * ```
 *
 * The filter uses MySQL's JSON path expressions (e.g., '$.meta.Author') to navigate
 * through nested JSON structures. See https://dev.mysql.com/doc/refman/8.4/en/json-search-functions.html
 * for more information on JSON_CONTAINS and path expressions.
 *
 * For more complex queries, you can use the where() method directly:
 * ```php
 * $list = MyDataObject::get()->where([
 *     'JSON_EXTRACT("Payload", ?) > ?' => ['$.price', 100],
 * ]);
 * ```
 */
class JsonFilter extends SearchFilter
{
    /**
     * Get the list of supported modifiers for this filter.
     *
     * @return array<string>
     */
    public function getSupportedModifiers(): array
    {
        return ['not'];
    }

    /**
     * Apply filter criteria to a SQL query.
     *
     * @param DataQuery $query The query to apply the filter to
     * @return DataQuery The modified query
     * @throws InvalidArgumentException If used with aggregate functions
     */
    public function apply(DataQuery $query): DataQuery
    {
        if ($this->aggregate) {
            throw new InvalidArgumentException(sprintf(
                'Aggregate functions can only be used with comparison filters. See %s',
                $this->fullName
            ));
        }

        return parent::apply($query);
    }

    /**
     * Apply filter for a single value.
     *
     * Note: This filter requires an array of path => value mappings,
     * so applyOne is not supported.
     *
     * @param DataQuery $query
     * @throws \RuntimeException Always throws as single value filtering is not supported
     */
    protected function applyOne(DataQuery $query): void
    {
        throw new \RuntimeException('JsonFilter requires an array of path => value mappings. Use applyMany instead.');
    }

    /**
     * Apply filter for multiple values using JSON_CONTAINS.
     *
     * Filters JSON columns to find documents containing specific values at specified paths.
     *
     * @param DataQuery $query The query to apply the filter to
     * @return DataQuery The modified query
     * @throws InvalidArgumentException If values are empty or not an array
     */
    protected function applyMany(DataQuery $query): DataQuery
    {
        $this->model = $query->applyRelation($this->relation);
        $field = $this->getDbName();
        $values = $this->getValue();

        if (empty($values)) {
            throw new InvalidArgumentException(sprintf(
                'Filtering by an empty array is not supported. See %s',
                $this->fullName
            ));
        }

        if (!is_array($values)) {
            throw new InvalidArgumentException(sprintf(
                'Must provide value as array when filtering by JSON_CONTAINS. See %s',
                $this->fullName
            ));
        }

        $whereClause = sprintf('JSON_CONTAINS(%s, ?, ?)', $field);
        $wheres = [];

        foreach ($values as $path => $value) {
            $wheres[] = [$whereClause => [json_encode($value), $path]];
        }

        return $query->whereAny($wheres);
    }

    /**
     * Exclude filter for a single value.
     *
     * Note: This filter requires an array of path => value mappings,
     * so excludeOne is not supported.
     *
     * @param DataQuery $query
     * @throws \RuntimeException Always throws as single value exclusion is not supported
     */
    protected function excludeOne(DataQuery $query): void
    {
        throw new \RuntimeException('JsonFilter requires an array of path => value mappings. Use excludeMany instead.');
    }

    /**
     * Exclude filter for multiple values using NOT JSON_CONTAINS.
     *
     * Filters JSON columns to exclude documents containing specific values at specified paths.
     *
     * @param DataQuery $query The query to apply the exclusion to
     * @return DataQuery The modified query
     * @throws InvalidArgumentException If values are empty or not an array
     */
    protected function excludeMany(DataQuery $query): DataQuery
    {
        $this->model = $query->applyRelation($this->relation);
        $field = $this->getDbName();
        $values = $this->getValue();

        if (empty($values)) {
            throw new InvalidArgumentException(sprintf(
                'Filtering by an empty array is not supported. See %s',
                $this->fullName
            ));
        }

        if (!is_array($values)) {
            throw new InvalidArgumentException(sprintf(
                'Must provide value as array when filtering by JSON_CONTAINS. See %s',
                $this->fullName
            ));
        }

        $whereClause = sprintf('NOT JSON_CONTAINS(%s, ?, ?)', $field);
        $wheres = [];

        foreach ($values as $path => $value) {
            $wheres[] = [$whereClause => [json_encode($value), $path]];
        }

        return $query->where($wheres);
    }

    /**
     * Check if the filter value is empty.
     *
     * @return bool True if the filter value is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->getValue());
    }
}
