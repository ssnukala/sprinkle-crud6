<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\ServicesProvider;

/**
 * Schema Normalizer.
 * 
 * Handles normalization of schema data to convert various input formats
 * into a consistent internal representation. This includes normalizing
 * ORM-style attributes, lookup attributes, visibility flags, and boolean types.
 */
class SchemaNormalizer
{
    /**
     * Normalize complete schema.
     * 
     * Applies all normalization steps in the correct order.
     * 
     * @param array $schema The schema to normalize
     * 
     * @return array The normalized schema
     */
    public function normalize(array $schema): array
    {
        // Normalize ORM-style attributes first (before other normalizations)
        $schema = $this->normalizeORMAttributes($schema);

        // Normalize lookup attributes for smartlookup fields
        $schema = $this->normalizeLookupAttributes($schema);

        // Normalize visibility flags to show_in array
        $schema = $this->normalizeVisibilityFlags($schema);

        // Normalize boolean field types with UI specification
        $schema = $this->normalizeBooleanTypes($schema);

        return $schema;
    }

    /**
     * Normalize lookup attributes for smartlookup fields.
     * 
     * Supports both nested and flat lookup structures:
     * - Nested: lookup: {model, id, desc}
     * - Flat: lookup_model, lookup_id, lookup_desc (legacy)
     * 
     * Converts nested structure to flat for backward compatibility.
     * Also supports shorthand attributes: model, id, desc as fallbacks.
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with normalized lookup attributes
     */
    public function normalizeLookupAttributes(array $schema): array
    {
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            return $schema;
        }

        foreach ($schema['fields'] as $fieldKey => &$field) {
            // Only process smartlookup fields
            if (($field['type'] ?? '') !== 'smartlookup') {
                continue;
            }

            // If nested 'lookup' object exists, expand it to flat attributes
            if (isset($field['lookup']) && is_array($field['lookup'])) {
                // Map nested lookup.model to lookup_model (if not already set)
                if (isset($field['lookup']['model']) && !isset($field['lookup_model'])) {
                    $field['lookup_model'] = $field['lookup']['model'];
                }
                
                // Map nested lookup.id to lookup_id (if not already set)
                if (isset($field['lookup']['id']) && !isset($field['lookup_id'])) {
                    $field['lookup_id'] = $field['lookup']['id'];
                }
                
                // Map nested lookup.desc to lookup_desc (if not already set)
                if (isset($field['lookup']['desc']) && !isset($field['lookup_desc'])) {
                    $field['lookup_desc'] = $field['lookup']['desc'];
                }
            }

            // Provide fallbacks to shorthand attributes if lookup_* not set
            // This supports both old shorthand format and ensures consistency
            if (!isset($field['lookup_model']) && isset($field['model'])) {
                $field['lookup_model'] = $field['model'];
            }
            
            if (!isset($field['lookup_id']) && isset($field['id'])) {
                $field['lookup_id'] = $field['id'];
            }
            
            if (!isset($field['lookup_desc']) && isset($field['desc'])) {
                $field['lookup_desc'] = $field['desc'];
            }
        }

