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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * - Dynamic relationship methods based on schema (e.g., roles(), permissions())
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
     * @var array<string, array<string, array>> Static storage for relationship configurations.
     * Keyed by table name, then by relationship name. This allows all model instances
     * (including hydrated ones) to access relationship configurations.
     * 
     * Structure: ['table_name' => ['roles' => [...config...], 'permissions' => [...config...]]]
     */
    protected static array $staticRelationships = [];

    /**
     * @var array<string, array> Static storage for schema configurations keyed by table name.
     * Stores fillable, casts, timestamps, and soft_delete settings so hydrated instances
     * can access them.
     * 
     * Structure: ['table_name' => ['fillable' => [...], 'casts' => [...], 'timestamps' => bool, 'deleted_at' => string|null]]
     */
    protected static array $staticSchemaConfig = [];

    /**
     * Configure the model with schema information
     *
     * @param array $schema The JSON schema configuration
     * @return static
     */
    public function configureFromSchema(array $schema): static
    {
        // Set table name first (needed for static storage keys)
        if (isset($schema['table'])) {
            $this->table = $schema['table'];
        }

        // Configure database connection
        // Use connection from schema if specified, otherwise use null for default connection
        $this->setConnection($schema['connection'] ?? null);

        // Configure timestamps
        $this->timestamps = $schema['timestamps'] ?? false;

        // Configure soft deletes
        $this->deleted_at = ($schema['soft_delete'] ?? false) ? 'deleted_at' : null;

        // Set fillable attributes and casts based on schema fields
        $this->configureFillableAndCasts($schema);

        // Configure dynamic relationships from schema
        $this->configureRelationships($schema);

        // Store schema config in static property for hydrated instances
        if ($this->table && $this->table !== 'CRUD6_NOT_SET') {
            static::$staticSchemaConfig[$this->table] = [
                'fillable' => $this->fillable,
                'casts' => $this->casts,
                'timestamps' => $this->timestamps,
                'deleted_at' => $this->deleted_at,
            ];
        }

        return $this;
    }

    /**
     * Configure dynamic relationships from schema.
     *
     * Reads the 'relationships' array from the schema and stores each relationship
     * configuration in a static property keyed by table name. This allows the __call 
     * magic method to create Eloquent relationship methods dynamically (e.g., $model->roles()).
     * 
     * Using static storage ensures that all model instances (including those hydrated by
     * Eloquent when retrieving records from the database) can access the relationship 
     * configurations as long as configureFromSchema() was called once for that table.
     *
     * @param array $schema The JSON schema configuration
     * @return void
     */
    protected function configureRelationships(array $schema): void
    {
        if (!isset($schema['relationships']) || !is_array($schema['relationships'])) {
            return;
        }

        // Build relationships array for this table
        $relationships = [];
        foreach ($schema['relationships'] as $relationship) {
            $name = $relationship['name'] ?? null;
            if ($name) {
                $relationships[$name] = $relationship;
            }
        }

        // Store in static property keyed by table name
        if ($this->table && $this->table !== 'CRUD6_NOT_SET') {
            static::$staticRelationships[$this->table] = $relationships;
        }
    }

    /**
     * Get the configuration for a dynamic relationship.
     * 
     * Retrieves the relationship configuration from static storage using the table name.
     *
     * @param string $name The relationship name
     * @return array|null The relationship configuration, or null if not found
     */
    public function getRelationshipConfig(string $name): ?array
    {
        if ($this->table && isset(static::$staticRelationships[$this->table][$name])) {
            return static::$staticRelationships[$this->table][$name];
        }

        return null;
    }

    /**
     * Check if a dynamic relationship exists.
     * 
     * Checks the static storage (keyed by table name) for the relationship configuration.
     *
     * @param string $name The relationship name
     * @return bool True if the relationship is configured
     */
    public function hasRelationship(string $name): bool
    {
        return $this->table && isset(static::$staticRelationships[$this->table][$name]);
    }

    /**
     * Clear static configurations (relationships and schema config).
     * 
     * Useful for testing or when switching between different schemas.
     * Can clear all static data or just those for a specific table.
     *
     * @param string|null $table Optional table name to clear. If null, clears all.
     * @return void
     */
    public static function clearStaticConfig(?string $table = null): void
    {
        if ($table === null) {
            static::$staticRelationships = [];
            static::$staticSchemaConfig = [];
        } else {
            unset(static::$staticRelationships[$table]);
            unset(static::$staticSchemaConfig[$table]);
        }
    }

    /**
     * Handle dynamic method calls for relationships.
     *
     * When a method like roles() is called on this model, this magic method
     * checks if it's a configured dynamic relationship and returns the
     * appropriate Eloquent BelongsToMany relationship.
     *
     * @param string $method The method name being called
     * @param array  $parameters The parameters passed to the method
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Check if this is a dynamic relationship call
        if ($this->hasRelationship($method)) {
            return $this->getDynamicRelationship($method);
        }

        // Fall back to parent __call for other dynamic methods
        return parent::__call($method, $parameters);
    }

    /**
     * Create and return a dynamic BelongsToMany relationship.
     *
     * Uses the relationship configuration from the schema to create an Eloquent
     * BelongsToMany relationship that can be used for attach/sync/detach operations.
     * 
     * Note: We use `static::class` as the related model because we only need the pivot
     * table operations (attach/sync/detach). The BelongsToMany relationship doesn't
     * actually need to know about the related model's table - it only needs the pivot
     * table configuration. Using self-reference allows pivot operations to work without
     * requiring the related entity to have a specific model class.
     *
     * @param string $name The relationship name
     * @return BelongsToMany
     * @throws \InvalidArgumentException If relationship type is not supported
     */
    protected function getDynamicRelationship(string $name): BelongsToMany
    {
        // Use getRelationshipConfig() to check both instance and static storage
        $config = $this->getRelationshipConfig($name);
        
        if ($config === null) {
            throw new \InvalidArgumentException(
                "Dynamic relationship '{$name}' not found. " .
                "Ensure configureFromSchema() was called before accessing relationships."
            );
        }
        
        $type = $config['type'] ?? 'many_to_many';

        if ($type !== 'many_to_many') {
            throw new \InvalidArgumentException(
                "Dynamic relationship '{$name}' has unsupported type '{$type}'. " .
                "Only 'many_to_many' relationships are supported for attach/sync/detach operations."
            );
        }

        // For many_to_many relationships, we use belongsToMany with a generic model
        // The related table doesn't need a specific model class - we just need the pivot table
        $pivotTable = $config['pivot_table'] ?? null;
        $foreignKey = $config['foreign_key'] ?? null;
        $relatedKey = $config['related_key'] ?? null;

        if (!$pivotTable || !$foreignKey || !$relatedKey) {
            throw new \InvalidArgumentException(
                "Dynamic relationship '{$name}' is missing required configuration: " .
                "pivot_table, foreign_key, or related_key."
            );
        }

        // Create a generic relationship using self (CRUD6Model) as the related model.
        // This works because BelongsToMany only uses the related model for eager loading
        // and returning related records. For pivot operations (attach/sync/detach),
        // only the pivot table configuration matters. The self-reference allows us to
        // use standard Eloquent pivot operations without needing a concrete model class
        // for the related entity.
        return $this->belongsToMany(
            static::class,   // Related model (self - we just need pivot operations)
            $pivotTable,     // Pivot table name
            $foreignKey,     // Foreign key in pivot table (points to this model)
            $relatedKey,     // Related key in pivot table (points to related model)
            null,            // Local key (default: primary key)
            null,            // Related key on related model (default: primary key)
            $name            // Relation name
        );
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
     * Returns fillable from instance property, or falls back to static storage
     * for hydrated instances.
     *
     * @return array
     */
    public function getFillable(): array
    {
        // If instance has fillable set, use it
        if (!empty($this->fillable)) {
            return $this->fillable;
        }

        // Fall back to static storage for hydrated instances
        if ($this->table && isset(static::$staticSchemaConfig[$this->table]['fillable'])) {
            return static::$staticSchemaConfig[$this->table]['fillable'];
        }

        return [];
    }

    /**
     * Get the casts array.
     * 
     * Returns casts from instance property merged with static storage
     * for hydrated instances.
     *
     * @return array
     */
    public function getCasts(): array
    {
        $casts = $this->casts;

        // Merge with static storage for hydrated instances
        if ($this->table && isset(static::$staticSchemaConfig[$this->table]['casts'])) {
            $casts = array_merge(static::$staticSchemaConfig[$this->table]['casts'], $casts);
        }

        return $casts;
    }

    /**
     * Determine if the model uses timestamps.
     * 
     * Checks static storage for hydrated instances.
     *
     * @return bool
     */
    public function usesTimestamps(): bool
    {
        // Check static storage for hydrated instances
        if ($this->table && isset(static::$staticSchemaConfig[$this->table]['timestamps'])) {
            return static::$staticSchemaConfig[$this->table]['timestamps'];
        }

        return $this->timestamps;
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
        if ($this->hasSoftDeletes()) {
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
        if ($this->hasSoftDeletes()) {
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
        if ($this->hasSoftDeletes()) {
            $this->{$this->getDeletedAtColumn()} = date('Y-m-d H:i:s');
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
        if ($this->hasSoftDeletes()) {
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
        if (!$this->hasSoftDeletes()) {
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
        if ($this->hasSoftDeletes()) {
            $query->whereNull($this->getDeletedAtColumn());
        }

        return $query;
    }

    /**
     * Get the name of the "deleted at" column.
     * 
     * Checks static storage for hydrated instances.
     *
     * @return string|null
     */
    public function getDeletedAtColumn(): ?string
    {
        // Check instance property first
        if ($this->deleted_at !== null) {
            return $this->deleted_at;
        }

        // Fall back to static storage for hydrated instances
        if ($this->table && isset(static::$staticSchemaConfig[$this->table]['deleted_at'])) {
            return static::$staticSchemaConfig[$this->table]['deleted_at'];
        }

        return null;
    }

    /**
     * Check if soft deletes are enabled for this model.
     * 
     * @return bool
     */
    public function hasSoftDeletes(): bool
    {
        return $this->getDeletedAtColumn() !== null;
    }

    /**
     * Create a dynamic relationship based on schema configuration.
     *
     * Leverages UserFrosting's built-in relationship methods (belongsToMany, belongsToManyThrough)
     * to create relationships from schema definitions without requiring hard-coded model classes.
     *
     * IMPORTANT: Pass a configured CRUD6Model instance (not a class name) to ensure the related
     * model has its table name and schema properly configured. Passing a class name will result
     * in Eloquent creating an unconfigured instance with the default table 'CRUD6_NOT_SET'.
     *
     * For belongs_to_many_through relationships, you must also pass a configured $throughModel
     * instance to ensure the intermediate model has its table properly configured.
     *
     * @param string                                                    $relationName  The name of the relationship
     * @param array                                                     $config        The relationship configuration from schema
     * @param \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model  $relatedModel  The configured related model instance
     * @param \UserFrosting\Sprinkle\CRUD6\Database\Models\CRUD6Model|null $throughModel The configured through model instance (required for belongs_to_many_through)
     *
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function dynamicRelationship(string $relationName, array $config, CRUD6Model $relatedModel, ?CRUD6Model $throughModel = null): \Illuminate\Database\Eloquent\Relations\Relation
    {
        $type = $config['type'] ?? 'belongs_to_many';

        if ($type === 'many_to_many') {
            // Standard many-to-many relationship (e.g., users -> roles)
            // Pass the configured model instance to ensure proper table configuration
            return $this->belongsToMany(
                $relatedModel,
                $config['pivot_table'],
                $config['foreign_key'],
                $config['related_key'],
                null, // parentKey (use default)
                null, // relatedKey (use default)
                $relationName
            );
        }

        if ($type === 'belongs_to_many_through' || isset($config['through'])) {
            // Nested many-to-many through intermediate model (e.g., users -> roles -> permissions)
            // The through model must be a configured CRUD6Model instance, not a class name string
            if ($throughModel === null) {
                throw new \InvalidArgumentException(
                    "belongs_to_many_through relationship '{$relationName}' requires a configured \$throughModel instance. " .
                    "The through model ('{$config['through']}') must be instantiated and configured with its schema before being passed."
                );
            }

            return $this->belongsToManyThrough(
                $relatedModel,
                $throughModel,
                $config['first_pivot_table'] ?? null,
                $config['first_foreign_key'] ?? null,
                $config['first_related_key'] ?? null,
                $config['second_pivot_table'] ?? null,
                $config['second_foreign_key'] ?? null,
                $config['second_related_key'] ?? null,
                null, // throughRelation (use default)
                $relationName
            );
        }

        throw new \InvalidArgumentException("Unsupported relationship type '{$type}' for relationship '{$relationName}'");
    }
}
