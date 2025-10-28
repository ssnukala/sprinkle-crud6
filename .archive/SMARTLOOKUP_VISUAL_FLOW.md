# SmartLookup Field Type - Visual Flow Diagram

## User Interaction Flow

```
┌─────────────────────────────────────────────────────────────────┐
│  1. USER TYPES IN FIELD                                         │
│  ┌──────────────────────────┐                                   │
│  │ Customer: [john____]     │  (User types "john")              │
│  └──────────────────────────┘                                   │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  2. DEBOUNCE WAIT (300ms)                                       │
│  ⏱ Wait for user to finish typing...                           │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  3. API REQUEST                                                  │
│  GET /api/crud6/customers?search=john&size=20                   │
│                                                                  │
│  Headers:                                                        │
│  - Content-Type: application/json                               │
│  - Authorization: Bearer {token}                                │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  4. BACKEND PROCESSING                                           │
│  - Sprunje searches "searchable" fields in customers schema     │
│  - Matches: name, email, phone (if marked searchable)           │
│  - Applies search filter: WHERE name LIKE '%john%'              │
│                             OR email LIKE '%john%'               │
│  - Limits to 20 results                                          │
│  - Returns JSON response                                         │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  5. API RESPONSE                                                 │
│  {                                                               │
│    "count": 2,                                                   │
│    "count_filtered": 2,                                          │
│    "rows": [                                                     │
│      {                                                           │
│        "id": 1,                  ← ID value (stored)            │
│        "name": "John Doe",       ← DESC value (displayed)       │
│        "email": "john@email.com"                                 │
│      },                                                          │
│      {                                                           │
│        "id": 5,                                                  │
│        "name": "Johnny Smith",                                   │
│        "email": "johnny@email.com"                               │
│      }                                                           │
│    ]                                                             │
│  }                                                               │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  6. DROPDOWN DISPLAY                                             │
│  ┌──────────────────────────┐                                   │
│  │ Customer: [john____]     │                                   │
│  └──────────────────────────┘                                   │
│  ┌──────────────────────────────────────┐                       │
│  │ ▸ John Doe                           │ ← Highlighted         │
│  │   Johnny Smith                       │                       │
│  └──────────────────────────────────────┘                       │
│                                                                  │
│  User can:                                                       │
│  - Click on a result                                             │
│  - Use ↑↓ arrow keys to navigate                               │
│  - Press Enter to select                                         │
│  - Press Escape to close                                         │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  7. USER SELECTS                                                 │
│  User clicks "John Doe" or presses Enter                        │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  8. VALUE STORED & DISPLAYED                                     │
│  ┌──────────────────────────┐                                   │
│  │ Customer: [John Doe ✕]   │  ← Display shows "John Doe"       │
│  └──────────────────────────┘                                   │
│                                                                  │
│  Form Data:                                                      │
│  {                                                               │
│    "customer_id": 1  ← ID value stored in field                 │
│  }                                                               │
│                                                                  │
│  ✕ button allows clearing the selection                         │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  9. FORM SUBMISSION                                              │
│  POST /api/crud6/orders                                         │
│  {                                                               │
│    "customer_id": 1,        ← ID value sent to backend         │
│    "order_number": "ORD-123",                                    │
│    "order_date": "2024-10-28"                                   │
│  }                                                               │
└─────────────────────────────────────────────────────────────────┘
```