        return $schema;
    }

    /**
     * Normalize visibility flags to show_in array.
     * 
     * Converts visibility flags (editable, viewable, listable) to show_in array
     * for consistent internal representation.
     * 
     * Supported contexts:
     * - 'list': Field appears in list/table view
     * - 'create': Field appears in create form
     * - 'edit': Field appears in edit form
     * - 'form': Shorthand for both create and edit (expanded to both)
     * - 'detail': Field appears in detail/view page
     * 
     * Special handling:
     * - Password fields: Default to ['create', 'edit'] (not viewable for security)
     * - Non-editable fields: Added to 'detail' but removed from 'create'/'edit'
     * - 'form' is expanded to ['create', 'edit'] for granular control
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with normalized visibility flags
     */
    public function normalizeVisibilityFlags(array $schema): array
    {
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            return $schema;
        }

        foreach ($schema['fields'] as $fieldKey => &$field) {
            $fieldType = $field['type'] ?? 'string';
            
            // If show_in already exists, normalize it
            if (isset($field['show_in']) && is_array($field['show_in'])) {
                // Expand 'form' to 'create' and 'edit' for granular control
                $normalizedShowIn = [];
                foreach ($field['show_in'] as $context) {
                    if ($context === 'form') {
                        $normalizedShowIn[] = 'create';
                        $normalizedShowIn[] = 'edit';
                    } else {
                        $normalizedShowIn[] = $context;
                    }
                }
                $field['show_in'] = array_unique($normalizedShowIn);
                
                // Derive convenience flags from show_in
                $field['listable'] = in_array('list', $field['show_in']);
                $field['editable'] = in_array('create', $field['show_in']) || in_array('edit', $field['show_in']);
                $field['viewable'] = in_array('detail', $field['show_in']);
                continue;
            }

            // Otherwise, create show_in from flags (or defaults)
            $showIn = [];
            
            // Default visibility based on flags or sensible defaults
            $listable = $field['listable'] ?? true;
            $editable = $field['editable'] ?? true;
            $viewable = $field['viewable'] ?? true;

            // Build show_in array
            if ($listable) {
                $showIn[] = 'list';
            }
            
            // Special handling for password fields
            if ($fieldType === 'password') {
                // Password fields default to create and edit only (not viewable for security)
                if ($editable) {
                    $showIn[] = 'create';
                    $showIn[] = 'edit';
                }
                // Never show password in detail view (security)
            } else {
                // Regular fields: add create/edit if editable
                if ($editable) {
                    $showIn[] = 'create';
                    $showIn[] = 'edit';
                }
                
                // Add detail view if viewable
                if ($viewable) {
                    $showIn[] = 'detail';
                }
            }

            // Set the show_in array
            $field['show_in'] = $showIn;

            // Set convenience flags
            $field['listable'] = $listable;
            $field['editable'] = $editable;
            $field['viewable'] = $viewable;
        }

        return $schema;
    }

    /**
     * Normalize boolean field types with UI specification.
     * 
     * Supports both new format (type: boolean, ui: toggle) and legacy format (type: boolean-tgl).
     * Converts legacy type suffixes to ui property for internal consistency.
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with normalized boolean types
     */
    public function normalizeBooleanTypes(array $schema): array
    {
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            return $schema;
        }

        foreach ($schema['fields'] as $fieldKey => &$field) {
            $type = $field['type'] ?? 'string';

            // Check if it's a legacy boolean type with UI suffix
            if (preg_match('/^boolean-(tgl|chk|sel|yn)$/', $type, $matches)) {
                $uiType = $matches[1];
                
                // Normalize to standard boolean type
                $field['type'] = 'boolean';
                
                // Set UI type if not already specified
                if (!isset($field['ui'])) {
                    $uiMap = [
                        'tgl' => 'toggle',
                        'chk' => 'checkbox',
                        'sel' => 'select',
                        'yn' => 'select',
                    ];
                    $field['ui'] = $uiMap[$uiType] ?? 'checkbox';
                }
            } elseif ($type === 'boolean' && !isset($field['ui'])) {
                // Set default UI for boolean fields without explicit UI
                $field['ui'] = 'checkbox';
            }
        }

        return $schema;
    }

    /**
     * Normalize ORM-style attributes to CRUD6 format.
     * 
     * Supports attributes from popular ORMs (Laravel, Sequelize, TypeORM, Django, Prisma):
     * - nullable → required (inverted)
     * - autoIncrement → auto_increment
     * - references → lookup configuration
     * - validate → validation
     * - ui → extract UI-specific attributes
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with normalized ORM attributes
     */
    public function normalizeORMAttributes(array $schema): array
    {
        if (!isset($schema['fields']) || !is_array($schema['fields'])) {
            return $schema;
        }

        foreach ($schema['fields'] as $fieldKey => &$field) {
            // 1. Normalize nullable → required (Laravel/Sequelize/TypeORM/Prisma pattern)
            if (isset($field['nullable']) && !isset($field['required'])) {
                $field['required'] = !$field['nullable'];
            }
            // Also support the reverse: required → nullable
            if (isset($field['required']) && !isset($field['nullable'])) {
                $field['nullable'] = !$field['required'];
            }

            // 2. Normalize autoIncrement → auto_increment (Sequelize/TypeORM/Prisma pattern)
            if (isset($field['autoIncrement']) && !isset($field['auto_increment'])) {
                $field['auto_increment'] = $field['autoIncrement'];
            }

            // 3. Normalize primaryKey → primary (TypeORM pattern)
            if (isset($field['primaryKey']) && !isset($field['primary'])) {
                $field['primary'] = $field['primaryKey'];
            }

            // 4. Normalize unique constraint (all ORMs support this)
            if (isset($field['unique']) && !isset($field['validation']['unique'])) {
                $field['validation'] = $field['validation'] ?? [];
                $field['validation']['unique'] = $field['unique'];
            }

            // 5. Normalize length to validation (Sequelize/Django pattern)
            if (isset($field['length']) && !isset($field['validation']['length'])) {
                $field['validation'] = $field['validation'] ?? [];
                $field['validation']['length'] = [
                    'max' => $field['length']
                ];
            }

            // 6. Normalize validate → validation (Sequelize pattern)
            if (isset($field['validate']) && !isset($field['validation'])) {
                $field['validation'] = $field['validate'];
            }

            // 7. Normalize references → lookup (Prisma/TypeORM pattern)
            if (isset($field['references']) && is_array($field['references'])) {
                // Convert references to lookup format
                if (!isset($field['lookup'])) {
                    $field['lookup'] = [
                        'model' => $field['references']['model'] ?? $field['references']['table'] ?? null,
                        'id' => $field['references']['key'] ?? $field['references']['id'] ?? 'id',
                        'desc' => $field['references']['display'] ?? $field['references']['desc'] ?? 'name',
                    ];
                }
                
                // If type not set and references exists, assume smartlookup
                if (!isset($field['type']) || $field['type'] === 'integer') {
                    // Only change to smartlookup if explicitly requested or references.display is set
                    if (isset($field['references']['display']) || isset($field['references']['desc'])) {
                        $field['type'] = 'smartlookup';
                    }
                }
            }

            // 8. Extract UI configuration from nested ui object
            if (isset($field['ui']) && is_array($field['ui'])) {
                $uiConfig = $field['ui'];
                
                // Extract label
                if (isset($uiConfig['label']) && !isset($field['label'])) {
                    $field['label'] = $uiConfig['label'];
                }
                
                // Extract show_in
                if (isset($uiConfig['show_in']) && !isset($field['show_in'])) {
                    $field['show_in'] = $uiConfig['show_in'];
                }
                
                // Extract sortable
                if (isset($uiConfig['sortable']) && !isset($field['sortable'])) {
                    $field['sortable'] = $uiConfig['sortable'];
                }
                
                // Extract filterable
                if (isset($uiConfig['filterable']) && !isset($field['filterable'])) {
                    $field['filterable'] = $uiConfig['filterable'];
                }
                
                // Extract widget/type as UI hint (for booleans, etc.)
                if (isset($uiConfig['widget']) && $field['type'] === 'boolean') {
                    $field['ui'] = $uiConfig['widget'];  // Override with widget name
                } elseif (isset($uiConfig['type']) && $uiConfig['type'] === 'lookup') {
                    // UI type hint for lookup fields
                    if ($field['type'] === 'integer' || !isset($field['type'])) {
                        $field['type'] = 'smartlookup';
                    }
                }
            }

            // 9. Normalize default/defaultValue (all ORMs support default)
            if (isset($field['defaultValue']) && !isset($field['default'])) {
                $field['default'] = $field['defaultValue'];
            }
        }

        return $schema;
    }
}
