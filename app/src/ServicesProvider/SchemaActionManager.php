<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\ServicesProvider;

use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;

/**
 * Schema Action Manager.
 * 
 * Handles management of CRUD actions in schemas, including adding default actions
 * and normalizing toggle actions to ensure proper confirmation.
 */
class SchemaActionManager
{
    /**
     * Constructor.
     * 
     * Dependencies are injected through the DI container following UserFrosting 6 patterns.
     * 
     * @param SchemaValidator      $validator Schema validator for permission checks
     * @param DebugLoggerInterface $logger    Debug logger for diagnostics
     */
    public function __construct(
        protected SchemaValidator $validator,
        protected DebugLoggerInterface $logger
    ) {
    }

    /**
     * Add default CRUD actions to schema if not already defined.
     * 
     * This method intelligently adds standard CRUD actions (create, edit, delete)
     * to schemas that don't already define them. Each action is scoped appropriately:
     * 
     * - 'create': Appears in list view (scope: 'list')
     * - 'edit': Appears in detail view (scope: 'detail')
     * - 'delete': Appears in detail view (scope: 'detail')
     * 
     * Actions are only added if:
     * 1. Schema doesn't have an existing action with the same key
     * 2. Schema permissions allow the operation
     * 3. Schema hasn't explicitly set `default_actions: false`
     * 
     * Schemas can override default actions in two ways:
     * 1. Set `"default_actions": false` to disable all defaults
     * 2. Define custom actions with keys matching default keys (create_action, edit_action, delete_action)
     * 
     * @param array $schema The schema array
     * 
     * @return array The schema with default actions added
     */
    public function addDefaultActions(array $schema): array
    {
        // Check if default actions are disabled
        if (isset($schema['default_actions']) && $schema['default_actions'] === false) {
            $this->debugLog('[SchemaActionManager.addDefaultActions] Default actions disabled', [
                'model' => $schema['model'] ?? 'unknown',
            ]);
            return $schema;
        }

        // Initialize actions array if not present
        if (!isset($schema['actions'])) {
            $schema['actions'] = [];
        }
        
        $this->debugLog('[SchemaActionManager.addDefaultActions] Before adding defaults', [
            'model' => $schema['model'] ?? 'unknown',
            'existing_actions' => array_column($schema['actions'], 'key'),
        ]);

        // Normalize toggle actions to ensure they have confirmation
        $schema['actions'] = $this->normalizeToggleActions($schema['actions'], $schema);

        // Get existing action keys for duplicate detection
        $existingKeys = array_column($schema['actions'], 'key');

        // Default actions - all available in row dropdown
        $defaultActions = [];

        // Create action
        if (!in_array('create_action', $existingKeys) && $this->validator->hasPermission($schema, 'create')) {
            $this->debugLog('[SchemaActionManager.addDefaultActions] Adding create_action', [
                'model' => $schema['model'] ?? 'unknown',
                'has_create_permission' => true,
            ]);
            $defaultActions[] = [
                'key' => 'create_action',
                'label' => "CRUD6.CREATE",
                'icon' => 'plus',
                'type' => 'form',
                'style' => 'primary',
                'permission' => $schema['permissions']['create'] ?? 'create',
                'modal_config' => [
                    'type' => 'form',
                    'title' => "CRUD6.CREATE",
                ],
            ];
        }

        // Edit action
        if (!in_array('edit_action', $existingKeys) && $this->validator->hasPermission($schema, 'update')) {
            $defaultActions[] = [
                'key' => 'edit_action',
                'label' => "CRUD6.EDIT",
                'icon' => 'pen-to-square',
                'type' => 'form',
                'style' => 'primary',
                'permission' => $schema['permissions']['update'] ?? 'update',
                'modal_config' => [
                    'type' => 'form',
                    'title' => "CRUD6.EDIT",
                ],
            ];
        }

        // Delete action
        if (!in_array('delete_action', $existingKeys) && $this->validator->hasPermission($schema, 'delete')) {
            $this->debugLog('[SchemaActionManager.addDefaultActions] Adding delete_action', [
                'model' => $schema['model'] ?? 'unknown',
                'has_delete_permission' => true,
            ]);
            $defaultActions[] = [
                'key' => 'delete_action',
                'label' => "CRUD6.DELETE",
                'icon' => 'trash',
                'type' => 'delete',
                'style' => 'danger',
                'permission' => $schema['permissions']['delete'] ?? 'delete',
                'confirm' => "CRUD6.DELETE_CONFIRM",
                'modal_config' => [
                    'type' => 'confirm',
                    'buttons' => 'yes_no',
                    'warning' => 'WARNING_CANNOT_UNDONE',
                ],
            ];
        }

        // Prepend default actions (so custom actions appear after)
        if (!empty($defaultActions)) {
            $schema['actions'] = array_merge($defaultActions, $schema['actions']);
            
            $this->debugLog('[SchemaActionManager.addDefaultActions] Added default actions', [
                'model' => $schema['model'] ?? 'unknown',
                'actions_added' => array_column($defaultActions, 'key'),
                'total_actions' => count($schema['actions']),
                'all_action_keys' => array_column($schema['actions'], 'key'),
            ]);
        } else {
            $this->debugLog('[SchemaActionManager.addDefaultActions] No default actions added', [
                'model' => $schema['model'] ?? 'unknown',
                'reason' => 'All defaults already exist or no permissions',
            ]);
        }

        return $schema;
    }

