# Detail/Details Data Flow - Visual Guide

## Request Flow Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│ User navigates to detail page: /crud6/users/1                   │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ PageRow.vue or PageMasterDetail.vue                             │
│ - Loads schema: GET /api/crud6/users/schema?context=detail      │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Backend: ApiAction.php                                           │
│ - Calls SchemaService.filterSchemaForContext()                  │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Backend: SchemaService.php (lines 652-660)                      │
│ ✅ if (isset($schema['detail']))    → Include singular detail   │
│ ✅ if (isset($schema['details']))   → Include details array     │
│ Returns both (if present) in API response                       │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ API Response for users.json:                                    │
│ {                                                                │
│   "model": "users",                                              │
│   "fields": { ... },                                             │
│   "details": [                                                   │
│     {"model": "activities", "foreign_key": "user_id"},           │
│     {"model": "roles", "foreign_key": "user_id"},                │
│     {"model": "permissions", "foreign_key": "user_id"}           │
│   ]                                                              │
│ }                                                                │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Frontend: PageRow.vue / PageMasterDetail.vue                    │
│ Computed property detailConfigs:                                │
│ ✅ if (schema.details && Array.isArray())  → return details     │
│ ✅ if (schema.detail)                      → return [detail]    │
│ Result: [activities, roles, permissions]                        │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Template renders 3 CRUD6Details components:                     │
│ v-for="(config, index) in detailConfigs"                        │
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐  │
│ │ CRUD6Details #1 (activities)                               │  │
│ │ - parentModel: "users"                                     │  │
│ │ - recordId: "1"                                            │  │
│ │ - detailConfig: {"model": "activities", ...}               │  │
│ └────────────────────────────────────────────────────────────┘  │
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐  │
│ │ CRUD6Details #2 (roles)                                    │  │
│ │ - parentModel: "users"                                     │  │
│ │ - recordId: "1"                                            │  │
│ │ - detailConfig: {"model": "roles", ...}                    │  │
│ └────────────────────────────────────────────────────────────┘  │
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐  │
│ │ CRUD6Details #3 (permissions)                              │  │
│ │ - parentModel: "users"                                     │  │
│ │ - recordId: "1"                                            │  │
│ │ - detailConfig: {"model": "permissions", ...}              │  │
│ └────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Each CRUD6Details component makes data request:                 │
│                                                                  │
│ GET /api/crud6/users/1/activities                                │
│ GET /api/crud6/users/1/roles                                     │
│ GET /api/crud6/users/1/permissions                               │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Backend: SprunjeAction.php (lines 89-107)                       │
│                                                                  │
│ Request: GET /api/crud6/users/1/activities                      │
│ - model = "users"                                                │
│ - id = "1"                                                       │
│ - relation = "activities"                                        │
│                                                                  │
│ ✅ Check details array:                                         │
│    foreach ($crudSchema['details'] as $config)                  │
│      if ($config['model'] === 'activities')                     │
│        $detailConfig = $config  ← FOUND!                        │
│                                                                  │
│ ✅ Extract foreign_key: "user_id"                               │
│ ✅ Apply filter: WHERE user_id = 1                              │
│ ✅ Return activities for user #1                                │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Result: Detail tables populated with data ✅                    │
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐  │
│ │ User Activities                                            │  │
│ ├───────────┬───────────┬─────────────────────────────────┤  │
│ │ Date      │ Type      │ Description                     │  │
│ ├───────────┼───────────┼─────────────────────────────────┤  │
│ │ 2024-01-15│ login     │ Login successful                │  │
│ │ 2024-01-14│ logout    │ User logged out                 │  │
│ │ 2024-01-13│ update    │ Profile updated                 │  │
│ └───────────┴───────────┴─────────────────────────────────┘  │
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐  │
│ │ User Roles                                                 │  │
│ ├───────────┬───────────┬─────────────────────────────────┤  │
│ │ Name      │ Slug      │ Description                     │  │
│ ├───────────┼───────────┼─────────────────────────────────┤  │
│ │ Admin     │ admin     │ Full system access              │  │
│ │ Editor    │ editor    │ Content management              │  │
│ └───────────┴───────────┴─────────────────────────────────┘  │
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐  │
│ │ User Permissions                                           │  │
│ ├───────────┬───────────┬─────────────────────────────────┤  │
│ │ Slug      │ Name      │ Description                     │  │
│ ├───────────┼───────────┼─────────────────────────────────┤  │
│ │ uri_users │ View Users│ View user list                  │  │
│ │ create... │ Create... │ Create new records              │  │
│ └───────────┴───────────┴─────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
```

## Backward Compatibility Flow

```
┌──────────────────────────────────────────────────────────────────┐
│ Legacy Schema with singular 'detail' object                     │
│ {                                                                │
│   "model": "orders",                                             │
│   "detail": {                                                    │
│     "model": "order_items",                                      │
│     "foreign_key": "order_id"                                    │
│   }                                                              │
│ }                                                                │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ SchemaService.php - Returns both:                               │
│ {                                                                │
│   "detail": {"model": "order_items", ...}  ← Singular           │
│ }                                                                │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Frontend detailConfigs computed property:                       │
│ if (schema.detail)                                               │
│   return [schema.detail]  ← Convert to array                    │
│                                                                  │
│ Result: [{"model": "order_items", ...}]                          │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Template renders 1 CRUD6Details component:                      │
│ v-for="(config, index) in detailConfigs"                        │
│                                                                  │
│ ┌────────────────────────────────────────────────────────────┐  │
│ │ CRUD6Details (order_items)                                 │  │
│ │ - detailConfig: {"model": "order_items", ...}              │  │
│ └────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Data request: GET /api/crud6/orders/1/order_items               │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ SprunjeAction.php - Backward compatibility check:               │
│                                                                  │
│ ✅ Check details array: No 'details' key → skip                 │
│ ✅ Check singular detail:                                       │
│    if ($crudSchema['detail']['model'] === 'order_items')        │
│      $detailConfig = $crudSchema['detail']  ← FOUND!            │
│                                                                  │
│ ✅ Apply filter: WHERE order_id = 1                             │
│ ✅ Return order items for order #1                              │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│ Result: Legacy schema works perfectly ✅                        │
└──────────────────────────────────────────────────────────────────┘
```

## Code Path Comparison

### Before Fix (BROKEN for details array)
```
Request: /api/crud6/users/1/activities
↓
SprunjeAction.php
  $detailConfig = $crudSchema['detail'] ?? null  ← NULL (no 'detail' key)
  ↓
  if ($detailConfig && $detailConfig['model'] === 'activities')  ← FALSE
    // Never executed
  ↓
  Falls through to main model listing  ❌
  ↓
  Returns ALL users (wrong data)  ❌
  ↓
