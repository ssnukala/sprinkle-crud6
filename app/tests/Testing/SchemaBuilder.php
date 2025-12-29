<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Testing;

/**
 * Schema Builder Helper for Tests.
 * 
 * Programmatically generates valid CRUD6 JSON schemas for testing.
 * This allows tests to create schemas dynamically and ensures they stay
 * in sync with code changes.
 * 
 * Usage:
 * ```php
 * $schema = SchemaBuilder::create('users', 'users')
 *     ->addStringField('user_name', required: true, listable: true)
 *     ->addStringField('email', required: true, listable: true)
 *     ->addStringField('first_name', listable: true)
 *     ->addStringField('last_name', listable: true)
 *     ->addPermissions(['read' => 'uri_users', 'create' => 'create_user'])
 *     ->build();
 * ```
 * 
 * Follows the builder pattern for fluent API construction.
 */
class SchemaBuilder
{
    /**
     * @var array The schema being built
     */
    private array $schema;

    /**
     * Private constructor - use create() factory method.
     * 
     * @param string $model The model name
     * @param string $table The database table name
     */
    private function __construct(string $model, string $table)
    {
        $this->schema = [
            'model' => $model,
            'table' => $table,
            'primary_key' => 'id',
            'title' => strtoupper("CRUD6.{$model}.PAGE"),
            'singular_title' => strtoupper("CRUD6.{$model}.1"),
            'title_field' => 'name',
            'description' => strtoupper("CRUD6.{$model}.PAGE_DESCRIPTION"),
            'default_sort' => ['id' => 'asc'],
            'permissions' => [],
            'fields' => [],
        ];
    }

    /**
     * Create a new schema builder instance.
     * 
     * @param string $model The model name
     * @param string $table The database table name (defaults to model name)
     * 
     * @return self
     */
    public static function create(string $model, ?string $table = null): self
    {
        return new self($model, $table ?? $model);
    }

    /**
     * Set the primary key field.
     * 
     * @param string $key The primary key field name
     * 
     * @return self
     */
    public function setPrimaryKey(string $key): self
    {
        $this->schema['primary_key'] = $key;
        return $this;
    }

    /**
     * Set the title field (field used to display record in UI).
     * 
     * @param string $field The field name to use as title
     * 
     * @return self
     */
    public function setTitleField(string $field): self
    {
        $this->schema['title_field'] = $field;
        return $this;
    }

    /**
     * Set default sort order.
     * 
     * @param string $field The field to sort by
     * @param string $direction Sort direction ('asc' or 'desc')
     * 
     * @return self
     */
    public function setDefaultSort(string $field, string $direction = 'asc'): self
    {
        $this->schema['default_sort'] = [$field => $direction];
        return $this;
    }

    /**
     * Set permissions for CRUD operations.
     * 
     * @param array $permissions Associative array of action => permission pairs
     * 
     * @return self
     */
    public function addPermissions(array $permissions): self
    {
        $this->schema['permissions'] = array_merge($this->schema['permissions'], $permissions);
        return $this;
    }

    /**
     * Add a database connection.
     * 
     * @param string $connection The database connection name
     * 
     * @return self
     */
    public function setConnection(string $connection): self
    {
        $this->schema['connection'] = $connection;
        return $this;
    }

    /**
     * Add a string field to the schema.
     * 
     * @param string $name       Field name
     * @param bool   $required   Is field required?
     * @param bool   $sortable   Can field be sorted?
     * @param bool   $filterable Can field be filtered?
     * @param bool   $listable   Should field appear in list view?
     * @param int    $maxLength  Maximum length constraint
     * @param bool   $unique     Should field be unique?
     * 
     * @return self
     */
    public function addStringField(
        string $name,
        bool $required = false,
        bool $sortable = false,
        bool $filterable = false,
        bool $listable = false,
        int $maxLength = 255,
        bool $unique = false
    ): self {
        $field = [
            'type' => 'string',
            'label' => strtoupper("CRUD6.{$this->schema['model']}.{$name}"),
            'required' => $required,
            'sortable' => $sortable,
            'filterable' => $filterable,
            'show_in' => $this->buildShowIn($listable, true, true),
        ];

        if ($maxLength !== 255) {
            $field['validation']['length']['max'] = $maxLength;
        }

        if ($unique) {
            $field['validation']['unique'] = true;
        }

        if ($required) {
            $field['validation']['required'] = true;
        }

        $this->schema['fields'][$name] = $field;
        return $this;
    }

