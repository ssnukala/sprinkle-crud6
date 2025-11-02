# CRUD6 Examples

This directory contains examples, documentation, and reference materials for the CRUD6 sprinkle.

## Directory Structure

### `/schema/` - JSON Schema Examples
Contains CRUD6 JSON schema files demonstrating various features and integrations:
- **Local examples**: Product catalog, orders, categories with various field types and relationships
- **C6Admin schemas**: UserFrosting 6 integration examples (users, roles, permissions, groups, activities)

See [schema/README.md](schema/README.md) for detailed schema documentation.

### `/docs/` - Documentation
User guides and integration documentation:
- `README.md` - Main examples overview (this file when viewed in docs/)
- `UFTable-Usage-Guide.md` - Guide for using UFTable component
- `frontend-usage.md` - Frontend integration examples
- `master-detail-integration.md` - Master-detail relationship integration
- `master-detail-usage.md` - Master-detail usage guide

### `/Migrations/` - Reference Migrations
UserFrosting account sprinkle migrations for reference:
- Database schema definitions for users, roles, permissions, and related tables
- Use as reference when creating CRUD6 schemas for UserFrosting tables

See [Migrations/README.md](Migrations/README.md) for details.

### Vue Component Examples
- `AutoLookupExamples.vue` - AutoLookup field examples
- `OrderEntryPage.vue` - Order entry page example
- `ProductCategoryPage.vue` - Product category management
- `ProductCategoryPageWithAutoLookup.vue` - Advanced product-category example

### Other Examples
- `model-usage-examples.php` - PHP examples for using CRUD6Model
- `schema-caching-examples.ts` - TypeScript schema caching examples

## Quick Start

### Using Schema Examples

1. **View example schemas**: Browse `schema/` directory for JSON examples
2. **Copy and customize**: Use schemas as templates for your own models
3. **Test locally**: Place custom schemas in your app's `schema://crud6/` location

### Understanding Relationships

Check these schemas for relationship examples:
- **Many-to-many**: `schema/c6admin-users.json` (users ↔ roles)
- **Belongs-to-many-through**: `schema/c6admin-users.json` (users → roles → permissions)
- **One-to-many**: `schema/c6admin-users.json` (users → activities)

### Frontend Integration

See the Vue component examples for:
- Data table implementations
- Form handling
- Master-detail relationships
- AutoLookup field integration

## More Information

- **Main Documentation**: See the main [README.md](../README.md) in the repository root
- **Field Templates**: See [../docs/FIELD_TEMPLATE_FEATURE.md](../docs/FIELD_TEMPLATE_FEATURE.md)
- **Schema Structure**: See [schema/README.md](schema/README.md)
