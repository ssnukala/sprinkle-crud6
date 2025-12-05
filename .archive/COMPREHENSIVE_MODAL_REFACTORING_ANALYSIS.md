# Comprehensive CRUD6 Modal Refactoring Analysis
**Date:** 2025-12-05  
**Objective:** Create a scalable, schema-driven framework with reusable components

## Executive Summary

The CRUD6 sprinkle's core objective is **JSON schema-driven functionality**. All modals, forms, and actions should be orchestrated through schema definitions, not hard-coded components. This analysis proposes consolidating all modal operations under a single, schema-driven `UnifiedModal` component that can handle ALL CRUD operations and custom actions.

## Current State Analysis

### Component Inventory (12 components, 3,349 total lines)

| Component | Lines | Purpose | Schema-Driven? | Redundancy |
|-----------|-------|---------|----------------|------------|
| **ActionModal.vue** | 555 | Unified action modal (confirm/input) | ✅ Yes | **Core** |
| **Form.vue** | 543 | CRUD form rendering | ✅ Yes | **Core** |
| **Info.vue** | 408 | Record detail display | ✅ Yes | **Core** |
| **MasterDetailForm.vue** | 378 | Master-detail entry | ✅ Yes | Specialized |
| **AutoLookup.vue** | 364 | Searchable autocomplete | ✅ Yes | Specialized |
| **GoogleAddress.vue** | 334 | Address lookup | ✅ Yes | Specialized |
| **DetailGrid.vue** | 329 | Related records grid | ✅ Yes | Specialized |
| **Details.vue** | 117 | Record details | ✅ Yes | **Core** |
| **ToggleSwitch.vue** | 114 | Boolean toggle UI | ✅ Yes | UI Component |
| **DeleteModal.vue** | 84 | Delete confirmation | ❌ Hard-coded | **REDUNDANT** |
| **EditModal.vue** | 65 | Edit form wrapper | ❌ Hard-coded | **REDUNDANT** |
| **CreateModal.vue** | 58 | Create form wrapper | ❌ Hard-coded | **REDUNDANT** |

### Key Findings

1. **ActionModal is 90% there** - Already handles most modal scenarios schema-driven
2. **Create/Edit/Delete are hard-coded** - Not driven by schema, duplicate patterns
3. **Form.vue is excellent** - Fully schema-driven, renders any model
4. **Opportunity** - Consolidate ALL modals into schema-driven architecture

## Schema-Driven Architecture Vision

### Current Schema Capabilities

From `examples/schema/users-extended.json`, schemas can define:

```json
{
  "actions": [
    {
      "key": "password_action",
      "label": "Change Password",
      "icon": "key",
      "type": "field_update",
      "field": "password",
      "confirm": "Enter a new password for <strong>{{user_name}}</strong>.",
      "modal_config": {
        "type": "input",
        "fields": ["password"]
      }
    },
    {
      "key": "disable_user",
      "label": "Disable User",
      "type": "field_update",
      "field": "flag_enabled",
      "value": false,
      "confirm": "Are you sure...",
      "modal_config": {
        "type": "confirm",
        "buttons": [...]
      }
    }
  ]
}
```

### Missing Schema-Driven Operations

**Problem:** Create, Edit, Delete are NOT schema-driven actions!

Current usage in `Info.vue`:
```vue
<!-- Hard-coded components -->
<CRUD6EditModal :crud6="crud6" :model="model" :schema="finalSchema" />
<CRUD6DeleteModal :crud6="crud6" :model="model" :schema="finalSchema" />
```

Current usage in `PageList.vue`:
```vue
<!-- Hard-coded components -->
<CRUD6CreateModal :model="model" :schema="schema" />
<CRUD6EditModal :crud6="row" :model="model" :schema="schema" />
<CRUD6DeleteModal :crud6="row" :model="model" :schema="schema" />
```

**Solution:** Define standard CRUD operations in schema:

```json
{
  "actions": [
    {
      "key": "create",
      "label": "CRUD6.CREATE",
      "icon": "plus",
      "type": "crud_operation",
      "operation": "create",
      "style": "primary",
      "modal_config": {
        "type": "form",
        "context": "create"
      }
    },
    {
      "key": "edit",
      "label": "CRUD6.EDIT",
      "icon": "pen-to-square",
      "type": "crud_operation",
      "operation": "update",
      "style": "primary",
      "modal_config": {
        "type": "form",
        "context": "edit"
      }
    },
    {
      "key": "delete",
      "label": "CRUD6.DELETE",
      "icon": "trash",
      "type": "crud_operation",
      "operation": "delete",
      "style": "danger",
      "confirm": "CRUD6.DELETE_CONFIRM",
      "modal_config": {
        "type": "confirm",
        "buttons": "yes_no",
        "warning": "WARNING_CANNOT_UNDONE"
      }
    }
  ]
}
```

