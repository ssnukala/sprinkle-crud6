# Vitest Test Fixtures

This directory contains JSON fixtures for Vitest frontend tests. Using these fixtures instead of inline mock data makes tests more realistic and maintainable.

## Directory Structure

```
examples/test/vitest/
├── fixtures/          # Test data (records)
│   ├── products.json  # Sample product records
│   └── users.json     # Sample user records
└── schemas/           # Schema definitions
    ├── products.json  # Product model schema
    └── users.json     # User model schema
```

## Usage in Tests

### Load Schema Fixture

```typescript
import { loadSchemaFixture } from '../fixtures'

const schema = loadSchemaFixture('products')
// Returns complete schema object from examples/test/vitest/schemas/products.json
```

### Load Data Fixture

```typescript
import { loadDataFixture, loadSingleRecordFixture } from '../fixtures'

// Load all records
const products = loadDataFixture('products')
// Returns array of product records

// Load single record
const product = loadSingleRecordFixture('products', 0)
// Returns first product record
```

### Schema Field Helpers

```typescript
import { 
  getEditableFields, 
  getViewableFields, 
  getListableFields,
  filterFields
} from '../fixtures'

const schema = loadSchemaFixture('products')

// Get field lists based on schema
const editableFields = getEditableFields(schema)  // ['name', 'sku', 'price', ...]
const viewableFields = getViewableFields(schema)  // ['id', 'name', 'sku', ...]
const listableFields = getListableFields(schema)  // ['id', 'name', 'sku', 'price']

// Filter record data
const record = loadSingleRecordFixture('products')
const formData = filterFields(record, editableFields)
// Returns only editable fields from record
```

## Example Test

```typescript
import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { loadSchemaFixture, loadSingleRecordFixture } from '../fixtures'
import Form from '../../components/CRUD6/Form.vue'

describe('Form with real fixtures', () => {
  it('renders form with product schema and data', () => {
    const schema = loadSchemaFixture('products')
    const product = loadSingleRecordFixture('products', 0)
    
    // Mock composable to return fixture data
    vi.mock('../../composables/useCRUD6Schema', () => ({
      useCRUD6Schema: () => ({
        schema: { value: schema },
        loading: { value: false }
      })
    }))
    
    // Mount component with real data
    const wrapper = mount(Form, {
      props: {
        modelValue: product
      }
    })
    
    // Test uses realistic data
    expect(wrapper.text()).toContain(product.name)
  })
})
```

## Benefits

### 1. Realistic Test Data
- Uses actual schema definitions from examples
- Test data matches production structure
- Easier to spot data-related issues

### 2. Maintainability
- Update fixtures once, affects all tests
- No duplicated inline mock data
- Schema changes automatically flow to tests

### 3. Consistency
- Same fixtures used across all tests
- Predictable test data
- Easier to understand test failures

### 4. Documentation
- Fixtures serve as examples
- Shows expected data structure
- Useful for new developers

## Adding New Fixtures

### 1. Create Schema File

Create `examples/test/vitest/schemas/model_name.json`:

```json
{
  "model": "model_name",
  "title": "Model Title",
  "table": "model_table",
  "fields": {
    "id": {
      "type": "integer",
      "label": "ID",
      "editable": false,
      "viewable": true,
      "listable": true
    },
    "name": {
      "type": "string",
      "label": "Name",
      "required": true,
      "editable": true,
      "viewable": true,
      "listable": true
    }
  }
}
```

### 2. Create Data File

Create `examples/test/vitest/fixtures/model_name.json`:

```json
[
  {
    "id": 1,
    "name": "Test Record 1"
  },
  {
    "id": 2,
    "name": "Test Record 2"
  }
]
```

### 3. Use in Tests

```typescript
const schema = loadSchemaFixture('model_name')
const data = loadDataFixture('model_name')
```

## CI Integration

The Vitest workflow (`.github/workflows/vitest-tests.yml`) automatically verifies fixtures exist before running tests:

```yaml
- name: Setup test fixtures
  run: |
    test -d "examples/test/vitest/fixtures"
    test -d "examples/test/vitest/schemas"
```

Fixtures are part of the repository and don't need to be generated during CI.

## Best Practices

### 1. Keep Fixtures Simple
- Include only necessary fields
- Use realistic but simple data
- Avoid overly complex scenarios

### 2. Provide Multiple Records
- At least 2-3 records per model
- Include edge cases (e.g., inactive records)
- Cover different field values

### 3. Match Production Schema
- Base fixtures on actual schema files from `examples/schema/`
- Update when schema changes
- Validate JSON structure

### 4. Document Special Cases
- Add comments in fixture JSON if needed
- Document why specific test data was chosen
- Note any dependencies between fixtures

## Troubleshooting

### Fixture Not Found
```
Error: Failed to load schema fixture for "model_name"
```
**Solution**: Ensure the file exists at `examples/test/vitest/schemas/model_name.json`

### Invalid JSON
```
Error: Unexpected token in JSON
```
**Solution**: Validate JSON syntax with a linter or `json.parse()` test

### Field Mismatch
```
Error: Expected field "name" but not found in fixture
```
**Solution**: Verify fixture data matches schema definition

## Related Files

- `app/assets/tests/fixtures.ts` - Fixture loader utility
- `app/assets/tests/fixtures.test.ts` - Fixture loader tests
- `.github/workflows/vitest-tests.yml` - CI workflow that uses fixtures
- `examples/schema/` - Production schema files (reference)