    /**
     * Add an integer field to the schema.
     * 
     * @param string $name         Field name
     * @param bool   $required     Is field required?
     * @param bool   $sortable     Can field be sorted?
     * @param bool   $filterable   Can field be filtered?
     * @param bool   $listable     Should field appear in list view?
     * @param bool   $autoIncrement Is this an auto-increment primary key?
     * @param bool   $readonly     Is field read-only?
     * 
     * @return self
     */
    public function addIntegerField(
        string $name,
        bool $required = false,
        bool $sortable = false,
        bool $filterable = false,
        bool $listable = false,
        bool $autoIncrement = false,
        bool $readonly = false
    ): self {
        $field = [
            'type' => 'integer',
            'label' => strtoupper("CRUD6.{$this->schema['model']}.{$name}"),
            'required' => $required,
            'sortable' => $sortable,
            'filterable' => $filterable,
            'show_in' => $this->buildShowIn($listable, !$readonly, true),
        ];

        if ($autoIncrement) {
            $field['auto_increment'] = true;
        }

        if ($readonly) {
            $field['readonly'] = true;
        }

        if ($required && !$autoIncrement) {
            $field['validation']['required'] = true;
        }

        $this->schema['fields'][$name] = $field;
        return $this;
    }

    /**
     * Add a text field (multiline) to the schema.
     * 
     * @param string $name       Field name
     * @param bool   $required   Is field required?
     * @param bool   $sortable   Can field be sorted?
     * @param bool   $filterable Can field be filtered?
     * @param bool   $listable   Should field appear in list view?
     * 
     * @return self
     */
    public function addTextField(
        string $name,
        bool $required = false,
        bool $sortable = false,
        bool $filterable = false,
        bool $listable = false
    ): self {
        $field = [
            'type' => 'text',
            'label' => strtoupper("CRUD6.{$this->schema['model']}.{$name}"),
            'required' => $required,
            'sortable' => $sortable,
            'filterable' => $filterable,
            'show_in' => $this->buildShowIn($listable, true, true),
        ];

        if ($required) {
            $field['validation']['required'] = true;
        }

        $this->schema['fields'][$name] = $field;
        return $this;
    }

    /**
     * Add a boolean field to the schema.
     * 
     * @param string $name       Field name
     * @param bool   $required   Is field required?
     * @param bool   $sortable   Can field be sorted?
     * @param bool   $filterable Can field be filtered?
     * @param bool   $listable   Should field appear in list view?
     * @param bool   $default    Default value
     * 
     * @return self
     */
    public function addBooleanField(
        string $name,
        bool $required = false,
        bool $sortable = false,
        bool $filterable = false,
        bool $listable = false,
        ?bool $default = null
    ): self {
        $field = [
            'type' => 'boolean',
            'label' => strtoupper("CRUD6.{$this->schema['model']}.{$name}"),
            'required' => $required,
            'sortable' => $sortable,
            'filterable' => $filterable,
            'show_in' => $this->buildShowIn($listable, true, true),
        ];

        if ($default !== null) {
            $field['default'] = $default;
        }

        if ($required) {
            $field['validation']['required'] = true;
        }

        $this->schema['fields'][$name] = $field;
        return $this;
    }

    /**
     * Add a datetime field to the schema.
     * 
     * @param string $name       Field name
     * @param bool   $required   Is field required?
     * @param bool   $sortable   Can field be sorted?
     * @param bool   $filterable Can field be filtered?
     * @param bool   $listable   Should field appear in list view?
     * @param bool   $readonly   Is field read-only?
     * @param string $format     Date format (default: 'Y-m-d H:i:s')
     * 
     * @return self
     */
    public function addDateTimeField(
        string $name,
        bool $required = false,
        bool $sortable = false,
        bool $filterable = false,
        bool $listable = false,
        bool $readonly = false,
        string $format = 'Y-m-d H:i:s'
    ): self {
        $field = [
            'type' => 'datetime',
            'label' => strtoupper("CRUD6.{$this->schema['model']}.{$name}"),
            'required' => $required,
            'sortable' => $sortable,
            'filterable' => $filterable,
            'show_in' => $this->buildShowIn($listable, !$readonly, true),
            'date_format' => $format,
        ];

        if ($readonly) {
            $field['readonly'] = true;
        }

        if ($required) {
            $field['validation']['required'] = true;
        }

        $this->schema['fields'][$name] = $field;
        return $this;
    }