## Proposed Unified Architecture

### 1. Single UnifiedModal Component (Replaces All 5 Modal Types)

**Component:** `UnifiedModal.vue` (evolution of ActionModal)

**Capabilities:**
- ✅ Confirmation dialogs (delete, disable, etc.)
- ✅ Input forms (password, field updates)
- ✅ Full CRUD forms (create, edit)
- ✅ Custom actions (API calls, navigation)
- ✅ Schema-driven everything (buttons, fields, validation, messages)

**Props:**
```typescript
interface UnifiedModalProps {
  action: ActionConfig          // The action definition from schema
  record?: CRUD6Interface       // Optional record for edit/delete
  schema: any                   // Full schema (for form context)
  model: string                 // Model name
  mode?: 'trigger' | 'programmatic'  // Show trigger button or control externally
}
```

**Modal Types (driven by `action.modal_config.type`):**

1. **`type: 'confirm'`** - Confirmation only
   - Shows message with Yes/No buttons
   - Used for: Delete, Disable, Send Email, etc.
   
2. **`type: 'input'`** - Field input form
   - Shows specific fields with validation
   - Used for: Password change, single field updates
   
3. **`type: 'form'`** - Full CRUD form
   - Embeds CRUD6Form component
   - Uses schema context (create/edit)
   - Used for: Create, Edit operations

### 2. Schema Enhancement: Default Actions

**Backend:** Auto-inject standard CRUD actions if not defined

```php
// In SchemaService.php
protected function addDefaultActions(array $schema): array
{
    $defaultActions = [
        [
            'key' => 'create',
            'label' => 'CRUD6.CREATE',
            'icon' => 'plus',
            'type' => 'crud_operation',
            'operation' => 'create',
            'style' => 'primary',
            'permission' => 'create',
            'modal_config' => [
                'type' => 'form',
                'context' => 'create'
            ],
            'scope' => 'list'  // Only show on list pages
        ],
        [
            'key' => 'edit',
            'label' => 'CRUD6.EDIT',
            'icon' => 'pen-to-square',
            'type' => 'crud_operation',
            'operation' => 'update',
            'style' => 'primary',
            'permission' => 'update',
            'modal_config' => [
                'type' => 'form',
                'context' => 'edit'
            ],
            'scope' => 'detail'  // Only show on detail pages
        ],
        [
            'key' => 'delete',
            'label' => 'CRUD6.DELETE',
            'icon' => 'trash',
            'type' => 'crud_operation',
            'operation' => 'delete',
            'style' => 'danger',
            'permission' => 'delete',
            'confirm' => 'CRUD6.DELETE_CONFIRM',
            'modal_config' => [
                'type' => 'confirm',
                'buttons' => 'yes_no',
                'warning' => 'WARNING_CANNOT_UNDONE'
            ],
            'scope' => 'detail'
        ]
    ];
    
    // Merge with custom actions, allowing override
    $schema['actions'] = array_merge($defaultActions, $schema['actions'] ?? []);
    return $schema;
}
```

### 3. Component Architecture

```
UnifiedModal.vue (600 lines)
├── Handles trigger button rendering
├── Modal type detection (confirm/input/form)
├── Dynamic button configuration
├── Field validation logic
└── Embeds:
    └── CRUD6Form.vue (when type='form')
        ├── Schema-driven field rendering
        ├── Multi-column layouts
        ├── Field type components
        └── Validation

Supporting Components (unchanged):
- AutoLookup.vue (for lookup fields)
- ToggleSwitch.vue (for boolean fields)
- GoogleAddress.vue (for address fields)
- DetailGrid.vue (for related records)
```

### 4. Usage Patterns

#### A. Info Component (Detail Page)
```vue
<template>
  <UFCardBox>
    <!-- Dynamic buttons from schema actions -->
    <template v-for="action in scopedActions('detail')" :key="action.key">
      <UnifiedModal
          :action="action"
          :record="crud6"
          :schema="schema"
          :model="model"
          @confirmed="handleAction(action, $event)" />
    </template>
  </UFCardBox>
</template>
```

#### B. List Component (Table Actions)
```vue
<template>
  <!-- Create button at top -->
  <UnifiedModal
      :action="getAction('create')"
      :schema="schema"
      :model="model"
      @confirmed="refresh" />
      
  <!-- Row actions -->
  <template v-for="row in rows">
    <UnifiedModal
        :action="getAction('edit')"
        :record="row"
        :schema="schema"
        :model="model"
        @confirmed="refresh" />
    <UnifiedModal
        :action="getAction('delete')"
        :record="row"
        :schema="schema"
        :model="model"
        @confirmed="removeRow(row)" />
  </template>
</template>
```