Frontend receives wrong structure  ❌
  ↓
Empty table displayed  ❌
```

### After Fix (WORKS for both formats)
```
Request: /api/crud6/users/1/activities
↓
SprunjeAction.php
  $detailConfig = null
  ↓
  Check details array:
    foreach ($crudSchema['details'])
      if ($config['model'] === 'activities')
        $detailConfig = $config  ← FOUND! ✅
  ↓
  Apply filter: WHERE user_id = 1  ✅
  ↓
  Return activities for user #1  ✅
  ↓
Frontend receives correct data  ✅
  ↓
Table populated with rows  ✅
```

## All Components in the Flow

### Backend Components
1. **ApiAction.php** - Schema endpoint
2. **SchemaService.php** - Schema filtering (includes both formats)
3. **SprunjeAction.php** - Data fetching (handles both formats)
4. **CRUD6Injector.php** - Middleware for model injection

### Frontend Components
1. **PageRow.vue** - Standard detail page with detailConfigs
2. **PageMasterDetail.vue** - Master-detail page with detailConfigs
3. **CRUD6Details.vue** - Single detail table component
4. **CRUD6Info.vue** - Master record display

### Supporting Components
1. **UFSprunjeTable** - Data table rendering
2. **UFCardBox** - Card container for each detail section

## Summary

### ✅ All Code Paths Updated
- Backend handles both `detail` and `details`
- Frontend handles both formats with `detailConfigs`
- API responses include both (when present)
- Components work with either format

### ✅ Zero Breaking Changes
- Legacy schemas with `detail` continue to work
- New schemas with `details` work correctly
- All existing functionality preserved

### ✅ Complete Coverage
- sprinkle-c6admin schemas fully supported (users.json, groups.json)
- Multiple detail sections display correctly
- Foreign key filtering works for all relations
- Backward compatibility maintained