    /**
     * Normalize toggle actions to ensure they have confirmation modals.
     * 
     * Toggle actions (field_update with toggle: true) should always show a confirmation
     * before changing the value. This method adds default confirm messages and modal
     * config if not already present.
     * 
     * The field label is included as a translatable parameter so it can be properly
     * translated in the frontend.
     * 
     * @param array $actions Actions array from schema
     * @param array $schema  Full schema for field label lookups
     * 
     * @return array Normalized actions array
     */
    public function normalizeToggleActions(array $actions, array $schema): array
    {
        foreach ($actions as &$action) {
            // Only process field_update actions with toggle enabled
            if (($action['type'] ?? '') !== 'field_update' || !($action['toggle'] ?? false)) {
                continue;
            }

            // Get field name and field configuration
            $fieldName = $action['field'] ?? null;
            if (!$fieldName) {
                continue;
            }

            $fieldConfig = $schema['fields'][$fieldName] ?? null;
            // Use field label (may be a translation key or plain text)
            $fieldLabel = $fieldConfig['label'] ?? ucfirst(str_replace('_', ' ', $fieldName));

            // Add default confirm message if not present
            // Use CRUD6.TOGGLE_CONFIRM translation key which will handle the field label translation
            if (!isset($action['confirm'])) {
                $titleField = $schema['title_field'] ?? 'id';
                // Store field label for translation
                if (!isset($action['field_label'])) {
                    $action['field_label'] = $fieldLabel;
                }
                // Use translation key for confirm message
                $action['confirm'] = "CRUD6.TOGGLE_CONFIRM";
            }

            // Add default modal config if not present
            if (!isset($action['modal_config'])) {
                $action['modal_config'] = [
                    'type' => 'confirm',
                    'buttons' => 'yes_no',
                ];
            } elseif (!isset($action['modal_config']['type'])) {
                // Ensure modal type is set to confirm for toggles
                $action['modal_config']['type'] = 'confirm';
            }

            $this->debugLog("[SchemaActionManager] Normalized toggle action", [
                'key' => $action['key'] ?? 'unknown',
                'field' => $fieldName,
                'has_confirm' => isset($action['confirm']),
            ]);
        }

        return $actions;
    }

    /**
     * Filter actions by scope.
     * 
     * Returns only actions that have the specified scope (list or detail).
     * Actions without a scope attribute are excluded - all actions must explicitly declare their scope.
     * 
     * @param array  $actions Actions array from schema
     * @param string $scope   The scope to filter by ('list' or 'detail')
     * 
     * @return array Filtered actions array
     */
    public function filterActionsByScope(array $actions, string $scope): array
    {
        $this->debugLog('[SchemaActionManager.filterActionsByScope] Filtering actions', [
            'scope' => $scope,
            'input_actions' => array_column($actions, 'key'),
            'actions_with_scopes' => array_map(function($action) {
                return ['key' => $action['key'], 'scope' => $action['scope'] ?? 'none'];
            }, $actions)
        ]);
        
        $filtered = array_values(array_filter($actions, function ($action) use ($scope) {
            // Exclude actions without scope (they should define their scope explicitly)
            // Only default actions (create/edit/delete) added by addDefaultActions have scopes
            if (!isset($action['scope'])) {
                $this->debugLog('[SchemaActionManager.filterActionsByScope] Excluding action without scope', [
                    'action_key' => $action['key'] ?? 'unknown',
                    'scope' => $scope,
                ]);
                return false;
            }

            // Check if scope matches
            if (is_array($action['scope'])) {
                return in_array($scope, $action['scope']);
            }

            return $action['scope'] === $scope;
        }));
        
        $this->debugLog('[SchemaActionManager.filterActionsByScope] Filter result', [
            'scope' => $scope,
            'filtered_actions' => array_column($filtered, 'key'),
        ]);
        
        return $filtered;
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
