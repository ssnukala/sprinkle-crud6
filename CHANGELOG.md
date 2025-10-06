# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.5.0] - 2024-10-06

### Added
- **Frontend Integration**: Complete Vue.js frontend components merged from theme-crud6
  - Added `CreateModal.vue` component for creating new records
  - Added `EditModal.vue` component for editing existing records
  - Added `DeleteModal.vue` component for delete confirmation
  - Added `Form.vue` component for dynamic form generation based on schema
  - Added `Info.vue` component for displaying record information
  - Added `Users.vue` component for related users display
  - Added complete `PageList.vue` view with data table, filtering, and pagination
  - Added complete `PageRow.vue` view with detail display and edit functionality
- **Plugin System**: Added Vue plugin for automatic component registration
  - Created `app/assets/plugins/crud6.ts` for global component registration
  - Components are now automatically available as `UFCRUD6*` globals
- **Documentation**: Added comprehensive frontend documentation
  - Updated README.md with Vue.js integration section
  - Created MIGRATION_FROM_THEME_CRUD6.md migration guide
  - Added component usage examples and composable documentation
- **Package Exports**: Added plugins export path to package.json
- **Test Scripts**: Added npm test scripts to package.json

### Changed
- **BREAKING**: Removed dependency on `@ssnukala/theme-crud6`
  - All frontend components now included in this package
  - No breaking changes for import paths or component usage
  - See MIGRATION_FROM_THEME_CRUD6.md for migration instructions
- **Updated**: README.md to reflect integrated frontend architecture
- **Updated**: package.json version bumped to 0.5.0
- **Updated**: Added UIkit as peer dependency

### Removed
- Dependency on `@ssnukala/theme-crud6` (components now integrated)

### Migration
- Users upgrading from 0.4.x should:
  1. Remove `@ssnukala/theme-crud6` from package.json
  2. Update to sprinkle-crud6 v0.5.0
  3. No code changes required - all imports remain the same
- See MIGRATION_FROM_THEME_CRUD6.md for detailed migration guide

### Architecture
This release aligns sprinkle-crud6 with UserFrosting 6 patterns:
- Follows sprinkle-admin architecture where frontend and backend are in the same package
- Single package simplifies dependency management
- Consistent with UserFrosting 6 sprinkle conventions

## [0.4.3] - Previous Releases

See individual change summary documents:
- FUNCTION_RENAME_SUMMARY.md
- FRONTEND_FIX_SUMMARY.md
- CHANGELOG_SCHEMA_CACHING.md
- SERVICE_PROVIDER_UPDATE.md
- TESTING_SUMMARY.md
- UF6_STANDARDS_REVIEW.md

---

## Version Support

| Version | Status | Frontend | Notes |
|---------|--------|----------|-------|
| 0.5.x+ | ✅ Current | Integrated | All-in-one package (recommended) |
| 0.4.x | ⚠️ Legacy | Requires theme-crud6 | Update recommended |

## Links

- [GitHub Repository](https://github.com/ssnukala/sprinkle-crud6)
- [Issue Tracker](https://github.com/ssnukala/sprinkle-crud6/issues)
- [Migration Guide](MIGRATION_FROM_THEME_CRUD6.md)
