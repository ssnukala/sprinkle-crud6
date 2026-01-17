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

use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * Schema Filter.
 * 
 * Handles filtering of schemas for specific contexts (list, form, detail, etc.)
 * and loading of related model schemas.
 */
class SchemaFilter
{
    /**
     * Constructor.
     * 
     * Dependencies are injected through the DI container following UserFrosting 6 patterns.
     * 
     * @param DebugLoggerInterface $logger Debug logger for diagnostics
     */
    public function __construct(
        protected DebugLoggerInterface $logger
    ) {
    }

    /**
     * Filter schema for a specific context or multiple contexts.
     * 
     * Returns only the schema properties needed for a specific frontend context,
     * reducing payload size and preventing exposure of sensitive information.
     * 
     * Supported contexts:
     * - 'list': Fields for listing/table view (listable fields only)
     * - 'form': Fields for create/edit forms (editable fields with validation)
     * - 'detail': Full field information for detail/view pages
     * - 'meta': Just model metadata (no field details)
     * - null/'full': Complete schema (backward compatible, but not recommended)
     * 
     * Multiple contexts can be specified as comma-separated values (e.g., 'list,form').
     * When multiple contexts are provided, returns a combined schema with separate
     * sections for each context under a 'contexts' key.
     * 
     * @param array       $schema  The complete schema array
     * @param string|null $context The context ('list', 'form', 'detail', 'meta', comma-separated for multiple, or null for full)
     * 
     * @return array The filtered schema appropriate for the context(s)
     */
    public function filterForContext(array $schema, ?string $context = null): array
    {
        // DEBUG: Log context filtering to track which contexts are being requested
        $this->debugLog("[CRUD6 SchemaFilter] filterForContext() called", [
            'model' => $schema['model'] ?? 'unknown',
            'context' => $context ?? 'null/full',
            'timestamp' => date('Y-m-d H:i:s.u'),
        ]);
        
        // If no context or 'full', return complete schema (backward compatible)
        if ($context === null || $context === 'full') {
            $this->debugLog("[CRUD6 SchemaFilter] Returning full schema (no filtering)");
            return $schema;
        }

        // Check if multiple contexts are requested (comma-separated)
        if (strpos($context, ',') !== false) {
            $contexts = array_map('trim', explode(',', $context));
            $this->debugLog("[CRUD6 SchemaFilter] Multiple contexts requested", [
                'contexts' => implode(', ', $contexts),
            ]);
            return $this->filterForMultipleContexts($schema, $contexts);
        }

        // Single context - use existing logic
        $this->debugLog("[CRUD6 SchemaFilter] Single context filtering", [
            'context' => $context,
        ]);
        return $this->filterForSingleContext($schema, $context);
    }

    /**
     * Filter schema for multiple contexts.
     * 
     * Returns a combined schema with separate sections for each requested context.
     * This reduces API calls by providing all needed schema information in one response.
     * 
     * @param array $schema   The complete schema array
     * @param array $contexts Array of context names to include
     * 
     * @return array Combined schema with contexts section
     */
    protected function filterForMultipleContexts(array $schema, array $contexts): array
    {
        // Start with base metadata that all contexts need
        $filtered = [
            'model' => $schema['model'],
            'title' => $schema['title'] ?? ucfirst($schema['model']),
            'singular_title' => $schema['singular_title'] ?? $schema['title'] ?? ucfirst($schema['model']),
            'primary_key' => $schema['primary_key'] ?? 'id',
        ];

        // Add title_field if present (used for translation context)
        if (isset($schema['title_field'])) {
            $filtered['title_field'] = $schema['title_field'];
        }

        // Add description if present
        if (isset($schema['description'])) {
            $filtered['description'] = $schema['description'];
        }

        // Add permissions if present (needed for permission checks)
        if (isset($schema['permissions'])) {
            $filtered['permissions'] = $schema['permissions'];
        }

        // Add actions at root level (all actions available for all contexts)
        if (isset($schema['actions'])) {
            $filtered['actions'] = $schema['actions'];
            $this->debugLog('[SchemaFilter.filterForMultipleContexts] Actions included at root level', [
                'model' => $schema['model'],
                'action_count' => count($schema['actions']),
                'action_keys' => array_column($schema['actions'], 'key'),
            ]);
        }

        // Add contexts section with filtered data for each context
        $filtered['contexts'] = [];
        foreach ($contexts as $context) {
            $contextData = $this->getContextSpecificData($schema, $context);
            if ($contextData !== null) {
                $filtered['contexts'][$context] = $contextData;
            }
        }

        return $filtered;
    }

