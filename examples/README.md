# CRUD6 Examples

This directory contains examples, documentation, and reference materials for the CRUD6 sprinkle.

## Directory Structure

### `/schema/` - JSON Schema Examples
Contains CRUD6 JSON schema files demonstrating various features and integrations:
- **Product examples**: Various product schemas with different layouts and features
- **Relationship examples**: Users, roles, permissions, groups with UserFrosting 6 integration
- **Order management**: Orders and order details
- **Smart lookup examples**: AutoLookup field demonstrations
- **Field templates**: Template file and inline template examples

See [schema/README.md](schema/README.md) for detailed schema documentation.

### `/locale/` - Locale File Examples
Example locale files showing proper translation patterns:
- `translation-example-messages.php` - Demonstrates `{{&KEY}}` nested translation syntax

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

### Test & Validation Scripts

Development and validation scripts for testing CRUD6 functionality. These are **reference scripts** used during sprinkle development and are not meant for end-user execution:

**Test Scripts:**
- `test-c6admin-relationships.php` - Tests UserFrosting admin relationship integration (requires sprinkle-c6admin)
- `test-c6admin-schema.php` - Tests UserFrosting admin schema compatibility (requires sprinkle-c6admin)
- `test-nested-lookup.php` - Tests nested lookup functionality
- `test-relationship-fix.php` - Tests belongs_to_many_through relationship fixes

**Validation Scripts:**
- `validate-autolookup.php` - Validates AutoLookup component file structure
- `validate-changes.php` - Validates schema optimization and field template features
- `validate-fix.php` - Validates belongs_to_many_through fix
- `verify-debug-mode.php` - Demonstrates debug mode configuration
- `verify-api-calls.sh` - Shell script for verifying API endpoint functionality
- `verify-frontend-debug.html` - Frontend debug verification page

> **Note:** These scripts are for **sprinkle development and testing purposes only**. They are included as examples and references but are not necessary for using CRUD6 in your application. They are not included in production packages (see `package.json` `files` section).

## Quick Start

### Using Schema Examples

1. **View example schemas**: Browse `schema/` directory for JSON examples
2. **Copy and customize**: Use schemas as templates for your own models
3. **Test locally**: Place custom schemas in your app's `schema://crud6/` location

### Understanding Relationships

Check these schemas for relationship examples:
- **Many-to-many**: `schema/users.json` (users ↔ roles via `role_users`)
- **Belongs-to-many-through**: `schema/users.json` (users → roles → permissions via `role_users` and `permission_roles`)
- **One-to-many**: `schema/users.json` (users → activities)

### Frontend Integration

See the Vue component examples for:
- Data table implementations
- Form handling
- Master-detail relationships
- AutoLookup field integration

## Translation Examples

### Nested Translation Pattern

The CRUD6 sprinkle supports nested translations using the `{{&KEY}}` syntax. This allows you to embed translation keys within locale messages, and they will be recursively translated at render time.

**Example Locale**: `locale/translation-example-messages.php`

**Key Pattern:**
```php
// In locale file
'DISABLE_CONFIRM' => 'Are you sure you want to disable <strong>{{user_name}}</strong>?<br/>{{&ACTION.CANNOT_UNDO}}',

'ACTION' => [
    'CANNOT_UNDO' => 'This action cannot be undone.',
],
```

The `{{&ACTION.CANNOT_UNDO}}` will be recursively translated to "This action cannot be undone."

**Full Guide**: See [../docs/NESTED_TRANSLATION_USAGE_GUIDE.md](../docs/NESTED_TRANSLATION_USAGE_GUIDE.md) for complete documentation.

## More Information

- **Main Documentation**: See the main [README.md](../README.md) in the repository root
- **Field Templates**: See [../docs/FIELD_TEMPLATE_FEATURE.md](../docs/FIELD_TEMPLATE_FEATURE.md)
- **Schema Structure**: See [schema/README.md](schema/README.md)
