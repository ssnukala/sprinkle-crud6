# Relationship Details Implementation - Complete Guide

## Overview

This document provides comprehensive documentation for the relationship/details feature implementation in CRUD6, covering all three phases of development.

## Phase 1: Basic Details Support

### Features Implemented
- Parse `details` section from JSON schemas
- Query many_to_many relationships through pivot tables
- Apply field filtering using `list_fields` from schema configuration
- Return formatted response with details object containing title, rows, and count

### Usage

**Schema Configuration:**
```json
{
    "model": "roles",
    "table": "roles",
    "relationships": [
        {
            "name": "permissions",
            "type": "many_to_many",
            "pivot_table": "permission_roles",
            "foreign_key": "role_id",
            "related_key": "permission_id"
        }
    ],
    "details": [
        {
            "model": "permissions",
            "list_fields": ["slug", "name", "description"],
            "title": "ROLE.PERMISSIONS"
        }
    ]
}
```

**API Request:**
```
GET /api/crud6/roles/1
```

**Response:**
```json
{
    "id": 1,
    "name": "Site Administrator",
    "slug": "site-admin",
    "details": {
        "permissions": {
            "title": "ROLE.PERMISSIONS",
            "rows": [
                {"id": 1, "slug": "uri_users", "name": "View Users", "description": "..."},
                {"id": 2, "slug": "create_user", "name": "Create User", "description": "..."}
            ],
            "count": 2
        }
    }
}
```

## Phase 2: Advanced Relationship Support

### Features Implemented
- Support for `belongs_to_many_through` relationships
- GET endpoints for individual relationships: `/api/crud6/{model}/{id}/{relationship}`
- Full pagination support with `page` and `per_page` parameters
- Sorting support with `sort` field and `direction` (ASC/DESC) parameters
- Search/filtering support with `search` parameter
- Field selection with `fields` parameter

### belongs_to_many_through Configuration

**Schema Example:**
```json
{
    "relationships": [
        {
            "name": "posts",
            "type": "belongs_to_many_through",
            "through": "users",
            "foreign_key": "country_id",
            "through_key": "user_id"
        }
    ]
}
```

### Relationship Endpoint Usage

**Paginated Request:**
```
GET /api/crud6/roles/1/permissions?page=2&per_page=20&sort=slug&direction=ASC&search=user
```

**Response:**
```json
{
    "relationship": "permissions",
    "title": "ROLE.PERMISSIONS",
    "type": "many_to_many",
    "rows": [...],
    "count": 20,
    "total": 50,
    "page": 2,
    "per_page": 20,
    "total_pages": 3
}
```

### Query Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `page` | integer | Page number (default: 1) | `page=2` |
| `per_page` | integer | Items per page (default: 10, max: 100) | `per_page=25` |
| `sort` | string | Field name to sort by | `sort=name` |
| `direction` | string | Sort direction: ASC or DESC (default: ASC) | `direction=DESC` |
| `search` | string | Search term to filter results | `search=admin` |
| `fields` | array | Specific fields to return | `fields[]=id&fields[]=name` |

## Phase 3: Optimization & Error Handling

### Performance Optimizations

#### 1. Schema Consolidation
Phase 3 implements schema consolidation to minimize database queries by batch-loading all related schemas in a single call.

**Before (Phase 1-2):**
```php
// Each detail loaded schema individually
foreach ($detailsConfig as $detail) {
    $schema = $this->schemaService->getSchema($detail['model']); // N queries
}
```

**After (Phase 3):**
```php
// All schemas loaded in one batch
$relatedSchemas = $this->schemaService->loadRelatedSchemas($crudSchema, 'list'); // 1 query
foreach ($detailsConfig as $detail) {
    $schema = $relatedSchemas[$detail['model']] ?? null; // Already loaded
}
```

**Impact:** Reduces schema loading from N+1 queries to 1 query where N is the number of relationships.

#### 2. Query Optimization
- Uses INNER JOIN for efficient relationship queries
- Field filtering applied at database level (not in PHP)
- Primary keys always included to ensure data integrity

### Error Handling

#### Comprehensive Error Logging
All errors are logged with full context for debugging:

```php
$this->logger->error("CRUD6 [EditAction] Failed to load detail", [
    'related_model' => $relatedModel,
    'error' => $e->getMessage(),
    'error_type' => get_class($e),
]);
```

#### Graceful Degradation
If one relationship fails to load, others continue:

