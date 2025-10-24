# Multi-Field Search Implementation

## Problem Statement
Previously, the search functionality in the list view would only search in one field. The requirement was to modify the search to search across ALL fields marked as "searchable" in the schema.

## Solution Overview
Implemented a global search feature in the CRUD6Sprunje class that searches across all fields marked with `"searchable": true` in the schema configuration.

## How It Works

### Schema Configuration
Fields in the schema can be marked as searchable:

```json
{
  "fields": {
    "user_name": {
      "type": "string",
      "searchable": true,
      ...
    },
    "email": {
      "type": "string", 
      "searchable": true,
      ...
    },
    "group_id": {
      "type": "integer",
      "searchable": false,
      ...
    }
  }
}
```

### API Usage
Users can now perform global searches across all searchable fields:

```
GET /api/crud6/users?search=john
```

This will search for "john" in ALL fields marked as searchable (user_name, first_name, last_name, email).

### Search Behavior

#### Before (Not Implemented)
- Search parameter was ignored
- Only specific field filters worked: `filters[user_name]=john`

#### After (Current Implementation)
- Search parameter searches across ALL searchable fields
- Uses OR logic to match any field
- Case-insensitive matching with LIKE operator
- Works in combination with other filters

### Example SQL Query Generated

For the users table with searchable fields: user_name, first_name, last_name, email

**API Call:**
```
GET /api/crud6/users?search=john
```

**Generated SQL (simplified):**
```sql
SELECT * FROM users 
WHERE (
  user_name LIKE '%john%' OR
  first_name LIKE '%john%' OR
  last_name LIKE '%john%' OR
  email LIKE '%john%'
)
```

### Field-Specific Filtering Still Available

The global search complements existing field-specific filtering:

```
GET /api/crud6/users?search=john&filters[group_id]=1
```

This searches for "john" in searchable fields AND filters by group_id = 1.

## Code Changes

### 1. CRUD6Sprunje.php
- Added `$searchable` property to store searchable field names
- Updated `setupSprunje()` to accept searchable fields parameter
- Implemented `applyTransformations()` method to handle the `search` query parameter
  - Applies OR logic across all searchable fields
  - Uses LIKE for partial matching
  - Works with existing filters and sorts

### 2. Base.php
- Added `getSearchableFields()` method to extract searchable fields from schema
- Follows same pattern as `getSortableFields()`, `getFilterableFields()`, etc.

### 3. SprunjeAction.php
- Updated sprunje setup calls to include searchable fields
- Added `getSearchableFieldsFromSchema()` helper method
- Ensures searchable fields are passed from schema to sprunje

## Testing

Comprehensive test suite created in `CRUD6SprunjeSearchTest.php`:

1. **testSearchAcrossMultipleFields** - Verifies search matches in different fields
2. **testSearchPartialMatch** - Tests partial string matching
3. **testSearchNoMatches** - Validates empty results for non-matching terms
4. **testSearchCaseInsensitive** - Confirms case-insensitive matching
5. **testSearchOnlySearchableFields** - Ensures non-searchable fields are excluded
6. **testSearchWithNoSearchableFields** - Handles edge case gracefully

## Benefits

1. **Better User Experience**: Users can search naturally without knowing which specific field to filter
2. **Consistent with Schema**: Respects the `searchable` field attribute from the schema
3. **Flexible**: Works with any model by configuring searchable fields in schema
4. **Non-Breaking**: Existing field-specific filters continue to work
5. **Performant**: Uses indexed LIKE queries with OR logic

## Examples

### Users Table
Schema defines these searchable fields:
- user_name
- first_name
- last_name
- email

Search query: `GET /api/crud6/users?search=alice`

Matches:
- user_name: "alice123" ✓
- first_name: "Alice" ✓
- last_name: "McAlister" ✓
- email: "alice@example.com" ✓

### Groups Table  
Schema defines these searchable fields:
- name
- slug
- description

Search query: `GET /api/crud6/groups?search=admin`

Matches:
- name: "Administrators" ✓
- slug: "admin-group" ✓
- description: "Admin users with full access" ✓

## Future Enhancements

Potential improvements for future versions:
- Support for weighted search (prioritize certain fields)
- Full-text search for better performance on large datasets
- Search operators (exact match, starts with, ends with)
- Multi-term search with AND logic
