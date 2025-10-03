<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Database\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use UserFrosting\Sprinkle\Core\Database\Models\Model;
use UserFrosting\Sprinkle\CRUD6\Database\Models\Interfaces\CRUD6ModelInterface;

/**
 * CRUD6Model - Generic Eloquent Model.
 *
 * A generic model that can be dynamically configured to work with any database table
 * based on JSON schema configuration. This enables CRUD operations on any table
 * without requiring pre-defined model classes.
 *
 * Features:
 * - Dynamic table name assignment
 * - Dynamic database connection selection
 * - Dynamic fillable attributes configuration
 * - Schema-based field casting
 * - Soft delete support when enabled in schema
 * - Timestamp management based on schema configuration
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class CRUD6Model extends Model implements CRUD6ModelInterface
{
    use HasFactory;

    /**
     * @var string The name of the table for the current model.
     * Default value indicates the table has not been set yet.
     */
    protected $table = 'CRUD6_NOT_SET';

    /**
     * @var string[] The attributes that are mass assignable.
     * Will be populated dynamically based on schema configuration.
     */
    protected $fillable = [];

    /**
     * @var string[] The attributes that should be cast.
     * Will be populated dynamically based on schema field types.
     */
    protected $casts = [];

    /**
     * @var bool Enable/disable timestamps based on schema configuration.
     */
    public $timestamps = false;

    /**
     * @var string|null The name of the "deleted at" column for soft deletes.
     */
    protected $deleted_at = null;

    /**
     * Configure the model with schema information
     *
     * @param array $schema The JSON schema configuration
     * @return static
     */
    public function configureFromSchema(array $schema): static
    {
        // Set table name
        if (isset($schema['table'])) {
            $this->table = $schema['table'];
        }

        // Configure database connection
        // Use connection from schema if specified, otherwise use null for default connection
        $this->setConnection($schema['connection'] ?? null);

        // Configure timestamps
        $this->timestamps = $schema['timestamps'] ?? false;

        // Configure soft deletes
        if ($schema['soft_delete'] ?? false) {
            $this->deleted_at = 'deleted_at';
        }

        // Set fillable attributes and casts based on schema fields
        $this->configureFillableAndCasts($schema);

        return $this;
    }

    /**
     * Set the table name for this model instance
     *
     * @param string $table
     * @return $this
     */
    public function setTable($table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the fillable attributes
     *
     * @param array $fillable
     * @return static
     */
    public function setFillable(array $fillable): static
    {
        $this->fillable = $fillable;
        return $this;
    }

    /**
     * Get the fillable attributes
     *
     * @return array
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * Set the cast attributes
     *
     * @param array $casts
     * @return static
     */
    public function setCasts(array $casts): static
    {
        $this->casts = array_merge($this->casts, $casts);
        return $this;
    }

    /**
     * Set the database connection for this model instance
     *
     * This method overrides the parent Eloquent Model::setConnection to ensure
     * compatibility with the CRUD6ModelInterface signature which requires
     * a static return type.
     *
     * @param string|null $connection The connection name, or null for default
     * @return static
     */
    public function setConnection($connection): static
    {
        parent::setConnection($connection);
        return $this;
    }

    /**
     * Configure fillable attributes and casts based on schema
     *
     * @param array $schema
     */
    protected function configureFillableAndCasts(array $schema): void
    {
        $fillable = [];
        $casts = [];

        if (isset($schema['fields']) && is_array($schema['fields'])) {
            foreach ($schema['fields'] as $fieldName => $fieldConfig) {
                // Skip auto-increment fields from fillable
                if (
                    !($fieldConfig['auto_increment'] ?? false) &&
                    !($fieldConfig['readonly'] ?? false)
                ) {
                    $fillable[] = $fieldName;
                }

                // Configure casts based on field type
                $fieldType = $fieldConfig['type'] ?? 'string';
                $cast = $this->mapFieldTypeToCast($fieldType);
                if ($cast !== null) {
                    $casts[$fieldName] = $cast;
                }
            }
        }

        $this->fillable = $fillable;
        $this->casts = array_merge($this->casts, $casts);
    }

    /**
     * Map schema field types to Eloquent cast types
     *
     * @param string $fieldType
     * @return string|null
     */
    protected function mapFieldTypeToCast(string $fieldType): ?string
    {
        return match ($fieldType) {
            'integer' => 'integer',
            'float', 'decimal' => 'float',
            'boolean' => 'boolean',
            'json' => 'array',
            'date' => 'date',
            'datetime' => 'datetime',
            default => null, // String types don't need explicit casting
        };
    }

    /**
     * Apply soft delete filter to query if enabled
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithoutSoftDeleted(Builder $query): Builder
    {
        if ($this->deleted_at) {
            return $query->whereNull($this->getDeletedAtColumn());
        }

        return $query;
    }

    /**
     * Apply soft delete filter to include only deleted records
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOnlySoftDeleted(Builder $query): Builder
    {
        if ($this->deleted_at) {
            return $query->whereNotNull($this->getDeletedAtColumn());
        }

        return $query;
    }

    /**
     * Apply soft delete filter to include all records (deleted and non-deleted)
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithSoftDeleted(Builder $query): Builder
    {
        // No additional where clause needed - shows all records
        return $query;
    }

    /**
     * Soft delete this model instance
     *
     * @return bool
     */
    public function softDelete(): bool
    {
        if ($this->deleted_at) {
            $this->{$this->getDeletedAtColumn()} = now();
            return $this->save();
        }

        return false;
    }

    /**
     * Restore a soft deleted model instance
     *
     * @return bool
     */
    public function restore(): bool
    {
        if ($this->deleted_at) {
            $this->{$this->getDeletedAtColumn()} = null;
            return $this->save();
        }

        return false;
    }

    /**
     * Check if this model instance is soft deleted
     *
     * @return bool
     */
    public function isSoftDeleted(): bool
    {
        if (!$this->deleted_at) {
            return false;
        }

        return !is_null($this->{$this->getDeletedAtColumn()});
    }

    /**
     * Create a new factory instance for the model.
     * Since this is a generic model, we'll use a base factory approach.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory|null
     */
    protected static function newFactory()
    {
        // For a generic model, factories would need to be created per table/schema
        // This can be extended in the future to support dynamic factory creation
        return null;
    }

    /**
     * Override the default query to apply soft delete filters automatically
     *
     * @return Builder
     */
    public function newQuery(): Builder
    {
        $query = parent::newQuery();

        // Automatically apply soft delete filter if enabled (but only if deleted_at column exists)
        if ($this->deleted_at) {
            $query->whereNull($this->getDeletedAtColumn());
        }

        return $query;
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn(): string
    {
        return $this->deleted_at ?? 'deleted_at';
    }
}
