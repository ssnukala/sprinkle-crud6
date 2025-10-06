# Dynamic Detail Section - Architecture Flow

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         SCHEMA CONFIGURATION                         │
│                      (app/schema/crud6/groups.json)                  │
├─────────────────────────────────────────────────────────────────────┤
│  {                                                                   │
│    "model": "groups",                                                │
│    "detail": {                                                       │
│      "model": "users",              ← Defines relationship           │
│      "foreign_key": "group_id",     ← Specifies FK                  │
│      "list_fields": ["user_name", "email", "first_name"]            │
│    }                                                                 │
│  }                                                                   │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        FRONTEND (PageRow.vue)                        │
├─────────────────────────────────────────────────────────────────────┤
│  1. Load schema via useCRUD6Schema()                                │
│  2. Check if schema.detail exists                                   │
│  3. If yes, render CRUD6Details component                           │
│  4. If no, skip detail section                                      │
│                                                                      │
│  <CRUD6Details                                                       │
│      :recordId="1"              ← Parent record ID                  │
│      :parentModel="groups"      ← Parent model name                 │
│      :detailConfig="schema.detail" /> ← Detail config               │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    GENERIC DETAIL COMPONENT                          │
│                  (components/CRUD6/Details.vue)                      │
├─────────────────────────────────────────────────────────────────────┤
│  1. Receive props: recordId, parentModel, detailConfig             │
│  2. Load detail model schema (users schema)                         │
│  3. Build API URL: /api/crud6/groups/1/users                       │
│  4. Render UFSprunjeTable with dynamic fields                       │
│  5. Format fields based on type (boolean, date, etc.)              │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     API REQUEST TO BACKEND                           │
│                  GET /api/crud6/groups/1/users                       │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    BACKEND (SprunjeAction.php)                       │
├─────────────────────────────────────────────────────────────────────┤
│  1. Receive request for groups/1/users                              │
│  2. Extract relation parameter: "users"                             │
│  3. Get schema detail config                                        │
│  4. Validate relation matches detail.model                          │
│  5. Extract foreign_key from config: "group_id"                     │
│  6. Apply query filter: WHERE group_id = 1                          │
│  7. Use UserSprunje to fetch filtered data                          │
│  8. Return JSON response                                            │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        RESPONSE DATA                                 │
├─────────────────────────────────────────────────────────────────────┤
│  {                                                                   │
│    "rows": [                                                         │
│      {                                                               │
│        "user_name": "john_doe",                                     │
│        "email": "john@example.com",                                 │
│        "first_name": "John",                                        │
│        "flag_enabled": true                                         │
│      }                                                               │
│    ],                                                                │
│    "count": 1                                                        │
│  }                                                                   │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    FRONTEND RENDERING                                │
│                  (UFSprunjeTable in Details.vue)                     │
├─────────────────────────────────────────────────────────────────────┤
│  ┌────────────┬─────────────────┬────────────┬─────────────┐       │
│  │ User Name  │ Email           │ First Name │ Status      │       │
│  ├────────────┼─────────────────┼────────────┼─────────────┤       │
│  │ john_doe   │ john@example.com│ John       │ ✓ ENABLED   │       │
│  └────────────┴─────────────────┴────────────┴─────────────┘       │
│                                                                      │
│  Features: Sorting, Searching, Pagination                           │
└─────────────────────────────────────────────────────────────────────┘
```

## Component Hierarchy

```
PageRow.vue
│
├── CRUD6Info.vue
│   └── Display parent record (Group) information
│
└── CRUD6Details.vue (NEW - Generic Detail Component)
    │
    ├── Props:
    │   ├── recordId: "1"
    │   ├── parentModel: "groups"
    │   └── detailConfig: {
    │         model: "users",
    │         foreign_key: "group_id",
    │         list_fields: [...]
    │       }
    │
    ├── Composables:
    │   └── useCRUD6Schema() - Load detail model schema
    │
    └── UFSprunjeTable
        ├── dataUrl: "/api/crud6/groups/1/users"
        ├── Dynamic Headers (from list_fields)
        └── Dynamic Columns (formatted by field type)
```

## Type System Flow

```
┌─────────────────────────────────────────┐
│    TypeScript Interface Definition      │
│    (useCRUD6Schema.ts)                 │
├─────────────────────────────────────────┤
│  export interface DetailConfig {        │
│    model: string                        │
│    foreign_key: string                  │
│    list_fields: string[]                │
│    title?: string                       │
│  }                                      │
│                                         │
│  export interface CRUD6Schema {         │
│    model: string                        │
│    table: string                        │
│    fields: Record<...>                  │
│    detail?: DetailConfig  ← Optional    │
│  }                                      │
└─────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│       Component Props (Details.vue)     │
├─────────────────────────────────────────┤
│  const props = defineProps<{            │
│    recordId: string                     │
│    parentModel: string                  │
│    detailConfig: DetailConfig           │
│  }>()                                   │
└─────────────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────┐
│      Type Export (index.ts)             │
├─────────────────────────────────────────┤
│  export type {                          │
│    CRUD6Schema,                         │
│    SchemaField,                         │
│    DetailConfig                         │
│  } from './useCRUD6Schema'              │
└─────────────────────────────────────────┘
```

## Decision Flow

```
START: User navigates to /groups/1
    │
    ▼
Load groups schema
    │
    ▼