    /**
     * Filter schema for a single context (legacy behavior).
     * 
     * @param array  $schema  The complete schema array
     * @param string $context The context name
     * 
     * @return array The filtered schema appropriate for the context
     */
    protected function filterForSingleContext(array $schema, string $context): array
    {
        // Start with base metadata that all contexts need
        $filtered = [
            'model' => $schema['model'],
            'title' => $schema['title'] ?? ucfirst($schema['model']),
            'singular_title' => $schema['singular_title'] ?? $schema['title'] ?? ucfirst($schema['model']),
            'primary_key' => $schema['primary_key'] ?? 'id',
        ];

        // Add description if present
        if (isset($schema['description'])) {
            $filtered['description'] = $schema['description'];
        }

        // Add permissions if present (needed for permission checks)
        if (isset($schema['permissions'])) {
            $filtered['permissions'] = $schema['permissions'];
        }

        // Get context-specific data
        $contextData = $this->getContextSpecificData($schema, $context);
        
        // If context data is null (unknown context), return full schema for safety
        if ($contextData === null) {
            return $schema;
        }

        // Merge context-specific data into filtered schema
        return array_merge($filtered, $contextData);
    }

    /**
     * Get context-specific data (fields and related configuration).
     * 
     * @param array  $schema  The complete schema array
     * @param string $context The context name
     * 
     * @return array|null Context-specific data, or null for unknown contexts
     */
    protected function getContextSpecificData(array $schema, string $context): ?array
    {
        switch ($context) {
            case 'meta':
                // Minimal metadata only - no field information
                // Just model identification and permissions (already in base)
                return [];

            case 'list':
                return $this->getListContextData($schema);

            case 'create':
                // For create forms: fields visible during creation
                return $this->getFormContextData($schema, 'create');

            case 'edit':
                // For edit forms: fields visible during editing
                return $this->getFormContextData($schema, 'edit');

            case 'form':
                // For create/edit forms: fields visible in both create and edit contexts
                // This merges fields from both create and edit contexts
                return $this->getCombinedFormContextData($schema);

            case 'detail':
                return $this->getDetailContextData($schema);

            default:
                // Unknown context - return null to signal fallback to full schema
                return null;
        }
    }

    /**
     * Get list context data.
     * 
     * @param array $schema The complete schema array
     * 
     * @return array List context data
     */
    protected function getListContextData(array $schema): array
    {
        // For list/table views: only listable fields with display properties
        $data = [
            'fields' => [],
            'default_sort' => $schema['default_sort'] ?? [],
        ];
        
        foreach ($schema['fields'] as $fieldKey => $field) {
            // Check show_in array for 'list' context
            $showInList = isset($field['show_in']) 
                ? in_array('list', $field['show_in']) 
                : ($field['listable'] ?? false);
            
            if ($showInList) {
                $data['fields'][$fieldKey] = [
                    'type' => $field['type'] ?? 'string',
                    'label' => $field['label'] ?? $fieldKey,
                    'sortable' => $field['sortable'] ?? false,
                    'filterable' => $field['filterable'] ?? false,
                ];

                // Include width if specified
                if (isset($field['width'])) {
                    $data['fields'][$fieldKey]['width'] = $field['width'];
                }

                // Include field_template if specified (for custom rendering)
                if (isset($field['field_template'])) {
                    $data['fields'][$fieldKey]['field_template'] = $field['field_template'];
                }

                // Include filter_type if field is filterable
                if (isset($field['filter_type']) && ($field['filterable'] ?? false)) {
                    $data['fields'][$fieldKey]['filter_type'] = $field['filter_type'];
                }
            }
        }

        // Include all actions
        if (isset($schema['actions'])) {
            $data['actions'] = $schema['actions'];
            $this->debugLog('[SchemaFilter.getListContextData] List context - all actions included', [
                'action_count' => count($schema['actions']),
                'action_keys' => array_column($schema['actions'], 'key'),
            ]);
        }

        return $data;
    }