```json
{
    "details": {
        "permissions": {
            "title": "ROLE.PERMISSIONS",
            "rows": [...],
            "count": 10
        },
        "users": {
            "title": "ROLE.USERS",
            "rows": [],
            "count": 0,
            "error": "Failed to load relationship data"
        }
    }
}
```

#### Validation
All relationship configurations are validated:
- Required fields checked (pivot_table, foreign_key, related_key)
- Missing fields reported in error logs
- Empty results returned instead of exceptions for non-critical errors

### Enhanced Documentation

All methods include comprehensive PHPDoc comments with:
- Full parameter descriptions
- Return type documentation
- Exception documentation
- Phase 3 optimization notes
- Usage examples

## Architecture

### Class Diagram

```
EditAction
├── handleRead() - GET request handler
├── loadDetailsFromSchema() - Phase 3: Uses schema consolidation
├── queryRelationship() - Routes to specific query method
├── queryManyToManyRelationship() - Phase 3: Accepts pre-loaded schema
└── queryBelongsToManyThroughRelationship() - Phase 3: Accepts pre-loaded schema

RelationshipAction
├── __invoke() - Routes GET/POST/DELETE
├── handleGetRelationship() - GET endpoint with pagination
├── getManyToManyRelationship() - Paginated many-to-many query
└── getBelongsToManyThroughRelationship() - Paginated belongs_to_many_through query
```

### Data Flow

```
1. Client Request
   ↓
2. CRUD6Injector (middleware)
   - Loads main model schema
   ↓
3. EditAction::handleRead()
   ↓
4. loadDetailsFromSchema()
   - Batch-loads all related schemas (Phase 3)
   ↓
5. For each detail:
   queryRelationship()
   ↓
6. queryManyToManyRelationship() or
   queryBelongsToManyThroughRelationship()
   - Uses pre-loaded schema (Phase 3)
   - Executes optimized JOIN query
   ↓
7. Returns formatted response with details
```

## Performance Metrics

### Query Reduction
- **Before Phase 3:** 1 + N schema queries (where N = number of relationships)
- **After Phase 3:** 1 schema query (consolidated)

### Example Scenario
For a role with 3 relationships (users, permissions, groups):
- **Phase 1-2:** 4 schema queries (1 for role + 3 for relationships)
- **Phase 3:** 1 schema query (all loaded in batch)
- **Improvement:** 75% reduction in schema queries

## Error Recovery

### Scenario: Invalid Pivot Table
```json
{
    "details": {
        "permissions": {
            "title": "ROLE.PERMISSIONS",
            "rows": [],
            "count": 0,
            "error": "Failed to load relationship data"
        }
    }
}
```

**Log Entry:**
```
[ERROR] CRUD6 [EditAction] Invalid many_to_many relationship configuration
{
    "relationship": {...},
    "missing_fields": {"pivot_table": true}
}
```

## Best Practices

### 1. Schema Configuration
- Always define `list_fields` for better performance
- Use meaningful `title` values for i18n support
- Validate relationship configurations in schema

### 2. API Usage
- Use pagination for large datasets
- Apply field filtering to reduce payload size
- Use search parameter for client-side filtering

### 3. Performance
- Enable schema caching in production
- Use appropriate `per_page` values (10-50 recommended)
- Consider relationship endpoint for large related datasets

## Troubleshooting

### No Details Returned
1. Check schema has `details` array configured
2. Verify relationship names match in `details` and `relationships`
3. Check debug logs for schema loading errors

### Empty Relationship Results
1. Verify pivot table exists and has data
2. Check foreign_key and related_key values match table columns
3. Review query execution logs

### Performance Issues
1. Ensure schema caching is enabled
2. Use `list_fields` to limit columns
3. Apply pagination for large datasets
4. Check database indexes on pivot table columns

## Future Enhancements

Potential Phase 4 improvements:
- Eager loading with relationship pre-fetching
- GraphQL-style field selection
- Real-time relationship updates via WebSockets
- Relationship data caching
- Bulk relationship operations

## Migration Guide

### From Phase 1 to Phase 3
No breaking changes - Phase 3 is fully backward compatible:
- All Phase 1 & 2 APIs continue to work
- Performance improvements automatic
- Error handling enhanced without API changes

### Schema Updates Required
None - existing schemas work without modification.

## Support

For issues or questions:
1. Check debug logs with `crud6.debug_mode = true`
2. Verify schema configuration
3. Review this documentation
4. Check UserFrosting 6 documentation for framework patterns