Does schema have 'detail' property?
    │
    ├─── NO ──────────► Display only CRUD6Info (parent record)
    │                   Skip detail section
    │
    └─── YES ─────────► Continue to render detail section
                            │
                            ▼
                    Does user have 'view_crud6_field' permission?
                            │
                            ├─── NO ──────► Skip detail section
                            │
                            └─── YES ─────► Render CRUD6Details component
                                                │
                                                ▼
                                        Load detail model schema (users)
                                                │
                                                ▼
                                        Fetch data from API
                                                │
                                                ▼
                                        Display related records table
```

## Field Type Formatting

```
Detail Component receives field value and type
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│              Field Type Switch                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  type === 'boolean'?                                        │
│  ├── YES → <UFLabel severity="success|danger">             │
│  │          {ENABLED|DISABLED}                             │
│  │                                                          │
│  type === 'date'?                                          │
│  ├── YES → new Date(value).toLocaleDateString()            │
│  │                                                          │
│  type === 'datetime'?                                      │
│  ├── YES → new Date(value).toLocaleString()                │
│  │                                                          │
│  └── DEFAULT → {{ value }}  (plain text)                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Backend Query Construction

```
Request: GET /api/crud6/groups/1/users
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│           SprunjeAction::__invoke()                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. Parse route parameters:                                │
│     - parentModel = "groups"                               │
│     - recordId = 1                                         │
│     - relation = "users"                                   │
│                                                             │
│  2. Load schema for "groups"                               │
│                                                             │
│  3. Extract detail config:                                 │
│     detailConfig = schema['detail']                        │
│     {                                                       │
│       "model": "users",                                    │
│       "foreign_key": "group_id"                            │
│     }                                                       │
│                                                             │
│  4. Validate: relation === detailConfig['model']           │
│     "users" === "users" ✓                                  │
│                                                             │
│  5. Build query with foreign key:                          │
│     SELECT * FROM users                                    │
│     WHERE group_id = 1                                     │
│     ORDER BY ...                                           │
│     LIMIT ... OFFSET ...                                   │
│                                                             │
│  6. Apply Sprunje filters, sorts, pagination               │
│                                                             │
│  7. Return JSON response                                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## Schema Loading Priority

```
┌───────────────────────────────────────────────────────────┐
│                  Schema Loading Flow                       │
├───────────────────────────────────────────────────────────┤
│                                                            │
│  Step 1: Load Parent Schema (groups)                      │
│          ↓                                                 │
│          GET /api/crud6/groups/schema                      │
│          ↓                                                 │
│          Returns: { model, fields, detail, ... }           │
│                                                            │
│  Step 2: Check for detail config                          │
│          ↓                                                 │
│          detail = schema.detail                            │
│          If null → Skip detail section                     │
│          If exists → Continue                              │
│                                                            │
│  Step 3: Load Detail Model Schema (users)                 │
│          ↓                                                 │
│          GET /api/crud6/users/schema                       │
│          ↓                                                 │
│          Returns: { model, fields, ... }                   │
│          ↓                                                 │
│          Used for field labels and types                   │
│                                                            │
│  Step 4: Build Detail View                                │
│          ↓                                                 │
│          Headers: From detail.list_fields                  │
│          Labels: From users schema fields                  │
│          Types: From users schema field types              │
│                                                            │
└───────────────────────────────────────────────────────────┘
```

## Comparison: Old vs New

### OLD APPROACH (Hardcoded)
```
┌────────────────────────────────────────────┐
│  PageRow.vue                               │
│  ┌──────────────────────────────────────┐ │
│  │  Hardcoded: CRUD6Users component     │ │
│  │  - Only works for groups → users     │ │
│  │  - Not reusable                      │ │
│  │  - Requires code change for new      │ │
│  │    relationships                     │ │
│  └──────────────────────────────────────┘ │
└────────────────────────────────────────────┘
```

### NEW APPROACH (Dynamic)
```
┌────────────────────────────────────────────┐
│  Schema Configuration                      │
│  ┌──────────────────────────────────────┐ │
│  │  "detail": {                         │ │
│  │    "model": "users",                 │ │
│  │    "foreign_key": "group_id"         │ │
│  │  }                                   │ │
│  └──────────────────────────────────────┘ │
└────────────────────────────────────────────┘
              ↓
┌────────────────────────────────────────────┐
│  PageRow.vue                               │
│  ┌──────────────────────────────────────┐ │
│  │  Generic: CRUD6Details component     │ │
│  │  - Works with any relationship       │ │
│  │  - Fully reusable                    │ │
│  │  - No code change needed             │ │
│  │  - Just update schema                │ │
│  └──────────────────────────────────────┘ │
└────────────────────────────────────────────┘
```

## Key Benefits Visualization

```
┌─────────────────────────────────────────────────────────────┐
│                    DECLARATIVE                              │
│  ┌───────────────────────────────────────────────────────┐ │
│  │  Configuration in schema JSON, not in code            │ │
│  │  Easy to read and understand                          │ │
│  └───────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    TYPE-SAFE                                │
│  ┌───────────────────────────────────────────────────────┐ │
│  │  TypeScript interfaces ensure correct structure       │ │
│  │  Compile-time validation                              │ │
│  │  IDE autocomplete and error detection                 │ │
│  └───────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    REUSABLE                                 │
│  ┌───────────────────────────────────────────────────────┐ │
│  │  Single component for all one-to-many relationships   │ │
│  │  Groups → Users                                        │ │
│  │  Categories → Products                                 │ │
│  │  Orders → Items                                        │ │
│  │  Projects → Tasks                                      │ │
│  └───────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                 BACKWARD COMPATIBLE                         │
│  ┌───────────────────────────────────────────────────────┐ │
│  │  Models without detail config work as before          │ │
│  │  No breaking changes                                   │ │
│  │  Optional feature                                      │ │
│  └───────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```