#### C. Custom Schema Actions
```json
{
  "actions": [
    {
      "key": "approve_order",
      "label": "Approve Order",
      "icon": "check",
      "type": "field_update",
      "field": "status",
      "value": "approved",
      "confirm": "Approve order #{{order_number}}?",
      "modal_config": {
        "type": "confirm",
        "buttons": [
          { "label": "Cancel", "action": "cancel" },
          { "label": "Approve", "icon": "check", "style": "primary", "action": "confirm" }
        ]
      },
      "visible_when": { "status": "pending" },
      "permission": "approve_orders"
    }
  ]
}
```

## Implementation Plan

### Phase 1: UnifiedModal Enhancement ✅ (Minimal Changes)

**Status:** MOSTLY COMPLETE - ActionModal already does this!

- [x] ActionModal handles confirm, input, form types
- [x] Schema-driven button configuration
- [x] Field validation and rendering
- [ ] Add CRUD6Form embedding for `type: 'form'`
- [ ] Add scope filtering (list/detail/both)

**Changes needed:**
```vue
<!-- In ActionModal.vue, add form type support -->
<template v-if="modalConfig.type === 'form'">
  <CRUD6Form
      :crud6="record"
      :model="model"
      :schema="schema"
      :context="modalConfig.context"
      @success="handleFormSuccess" />
</template>
```

### Phase 2: Schema Enhancement (Backend) 🔄

**Files to modify:**
- `app/src/ServicesProvider/SchemaService.php`

**Tasks:**
- [ ] Add `addDefaultActions()` method
- [ ] Inject standard CRUD actions into all schemas
- [ ] Support action scope filtering (list/detail)
- [ ] Allow schema override of default actions

**Example:**
```php
class SchemaService {
    public function loadSchema(string $model, ?string $context = null): array
    {
        $schema = $this->loadFromFile($model);
        $schema = $this->addDefaultActions($schema, $context);
        $schema = $this->filterByContext($schema, $context);
        return $schema;
    }
}
```

### Phase 3: Component Migration 🚀

**Remove hard-coded modals:**
- [ ] Delete `CreateModal.vue` (58 lines)
- [ ] Delete `EditModal.vue` (65 lines)
- [ ] Delete `DeleteModal.vue` (84 lines)
- [ ] Rename `ActionModal.vue` → `UnifiedModal.vue`
- [ ] Update all imports and exports

**Update consumers:**
- [ ] Refactor `Info.vue` to use UnifiedModal with schema actions
- [ ] Refactor `PageList.vue` to use UnifiedModal with schema actions
- [ ] Update any other components using old modals

**Total lines removed:** ~200 lines  
**Total lines added:** ~50 lines (form embedding, scope filtering)

### Phase 4: Testing & Documentation 📝

- [ ] Test all CRUD operations via UnifiedModal
- [ ] Test custom actions (toggles, password, API calls)
- [ ] Test permission-based visibility
- [ ] Test scope filtering (list vs detail)
- [ ] Update documentation with schema examples
- [ ] Create migration guide for existing schemas

## Benefits of Unified Approach

### 1. **100% Schema-Driven** ✨
- **Before:** Hard-coded components for Create/Edit/Delete
- **After:** Everything defined in JSON schema
- **Impact:** True to sprinkle's core objective

### 2. **Radical Simplification** 🎯
- **Before:** 5 different modal components + patterns
- **After:** 1 UnifiedModal handles everything
- **Impact:** ~250 lines removed, easier maintenance

### 3. **Unlimited Extensibility** 🚀
- **Before:** Custom actions limited to ActionModal
- **After:** ANY operation (CRUD or custom) uses same pattern
- **Impact:** Schemas can define unlimited actions

### 4. **Consistent UX** 💎
- **Before:** Different modal styles for different operations
- **After:** Unified modal behavior, consistent button configs
- **Impact:** Better user experience

### 5. **Backward Compatible** ✅
- **Before:** Breaking changes for existing users
- **After:** Default actions auto-injected, existing code works
- **Impact:** Safe migration path

## Schema Examples

### Minimal Schema (Auto-gets CRUD actions)
```json
{
  "model": "products",
  "table": "products",
  "fields": {
    "name": { "type": "string", "label": "Product Name" },
    "price": { "type": "decimal", "label": "Price" }
  }
}
```
**Backend auto-injects:** create, edit, delete actions

### Custom Schema (Override defaults)
```json
{
  "model": "users",
  "actions": [
    {
      "key": "create",
      "label": "Invite User",
      "icon": "user-plus",
      "modal_config": {
        "type": "form",
        "context": "create",
        "title": "Invite New User"
      }
    },
    {
      "key": "delete",
      "enabled": false
    },
    {
      "key": "archive_user",
      "label": "Archive User",
      "icon": "archive",
      "type": "field_update",
      "field": "archived",
      "value": true,
      "confirm": "Archive {{user_name}}?",
      "modal_config": { "type": "confirm" }
    }
  ]
}
```
**Result:** Custom create, no delete, custom archive action

