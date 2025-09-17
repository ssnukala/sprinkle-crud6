<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Sprunje;

use UserFrosting\Sprinkle\Core\Sprunje\Sprunje;
use UserFrosting\Sprinkle\Core\Database\Connection;
use Illuminate\Database\Query\Builder;

/**
 * CRUD6 Sprunje
 * 
 * Generic sprunje for handling data queries on any table based on schema configuration.
 * Supports dynamic sorting, filtering, and pagination.
 */
class CRUD6Sprunje extends Sprunje
{
    protected string $tableName = '';
    protected array $schema = [];
    
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Setup the sprunje with table and schema configuration
     */
    public function setupSprunje(string $tableName, array $sortable, array $filterable, array $schema): void
    {
        $this->tableName = $tableName;
        $this->schema = $schema;
        
        // Set sortable fields
        $this->sortable = $sortable;
        
        // Set filterable fields
        $this->filterable = $filterable;
        
        // Set default sort if specified in schema
        if (isset($schema['default_sort'])) {
            $this->sorts = $schema['default_sort'];
        }
    }

    /**
     * Get the base query builder
     */
    protected function baseQuery(): Builder
    {
        if (empty($this->tableName)) {
            throw new \RuntimeException('Table name not set. Call setupSprunje() first.');
        }
        
        $query = $this->db->table($this->tableName);
        
        // Apply soft delete filter if enabled
        if ($this->schema['soft_delete'] ?? false) {
            $query->whereNull('deleted_at');
        }
        
        return $query;
    }

    /**
     * Apply custom filters based on schema configuration
     */
    protected function applyFilters(Builder $query): Builder
    {
        // Apply global search if configured
        if (isset($this->filters['search']) && !empty($this->filters['search'])) {
            $searchTerm = $this->filters['search'];
            $searchableFields = $this->getSearchableFields();
            
            if (!empty($searchableFields)) {
                $query->where(function ($q) use ($searchableFields, $searchTerm) {
                    foreach ($searchableFields as $field) {
                        $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                    }
                });
            }
        }
        
        // Apply individual field filters
        foreach ($this->filterable as $field) {
            if (isset($this->filters[$field]) && $this->filters[$field] !== '') {
                $value = $this->filters[$field];
                $fieldConfig = $this->schema['fields'][$field] ?? [];
                
                // Apply filter based on field type
                $this->applyFieldFilter($query, $field, $value, $fieldConfig);
            }
        }
        
        return $query;
    }

    /**
     * Apply filter for a specific field based on its type
     */
    protected function applyFieldFilter(Builder $query, string $field, $value, array $fieldConfig): void
    {
        $type = $fieldConfig['type'] ?? 'string';
        $filterType = $fieldConfig['filter_type'] ?? 'equals';
        
        switch ($filterType) {
            case 'like':
                $query->where($field, 'LIKE', "%{$value}%");
                break;
                
            case 'starts_with':
                $query->where($field, 'LIKE', "{$value}%");
                break;
                
            case 'ends_with':
                $query->where($field, 'LIKE', "%{$value}");
                break;
                
            case 'in':
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereIn($field, $values);
                break;
                
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($field, $value);
                }
                break;
                
            case 'greater_than':
                $query->where($field, '>', $value);
                break;
                
            case 'less_than':
                $query->where($field, '<', $value);
                break;
                
            case 'not_equals':
                $query->where($field, '!=', $value);
                break;
                
            default: // equals
                $query->where($field, $value);
                break;
        }
    }

    /**
     * Get searchable fields from schema
     */
    protected function getSearchableFields(): array
    {
        $searchable = [];
        
        foreach ($this->schema['fields'] as $fieldName => $fieldConfig) {
            if ($fieldConfig['searchable'] ?? false) {
                $searchable[] = $fieldName;
            }
        }
        
        return $searchable;
    }

    /**
     * Apply custom sorting logic if needed
     */
    protected function applySorts(Builder $query): Builder
    {
        foreach ($this->sorts as $field => $direction) {
            if (in_array($field, $this->sortable)) {
                $fieldConfig = $this->schema['fields'][$field] ?? [];
                
                // Handle custom sort logic if defined
                if (isset($fieldConfig['sort_column'])) {
                    $query->orderBy($fieldConfig['sort_column'], $direction);
                } else {
                    $query->orderBy($field, $direction);
                }
            }
        }
        
        return $query;
    }

    /**
     * Transform results before sending to client
     */
    protected function transformResults(array $results): array
    {
        $transformed = [];
        
        foreach ($results as $row) {
            $transformedRow = [];
            $rowArray = (array) $row;
            
            foreach ($rowArray as $field => $value) {
                $fieldConfig = $this->schema['fields'][$field] ?? [];
                $transformedRow[$field] = $this->transformFieldValue($fieldConfig, $value);
            }
            
            $transformed[] = $transformedRow;
        }
        
        return $transformed;
    }

    /**
     * Transform field value for display
     */
    protected function transformFieldValue(array $fieldConfig, $value)
    {
        if ($value === null) {
            return null;
        }
        
        $type = $fieldConfig['type'] ?? 'string';
        
        switch ($type) {
            case 'boolean':
                return (bool) $value;
                
            case 'integer':
                return (int) $value;
                
            case 'float':
            case 'decimal':
                return (float) $value;
                
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
                
            case 'date':
            case 'datetime':
                // Format date if formatter is specified
                if (isset($fieldConfig['date_format'])) {
                    $date = new \DateTime($value);
                    return $date->format($fieldConfig['date_format']);
                }
                return $value;
                
            default:
                return (string) $value;
        }
    }
}