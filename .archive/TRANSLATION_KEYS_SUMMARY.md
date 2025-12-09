# CRUD6 Sprinkle Translation Keys Summary

This document lists all translation keys defined in the CRUD6 sprinkle for both English (en_US) and French (fr_FR) locales.

## Overview
- **Total Keys**: 48
- **Locales**: en_US, fr_FR
- **Status**: ✓ All keys match between locales
- **Date**: 2025-12-09

## Translation Keys by Category

### Action Messages (CRUD6.ACTION.*)
- `CRUD6.ACTION.SUCCESS` - Action completed successfully
- `CRUD6.ACTION.SUCCESS_TITLE` - Success title
- `CRUD6.ACTION.CANNOT_UNDO` - Warning that action cannot be undone

### API Messages (CRUD6.API.*)
- `CRUD6.API.SUCCESS` - API schema retrieval success

### Create Messages (CRUD6.CREATE.*)
- `CRUD6.CREATE.SUCCESS` - Create success message
- `CRUD6.CREATE.SUCCESS_TITLE` - Create success title
- `CRUD6.CREATE.ERROR` - Create error message
- `CRUD6.CREATE.ERROR_TITLE` - Create error title
- `CRUD6.CREATION_SUCCESSFUL` - Alternative create success (with name)

### Delete Messages (CRUD6.DELETE.*)
- `CRUD6.DELETE.SUCCESS` - Delete success message
- `CRUD6.DELETE.SUCCESS_TITLE` - Delete success title
- `CRUD6.DELETE.ERROR` - Delete error message
- `CRUD6.DELETE.ERROR_TITLE` - Delete error title
- `CRUD6.DELETE_CONFIRM` - Delete confirmation prompt
- `CRUD6.DELETE_DEFAULT` - Cannot delete default item message
- `CRUD6.DELETE_YES` - Delete confirmation button
- `CRUD6.DELETION_SUCCESSFUL` - Alternative delete success (with name)

### Edit/Update Messages (CRUD6.EDIT.* and CRUD6.UPDATE.*)
- `CRUD6.EDIT.SUCCESS` - Edit/retrieve success message
- `CRUD6.EDIT.ERROR` - Edit/retrieve error message
- `CRUD6.UPDATE.SUCCESS` - Update success message
- `CRUD6.UPDATE.SUCCESS_TITLE` - Update success title
- `CRUD6.UPDATE.ERROR` - Update error message
- `CRUD6.UPDATE.ERROR_TITLE` - Update error title
- `CRUD6.UPDATE_FIELD_SUCCESSFUL` - Field update success

### Relationship Messages (CRUD6.RELATIONSHIP.*)
- `CRUD6.RELATIONSHIP.ATTACH_SUCCESS` - Relationship attach success
- `CRUD6.RELATIONSHIP.DETACH_SUCCESS` - Relationship detach success

### Validation Messages (CRUD6.VALIDATION.*)
- `CRUD6.VALIDATION.ENTER_VALUE` - Enter value prompt
- `CRUD6.VALIDATION.CONFIRM` - Confirm label
- `CRUD6.VALIDATION.CONFIRM_PLACEHOLDER` - Confirm placeholder text
- `CRUD6.VALIDATION.MIN_LENGTH_HINT` - Minimum length hint
- `CRUD6.VALIDATION.MATCH_HINT` - Values must match hint
- `CRUD6.VALIDATION.FIELDS_MUST_MATCH` - Fields must match error
- `CRUD6.VALIDATION.MIN_LENGTH` - Minimum length error

### General Messages (CRUD6.*)
- `CRUD6.ADMIN_PANEL` - Admin panel title
- `CRUD6.EXCEPTION` - Generic error
- `CRUD6.ICON` - Icon label
- `CRUD6.ICON_EXPLAIN` - Icon explanation
- `CRUD6.INFO_PAGE` - Info page description
- `CRUD6.NAME` - Name label
- `CRUD6.NAME_EXPLAIN` - Name explanation
- `CRUD6.NAME_IN_USE` - Name already in use error
- `CRUD6.NONE` - No items message
- `CRUD6.NOT_EMPTY` - Cannot delete, still has associations
- `CRUD6.NOT_FOUND` - Item not found error
- `CRUD6.PAGE` - Page title
- `CRUD6.PAGE_DESCRIPTION` - Page description
- `CRUD6.TOGGLE_CONFIRM` - Toggle confirmation prompt
- `CRUD6.TOGGLE_SUCCESS` - Toggle success message

## Usage in Code

### Controllers Using Translations
- `ApiAction.php` - CRUD6.API.SUCCESS
- `CreateAction.php` - CRUD6.CREATE.SUCCESS, CRUD6.CREATE.SUCCESS_TITLE
- `DeleteAction.php` - CRUD6.DELETE.SUCCESS, CRUD6.DELETE.SUCCESS_TITLE
- `EditAction.php` - CRUD6.EDIT.SUCCESS, CRUD6.EDIT.ERROR, CRUD6.UPDATE.SUCCESS, CRUD6.UPDATE.SUCCESS_TITLE
- `UpdateFieldAction.php` - CRUD6.UPDATE_FIELD_SUCCESSFUL
- `CustomActionController.php` - CRUD6.ACTION.SUCCESS, CRUD6.ACTION.SUCCESS_TITLE
- `RelationshipAction.php` - CRUD6.RELATIONSHIP.ATTACH_SUCCESS, CRUD6.RELATIONSHIP.DETACH_SUCCESS

### Frontend Components Using Translations
- `UnifiedModal.vue` - All CRUD6.VALIDATION.* keys
- `PageRow.vue` - CRUD6.CREATE, CRUD6.INFO_PAGE
- `useCRUD6Actions.ts` - CRUD6.ACTION.SUCCESS, CRUD6.ACTION.SUCCESS_TITLE

## Notes

### Model-Specific Keys
Translation keys like `CRUD6.USER.*`, `CRUD6.GROUP.*`, etc. are NOT defined in this sprinkle.
They should be defined in the c6admin sprinkle or application-specific sprinkles.

### Placeholder Variables
Many messages use placeholder variables:
- `{{model}}` - Model display name
- `{{field}}` - Field display name
- `{{name}}` - Record name
- `{{id}}` - Record ID
- `{{count}}` - Count of items
- `{{relation}}` - Relationship name
- `{{min}}` - Minimum value
- `{{title}}` - Item title

These are replaced at runtime by the translation system.

## Verification Script

To verify all translation keys:

```bash
php -r "
\$en = include 'app/locale/en_US/messages.php';
\$fr = include 'app/locale/fr_FR/messages.php';
echo 'EN keys: ' . count(\$en['CRUD6']) . PHP_EOL;
echo 'FR keys: ' . count(\$fr['CRUD6']) . PHP_EOL;
"
```

## Changes from Previous Versions

### First Production Release (2025-12-09)
- Added CRUD6.ACTION.SUCCESS_TITLE (was missing in initial implementation)
- Added CRUD6.API.SUCCESS for API endpoint success messages
- Removed backward compatibility keys (ACTION and VALIDATION at root level)
- All 48 keys now match perfectly between en_US and fr_FR locales