### Advanced Schema (Complex workflows)
```json
{
  "model": "orders",
  "actions": [
    {
      "key": "create",
      "scope": "list"
    },
    {
      "key": "edit",
      "scope": "detail",
      "visible_when": { "status": "draft" }
    },
    {
      "key": "submit_for_approval",
      "type": "field_update",
      "field": "status",
      "value": "pending_approval",
      "confirm": "Submit order #{{order_number}} for approval?",
      "visible_when": { "status": "draft" },
      "modal_config": {
        "type": "input",
        "fields": ["approval_notes"],
        "buttons": "save_cancel"
      }
    },
    {
      "key": "approve",
      "type": "api_call",
      "endpoint": "/api/orders/{id}/approve",
      "method": "POST",
      "confirm": "Approve order #{{order_number}}?",
      "visible_when": { "status": "pending_approval" },
      "permission": "approve_orders"
    }
  ]
}
```

## Migration Strategy

### For Existing CRUD6 Users

1. **No immediate changes required**
   - Default actions auto-injected
   - Old component imports still work (deprecated)
   
2. **Recommended migration**
   - Update schemas to define CRUD actions explicitly
   - Replace old modal components with UnifiedModal
   - Test action visibility and permissions

3. **Deprecation timeline**
   - v0.7: Add UnifiedModal, deprecate old modals
   - v0.8: Remove old modal components
   - v1.0: Full schema-driven architecture

### For New Projects

Start with schema-first approach:
```json
{
  "model": "my_model",
  "actions": [
    { "key": "create", "scope": "list" },
    { "key": "edit", "scope": "detail" },
    { "key": "delete", "scope": "detail" }
  ]
}
```

Use UnifiedModal everywhere:
```vue
<UnifiedModal v-for="action in actions" :action="action" />
```

## Technical Considerations

### 1. UIKit Modal Integration
- UnifiedModal uses UIKit `uk-toggle` and `uk-modal`
- Maintains compatibility with existing UF patterns
- Dynamic modal IDs based on action key + record ID

### 2. Form Context Handling
- Schema contexts (create, edit, list, detail) remain unchanged
- Form component requests specific context
- UnifiedModal passes context from `modal_config.context`

### 3. Permission System
- Actions filtered by permission before rendering
- Backend validates permissions on API calls
- Frontend hides unauthorized actions

### 4. Event Handling
```typescript
// UnifiedModal emits
@confirmed(data?: any)  // Action confirmed (with optional data)
@cancelled()            // Action cancelled
@success()              // Form submitted successfully
@error(error: any)      // Action failed
```

### 5. Action Execution Flow
```
User clicks trigger button
  ↓
UnifiedModal shows based on modal_config.type
  ↓
User interacts (confirms, fills form, etc.)
  ↓
Modal emits @confirmed with data
  ↓
Parent component calls useCRUD6Actions.executeActionWithoutConfirm()
  ↓
Action executes based on type (field_update, api_call, etc.)
  ↓
Success/error handling
```

## Code Size Comparison

### Current (After removing ConfirmActionModal, FieldEditModal)
- **Total:** 12 components, 3,349 lines
- **Modals:** ActionModal (555) + Create (58) + Edit (65) + Delete (84) = 762 lines

### Proposed (Unified Architecture)
- **Total:** 9 components, ~3,150 lines  
- **Modals:** UnifiedModal (600 lines) = 600 lines

**Reduction:** 3 components removed, ~200 lines saved

## Recommendation

**Proceed with Full Unified Architecture:**

1. ✅ **Aligns with core objective** - 100% schema-driven
2. ✅ **Minimal changes to working code** - ActionModal is 90% there
3. ✅ **Significant simplification** - 5 modal types → 1
4. ✅ **Backward compatible** - Default actions auto-injected
5. ✅ **Future-proof** - Unlimited schema extensibility

**Implementation Order:**
1. Phase 1: Enhance ActionModal → UnifiedModal (add form type)
2. Phase 2: Backend schema enhancement (default actions)
3. Phase 3: Migrate Info.vue and PageList.vue
4. Phase 4: Remove old modal components
5. Phase 5: Documentation and examples

**Timeline Estimate:**
- Phase 1: 2-3 hours
- Phase 2: 3-4 hours  
- Phase 3: 2-3 hours
- Phase 4: 1 hour
- Phase 5: 2-3 hours
- **Total:** ~12-16 hours

## Next Steps

1. Get alignment on unified architecture approach
2. Start with Phase 1 (enhance ActionModal to support form type)
3. Update schema examples with default actions
4. Test with real-world schemas
5. Complete migration progressively

---

**This architecture achieves the goal: Drive ALL functionality using JSON schemas.**