## Component Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    PageMasterDetail.vue                          │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  MASTER FORM (Edit Mode)                                   │ │
│  │  ┌──────────────────────────────────────────────────────┐  │ │
│  │  │  Field: customer_id (type: "smartlookup")            │  │ │
│  │  │  ↓                                                    │  │ │
│  │  │  <CRUD6AutoLookup                                    │  │ │
│  │  │    model="customers"                                 │  │ │
│  │  │    id-field="id"                                     │  │ │
│  │  │    display-field="name"                              │  │ │
│  │  │    v-model="record['customer_id']"                   │  │ │
│  │  │  />                                                   │  │ │
│  │  └──────────────────────────────────────────────────────┘  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  DETAIL GRID (Edit Mode)                                   │ │
│  │  ┌──────────────────────────────────────────────────────┐  │ │
│  │  │  <CRUD6DetailGrid                                    │  │ │
│  │  │    v-model="detailRecords"                           │  │ │
│  │  │  />                                                   │  │ │
│  │  │  ┌─────────────────────────────────────────────────┐ │  │ │
│  │  │  │  For each row:                                   │ │  │ │
│  │  │  │  Field: product_id (type: "smartlookup")         │ │  │ │
│  │  │  │  ↓                                                │ │  │ │
│  │  │  │  <CRUD6AutoLookup                                │ │  │ │
│  │  │  │    model="products"                              │ │  │ │
│  │  │  │    id-field="id"                                 │ │  │ │
│  │  │  │    display-field="name"                          │ │  │ │
│  │  │  │    v-model="row['product_id']"                   │ │  │ │
│  │  │  │  />                                               │ │  │ │
│  │  │  └─────────────────────────────────────────────────┘ │  │ │
│  │  └──────────────────────────────────────────────────────┘  │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow in Master-Detail Save

```
┌─────────────────────────────────────────────────────────────────┐
│  USER CLICKS SAVE BUTTON                                         │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  COLLECT FORM DATA                                               │
│                                                                  │
│  masterData = {                                                  │
│    customer_id: 1,      ← SmartLookup value (ID)               │
│    order_number: "ORD-123",                                     │
│    order_date: "2024-10-28"                                     │
│  }                                                               │
│                                                                  │
│  detailRecords = [                                              │
│    {                                                             │
│      product_id: 10,    ← SmartLookup value (ID)               │
│      quantity: 5,                                                │
│      unit_price: 29.99,                                          │
│      _action: 'create'  ← Internal flag                         │
│    },                                                            │
│    {                                                             │
│      id: 42,                                                     │
│      product_id: 15,    ← SmartLookup value (ID)               │
│      quantity: 2,                                                │
│      unit_price: 49.99,                                          │
│      _action: 'update'  ← Internal flag                         │
│    }                                                             │
│  ]                                                               │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  SAVE MASTER RECORD                                              │
│  POST /api/crud6/orders                                         │
│  {                                                               │
│    "customer_id": 1,                                             │
│    "order_number": "ORD-123",                                   │
│    "order_date": "2024-10-28"                                   │
│  }                                                               │
│                                                                  │
│  Response: { "data": { "id": 100 } }  ← New order ID           │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  SAVE DETAIL RECORDS                                             │
│                                                                  │
│  For detail #1 (_action: 'create'):                            │
│  POST /api/crud6/order_lines                                    │
│  {                                                               │
│    "order_id": 100,       ← Foreign key set automatically      │
│    "product_id": 10,      ← SmartLookup ID value               │
│    "quantity": 5,                                                │
│    "unit_price": 29.99                                           │
│  }                                                               │
│                                                                  │
│  For detail #2 (_action: 'update'):                            │
│  PUT /api/crud6/order_lines/42                                  │
│  {                                                               │
│    "order_id": 100,                                              │
│    "product_id": 15,      ← SmartLookup ID value               │
│    "quantity": 2,                                                │
│    "unit_price": 49.99                                           │
│  }                                                               │
└─────────────────────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────────────────────┐
│  SUCCESS NOTIFICATION                                            │
│  ✓ Successfully created order with 2 detail records             │
│  → Navigate to /crud6/orders                                    │
└─────────────────────────────────────────────────────────────────┘
```

## Schema Configuration Example

```json
{
  "model": "order",
  "title": "Order Management",
  "detail_editable": {
    "model": "order_lines",
    "foreign_key": "order_id",
    "fields": ["product_id", "quantity", "unit_price"]
  },
  "fields": {
    "customer_id": {
      "type": "smartlookup",
      "label": "Customer",
      "lookup_model": "customers",
      "lookup_id": "id",
      "lookup_desc": "name"
    }
  }
}
```

## Key Points

1. **ID vs Description**: SmartLookup stores the ID but displays the description
2. **Standard API**: Uses `/api/crud6/{model}` - no custom endpoints needed
3. **Searchable Fields**: Backend searches all fields marked `searchable: true`
4. **Debouncing**: Prevents excessive API calls while user types
5. **Master-Detail**: Works seamlessly in both master forms and detail grids
6. **Single Transaction**: All saves handled together in master-detail mode
