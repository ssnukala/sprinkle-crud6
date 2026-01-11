# Model-Specific Test Removal

## Removed Files

The following hardcoded model-specific test files have been **removed** (not deprecated):

1. **`FrontendUserWorkflowTest.php`** - Hardcoded User workflow tests
2. **`CRUD6UsersIntegrationTest.php`** - Hardcoded User CRUD tests
3. **`CRUD6GroupsIntegrationTest.php`** - Hardcoded Group CRUD tests
4. **`RoleUsersRelationshipTest.php`** - Hardcoded Role-User relationship tests

## Rationale

These tests violated CRUD6's core principle of being schema-driven by hardcoding model-specific logic. They have been completely removed in favor of the generic, schema-driven approach.

## Replacement

All functionality is now covered by **SchemaBasedApiTest.php** with these generic test methods:

### 1. `testSchemaDrivenCrudOperations($modelName)`
- Uses `@dataProvider schemaProvider`
- Tests CRUD operations (list, create, read, update, delete) for ANY model
- Automatically runs for all schemas in `app/schema/crud6/`
- No hardcoded model logic

### 2. `testSchemaDrivenRelationships($modelName)`
- Uses `@dataProvider schemaProvider`
- Validates relationship definitions for models with relationships
- Tests has_many, belongs_to_many, belongs_to relationships
- Skips models without relationships

### 3. `testSchemaDrivenCustomActions($modelName)`
- Uses `@dataProvider schemaProvider`
- Validates custom action definitions
- Tests action schema structure
- Skips models without custom actions

### 4. Model-Specific Comprehensive Tests (Retained)
For comprehensive testing, the following model-specific tests are **retained** in SchemaBasedApiTest:
- `testUsersModelCompleteApiIntegration()` - Full workflow for users model
- `testRolesModelCompleteApiIntegration()` - Full workflow for roles model
- `testGroupsModelCompleteApiIntegration()` - Full workflow for groups model
- `testPermissionsModelCompleteApiIntegration()` - Full workflow for permissions model

These are kept because they test complex multi-step workflows and edge cases that are difficult to test generically.

## Benefits

✅ **Schema-Driven**: All tests driven by schema configuration
✅ **No Duplication**: Single test suite for all models
✅ **Scalable**: Add new models by adding schema files only
✅ **Maintainable**: No need to write model-specific tests
✅ **Consistent**: Same test logic for all models

## Adding New Models

To test a new model:
1. Add schema file to `app/schema/crud6/{model}.json`
2. Tests automatically run via data provider
3. No test code changes needed

## Migration Notes

- **Before**: Had to write separate test files for each model
- **After**: Schema files automatically drive tests
- **Result**: Cleaner codebase, easier maintenance, true schema-driven testing