    /**
     * Get detail context data.
     * 
     * @param array $schema The complete schema array
     * 
     * @return array Detail context data
     */
    protected function getDetailContextData(array $schema): array
    {
        // For detail/view pages: only viewable fields with full display properties
        $data = ['fields' => []];
        
        foreach ($schema['fields'] as $fieldKey => $field) {
            // Check show_in array for 'detail' context
            $showInDetail = isset($field['show_in']) 
                ? in_array('detail', $field['show_in']) 
                : ($field['viewable'] ?? true);
            
            if ($showInDetail) {
                $fieldType = $field['type'] ?? 'string';
                
                // Password fields should always be readonly in detail view
                $isPasswordField = $fieldType === 'password';
                $readonly = $field['readonly'] ?? $isPasswordField;
                
                $data['fields'][$fieldKey] = [
                    'type' => $fieldType,
                    'label' => $field['label'] ?? $fieldKey,
                    'editable' => $field['editable'] ?? !$readonly,
                    'readonly' => $readonly,
                ];

                // Include description if present
                if (isset($field['description'])) {
                    $data['fields'][$fieldKey]['description'] = $field['description'];
                }

                // Include field_template if specified
                if (isset($field['field_template'])) {
                    $data['fields'][$fieldKey]['field_template'] = $field['field_template'];
                }

                // Include default value for display purposes
                if (isset($field['default'])) {
                    $data['fields'][$fieldKey]['default'] = $field['default'];
                }
            }
        }

        // Include detail configuration if present (for related data - singular, legacy)
        if (isset($schema['detail'])) {
            $data['detail'] = $schema['detail'];
        }

        // Include details configuration if present (for related data - plural, new format)
        if (isset($schema['details'])) {
            $data['details'] = $schema['details'];
        }

        // Include all actions
        if (isset($schema['actions'])) {
            $data['actions'] = $schema['actions'];
            $this->debugLog('[SchemaFilter.getDetailContextData] Detail context - all actions included', [
                'action_count' => count($schema['actions']),
                'action_keys' => array_column($schema['actions'], 'key'),
            ]);
        }

        // Include relationships configuration if present (for data fetching)
        if (isset($schema['relationships'])) {
            $data['relationships'] = $schema['relationships'];
        }

        // Include detail_editable configuration if present
        if (isset($schema['detail_editable'])) {
            $data['detail_editable'] = $schema['detail_editable'];
        }

        // Include render_mode if present
        if (isset($schema['render_mode'])) {
            $data['render_mode'] = $schema['render_mode'];
        }

        // Include title_field if present (for displaying record name)
        if (isset($schema['title_field'])) {
            $data['title_field'] = $schema['title_field'];
        }
        
        return $data;
    }

