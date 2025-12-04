# CRUD6 Frontend Locale Files

This directory contains CRUD6 translations in JSON format for use with Vue i18n in UserFrosting 6 applications.

## Files

- `en_US.json` - English (US) translations
- `fr_FR.json` - French translations
- `index.ts` - TypeScript export module

## Usage

The CRUD6 Vue components use `$t()` for translation keys like `VALIDATION.ENTER_VALUE`, `ACTION.CANNOT_UNDO`, etc. These translations need to be loaded into your Vue i18n instance.

### In Your UserFrosting App

**Option 1: Import and merge with your i18n messages**

```typescript
import { en_US as crud6_en_US, fr_FR as crud6_fr_FR } from '@ssnukala/sprinkle-crud6/locale'

// In your i18n setup
const i18n = createI18n({
  locale: 'en_US',
  messages: {
    en_US: {
      ...yourExistingMessages_en_US,
      ...crud6_en_US
    },
    fr_FR: {
      ...yourExistingMessages_fr_FR,
      ...crud6_fr_FR
    }
  }
})
```

**Option 2: Add messages dynamically**

```typescript
import { en_US as crud6_en_US, fr_FR as crud6_fr_FR } from '@ssnukala/sprinkle-crud6/locale'

// After creating your i18n instance
i18n.global.mergeLocaleMessage('en_US', crud6_en_US)
i18n.global.mergeLocaleMessage('fr_FR', crud6_fr_FR)
```

## Translation Keys

### VALIDATION Keys
Used in dynamically generated form modals:
- `VALIDATION.ENTER_VALUE` - Placeholder for input fields
- `VALIDATION.CONFIRM` - Confirmation field label
- `VALIDATION.CONFIRM_PLACEHOLDER` - Confirmation field placeholder
- `VALIDATION.MIN_LENGTH_HINT` - Minimum length hint message
- `VALIDATION.MATCH_HINT` - Fields must match hint
- `VALIDATION.FIELDS_MUST_MATCH` - Validation error message
- `VALIDATION.MIN_LENGTH` - Minimum length validation error

### ACTION Keys
Used in action confirmation modals:
- `ACTION.CANNOT_UNDO` - Warning that action cannot be undone

### CRUD6 Keys
Generic CRUD operation messages:
- `CRUD6.CREATE.*` - Create success/error messages
- `CRUD6.UPDATE.*` - Update success/error messages
- `CRUD6.DELETE.*` - Delete success/error messages
- `CRUD6.RELATIONSHIP.*` - Relationship operation messages

## Backend Integration

These JSON files are automatically generated from the PHP locale files at `app/locale/{locale}/messages.php`.

The backend UserFrosting i18n system translates schema keys (like field labels, action labels) before sending them to the frontend. However, dynamically generated UI elements in Vue components need client-side translations via `$t()`.

## For Consuming Sprinkles

If your sprinkle (like sprinkle-c6admin) has model-specific translations (e.g., `CRUD6.USER.*`, `CRUD6.ROLE.*`), you should:

1. Create your own `app/assets/locale/` directory
2. Generate JSON files from your PHP locale files
3. Export them via your package.json
4. Import and merge them in your application

This ensures all translation keys used by CRUD6 components are available to the Vue i18n system.