    /**
     * Add a date field to the schema.
     * 
     * @param string $name       Field name
     * @param bool   $required   Is field required?
     * @param bool   $sortable   Can field be sorted?
     * @param bool   $filterable Can field be filtered?
     * @param bool   $listable   Should field appear in list view?
     * @param string $format     Date format (default: 'Y-m-d')
     * 
     * @return self
     */
    public function addDateField(
        string $name,
        bool $required = false,
        bool $sortable = false,
        bool $filterable = false,
        bool $listable = false,
        string $format = 'Y-m-d'
    ): self {
        $field = [
            'type' => 'date',
            'label' => strtoupper("CRUD6.{$this->schema['model']}.{$name}"),
            'required' => $required,
            'sortable' => $sortable,
            'filterable' => $filterable,
            'show_in' => $this->buildShowIn($listable, true, true),
            'date_format' => $format,
        ];

        if ($required) {
            $field['validation']['required'] = true;
        }

        $this->schema['fields'][$name] = $field;
        return $this;
    }

    /**
     * Add an email field to the schema.
     * 
     * @param string $name       Field name
     * @param bool   $required   Is field required?
     * @param bool   $sortable   Can field be sorted?
     * @param bool   $filterable Can field be filtered?
     * @param bool   $listable   Should field appear in list view?
     * @param bool   $unique     Should field be unique?
     * 
     * @return self
     */
    public function addEmailField(
        string $name,
        bool $required = false,
        bool $sortable = false,
        bool $filterable = false,
        bool $listable = false,
        bool $unique = false
    ): self {
        $field = [
            'type' => 'string',
            'label' => strtoupper("CRUD6.{$this->schema['model']}.{$name}"),
            'required' => $required,
            'sortable' => $sortable,
            'filterable' => $filterable,
            'show_in' => $this->buildShowIn($listable, true, true),
            'validation' => [
                'email' => true,
            ],
        ];

        if ($unique) {
            $field['validation']['unique'] = true;
        }

        if ($required) {
            $field['validation']['required'] = true;
        }

        $this->schema['fields'][$name] = $field;
        return $this;
    }

    /**
     * Add a password field to the schema.
     * 
     * @param string $name       Field name
     * @param bool   $required   Is field required?
     * @param int    $minLength  Minimum password length
     * 
     * @return self
     */
    public function addPasswordField(
        string $name,
        bool $required = false,
        int $minLength = 8
    ): self {
        $field = [
            'type' => 'string',
            'label' => strtoupper("CRUD6.{$this->schema['model']}.{$name}"),
            'required' => $required,
            'sortable' => false,
            'filterable' => false,
            'show_in' => ['form'],
            'validation' => [
                'length' => [
                    'min' => $minLength,
                ],
            ],
        ];

        if ($required) {
            $field['validation']['required'] = true;
        }

        $this->schema['fields'][$name] = $field;
        return $this;
    }

    /**
     * Add a JSON field to the schema.
     * 
     * @param string $name       Field name
     * @param bool   $required   Is field required?
     * @param bool   $listable   Should field appear in list view?
     * 
     * @return self
     */
    public function addJsonField(
        string $name,
        bool $required = false,
        bool $listable = false
    ): self {
        $field = [
            'type' => 'json',
            'label' => strtoupper("CRUD6.{$this->schema['model']}.{$name}"),
            'required' => $required,
            'sortable' => false,
            'filterable' => false,
            'show_in' => $this->buildShowIn($listable, true, true),
        ];

        if ($required) {
            $field['validation']['required'] = true;
        }

        $this->schema['fields'][$name] = $field;
        return $this;
    }

    /**
     * Add a custom field with full control over configuration.
     * 
     * @param string $name   Field name
     * @param array  $config Full field configuration
     * 
     * @return self
     */
    public function addCustomField(string $name, array $config): self
    {
        $this->schema['fields'][$name] = $config;
        return $this;
    }

    /**
     * Add a relationship definition for display purposes.
     * 
     * Use this for has-many relationships where the related model has a foreign key.
     * For many-to-many relationships, use addManyToManyDetail() or don't include foreign_key.
     * 
     * @param string      $model       Related model name
     * @param string|null $foreignKey  Foreign key field (for has-many), null for many-to-many
     * @param array       $listFields  Fields to display in related list
     * @param string|null $title       Translation key for relationship title
     * 
     * @return self
     */
    public function addDetail(
        string $model,
        ?string $foreignKey = null,
        array $listFields = [],
        ?string $title = null
    ): self {
        if (!isset($this->schema['details'])) {
            $this->schema['details'] = [];
        }

        $detail = [
            'model' => $model,
            'list_fields' => $listFields,
        ];

        // Only add foreign_key if provided (for has-many relationships)
        if ($foreignKey !== null) {
            $detail['foreign_key'] = $foreignKey;
        }

        if ($title !== null) {
            $detail['title'] = $title;
        }

        $this->schema['details'][] = $detail;
        return $this;
    }
    