    /**
     * Get form context data for create or edit context.
     * 
     * Helper method to extract fields visible in create or edit forms.
     * 
     * @param array  $schema  The complete schema array
     * @param string $context Either 'create' or 'edit'
     * 
     * @return array Form data with fields for the specified context
     */
    protected function getFormContextData(array $schema, string $context): array
    {
        $data = ['fields' => []];
        
        foreach ($schema['fields'] as $fieldKey => $field) {
            // Check show_in array for the specific context (create or edit)
            $showInContext = false;
            
            if (isset($field['show_in']) && is_array($field['show_in'])) {
                $showInContext = in_array($context, $field['show_in']);
            } else {
                // Fallback if show_in not set
                $showInContext = ($field['editable'] ?? true) !== false;
            }
            
            if ($showInContext) {
                $data['fields'][$fieldKey] = [
                    'type' => $field['type'] ?? 'string',
                    'label' => $field['label'] ?? $fieldKey,
                    'required' => $field['required'] ?? false,
                    'editable' => $field['editable'] ?? true,
                ];

                // Include validation rules if present
                if (isset($field['validation'])) {
                    $data['fields'][$fieldKey]['validation'] = $field['validation'];
                }

                // Include placeholder if present
                if (isset($field['placeholder'])) {
                    $data['fields'][$fieldKey]['placeholder'] = $field['placeholder'];
                }

                // Include description if present (helpful hint text)
                if (isset($field['description'])) {
                    $data['fields'][$fieldKey]['description'] = $field['description'];
                }

                // Include default value if present
                if (isset($field['default'])) {
                    $data['fields'][$fieldKey]['default'] = $field['default'];
                }

                // Include icon if present
                if (isset($field['icon'])) {
                    $data['fields'][$fieldKey]['icon'] = $field['icon'];
                }

                // Include rows for textarea fields
                if (isset($field['rows'])) {
                    $data['fields'][$fieldKey]['rows'] = $field['rows'];
                }

                // Include editable flag (may differ from parent default)
                if (isset($field['editable'])) {
                    $data['fields'][$fieldKey]['editable'] = $field['editable'];
                }
                
                // Include show_in for reference
                if (isset($field['show_in'])) {
                    $data['fields'][$fieldKey]['show_in'] = $field['show_in'];
                }

                // Include smartlookup configuration if present
                if (($field['type'] ?? '') === 'smartlookup') {
                    $this->includeSmartlookupFields($field, $data['fields'][$fieldKey]);
                }
            }
        }
        
        return $data;
    }

    /**
     * Get combined form context data (both create and edit).
     * 
     * @param array $schema The complete schema array
     * 
     * @return array Combined form data
     */
    protected function getCombinedFormContextData(array $schema): array
    {
        $createData = $this->getFormContextData($schema, 'create');
        $editData = $this->getFormContextData($schema, 'edit');
        
        // Merge fields from both contexts
        $mergedFields = [];
        foreach ($createData['fields'] as $key => $field) {
            $mergedFields[$key] = $field;
        }
        foreach ($editData['fields'] as $key => $field) {
            if (!isset($mergedFields[$key])) {
                $mergedFields[$key] = $field;
            }
        }
        
        return ['fields' => $mergedFields];
    }

    /**
     * Include smartlookup field configuration.
     * 
     * @param array $field      The field configuration
     * @param array &$targetField Reference to target field array
     * 
     * @return void
     */
    protected function includeSmartlookupFields(array $field, array &$targetField): void
    {
        if (isset($field['lookup_model'])) {
            $targetField['lookup_model'] = $field['lookup_model'];
        }
        if (isset($field['lookup_id'])) {
            $targetField['lookup_id'] = $field['lookup_id'];
        }
        if (isset($field['lookup_desc'])) {
            $targetField['lookup_desc'] = $field['lookup_desc'];
        }
        if (isset($field['model'])) {
            $targetField['model'] = $field['model'];
        }
        if (isset($field['id'])) {
            $targetField['id'] = $field['id'];
        }
        if (isset($field['desc'])) {
            $targetField['desc'] = $field['desc'];
        }
    }

    /**
     * Log debug message.
     * 
    /**
     * Log debug message.
     * 
     * @param string $message Debug message
     * @param array  $context Context data for structured logging
     * 
     * @return void
     */
    protected function debugLog(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }
}