    /**
     * Add a many-to-many relationship detail (no foreign_key).
     * 
     * This is a convenience method for adding details without a foreign_key.
     * Use this when the relationship is many-to-many and relies on a pivot table.
     * 
     * @param string      $model       Related model name
     * @param array       $listFields  Fields to display in related list
     * @param string|null $title       Translation key for relationship title
     * 
     * @return self
     */
    public function addManyToManyDetail(
        string $model,
        array $listFields = [],
        ?string $title = null
    ): self {
        return $this->addDetail($model, null, $listFields, $title);
    }

    /**
     * Build the final schema array.
     * 
     * @return array The complete schema
     */
    public function build(): array
    {
        return $this->schema;
    }

    /**
     * Build the final schema and return as JSON string.
     * 
     * @param int $flags JSON encoding flags (default: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
     * 
     * @return string The schema as JSON
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->schema, $flags);
    }

    /**
     * Build the 'show_in' array based on field visibility settings.
     * 
     * @param bool $listable Show in list view?
     * @param bool $formable Show in form view?
     * @param bool $detailable Show in detail view?
     * 
     * @return array
     */
    private function buildShowIn(bool $listable, bool $formable, bool $detailable): array
    {
        $showIn = [];

        if ($listable) {
            $showIn[] = 'list';
        }
        if ($formable) {
            $showIn[] = 'form';
        }
        if ($detailable) {
            $showIn[] = 'detail';
        }

        return $showIn;
    }

    /**
     * Quick helper to create a basic user schema for testing.
     * 
     * @return array User schema
     */
    public static function userSchema(): array
    {
        return self::create('users', 'users')
            ->setTitleField('user_name')
            ->addPermissions([
                'read' => 'uri_users',
                'create' => 'create_user',
                'update' => 'update_user_field',
                'delete' => 'delete_user',
            ])
            ->addIntegerField('id', autoIncrement: true, readonly: true, listable: false)
            ->addStringField('user_name', required: true, sortable: true, filterable: true, listable: true, unique: true)
            ->addStringField('first_name', sortable: true, filterable: true, listable: true)
            ->addStringField('last_name', sortable: true, filterable: true, listable: true)
            ->addEmailField('email', required: true, sortable: true, filterable: true, listable: true, unique: true)
            ->addPasswordField('password', required: true)
            ->addBooleanField('flag_enabled', listable: true, default: true)
            ->addDateTimeField('created_at', readonly: true, listable: false)
            ->addDateTimeField('updated_at', readonly: true, listable: false)
            ->build();
    }

    /**
     * Quick helper to create a basic group schema for testing.
     * 
     * @return array Group schema
     */
    public static function groupSchema(): array
    {
        return self::create('groups', 'groups')
            ->setTitleField('name')
            ->addPermissions([
                'read' => 'uri_group',
                'create' => 'create_group',
                'update' => 'update_group_field',
                'delete' => 'delete_group',
            ])
            ->addIntegerField('id', autoIncrement: true, readonly: true, listable: false)
            ->addStringField('slug', required: true, unique: true, sortable: true, listable: true)
            ->addStringField('name', required: true, sortable: true, filterable: true, listable: true)
            ->addTextField('description', filterable: true, listable: true)
            ->addStringField('icon', maxLength: 100, listable: false)
            ->addDateTimeField('created_at', readonly: true, listable: false)
            ->addDateTimeField('updated_at', readonly: true, listable: false)
            ->build();
    }

    /**
     * Quick helper to create a basic product schema for testing.
     * 
     * @return array Product schema
     */
    public static function productSchema(): array
    {
        return self::create('products', 'products')
            ->setTitleField('name')
            ->addPermissions([
                'read' => 'uri_products',
                'create' => 'create_product',
                'update' => 'update_product_field',
                'delete' => 'delete_product',
            ])
            ->addIntegerField('id', autoIncrement: true, readonly: true, listable: false)
            ->addStringField('sku', required: true, unique: true, sortable: true, listable: true)
            ->addStringField('name', required: true, sortable: true, filterable: true, listable: true)
            ->addTextField('description', filterable: true, listable: false)
            ->addCustomField('price', [
                'type' => 'decimal',
                'label' => 'CRUD6.PRODUCTS.PRICE',
                'required' => true,
                'sortable' => true,
                'filterable' => true,
                'show_in' => ['list', 'form', 'detail'],
                'validation' => [
                    'required' => true,
                    'numeric' => true,
                    'min' => 0,
                ],
            ])
            ->addIntegerField('quantity', sortable: true, listable: true)
            ->addBooleanField('active', listable: true, default: true)
            ->addDateTimeField('created_at', readonly: true, listable: false)
            ->addDateTimeField('updated_at', readonly: true, listable: false)
            ->build();
    }
}
