# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **UserFrosting 6.0.0-beta.8 Compatibility**: Aligned with beta.8 release
  - Added Node.js engine specification (`>= 18`) in `package.json`
  - Confirmed compatibility with Node 18+ (currently using Node 20 in CI)
  - Verified `vite.config.ts` optimizeDeps configuration is correct for CRUD6
    - CRUD6 requires `limax` and `lodash.deburr` in optimizeDeps (used in useCRUD6Api.ts)
    - This is different from main UserFrosting app which only needs `uikit`
  - Updated README to indicate testing with beta.8
  - No breaking changes to integration test workflows (avoiding issues from reverted PR #366)
- **PHP Version**: Updated minimum PHP version requirement from 8.1 to 8.4
  - Updated `composer.json` to require `php: ^8.4`
  - Updated all workflow templates and testing configurations
  - Updated all documentation to reflect PHP 8.4 requirement
  - Aligns with UserFrosting 6 updated requirements
- **Documentation Organization**: Restructured documentation for cleaner repository
  - Moved `FRONTEND_INTEGRATION_TESTING.md` to `docs/`
  - Moved `FRONTEND_TESTING.md` to `docs/`
  - Moved `INTEGRATION_TESTING_QUICK_START.md` to `docs/`
  - Moved `READY_FOR_TESTING.md` to `.archive/` (historical document)
  - Root now contains only essential files: `README.md` and `CHANGELOG.md`
  - Updated all internal documentation references
- **Package Configuration**: Prepared for release
  - Updated `package.json` version to 0.6.1.8
  - Fixed repository URLs in `package.json` to point to correct GitHub repository
  - Verified all dependencies are current
  - Confirmed archive exclusions are appropriate

### Migration Notes
- Update your PHP environment to 8.4 or later
- Update composer dependencies: `composer update`
- No code changes required - this is a configuration update only
- All APIs and components remain backward compatible

## [0.6.1.8] - 2026-01-12

### Fixed
- **Vite Module Loading**: Added dependency optimization for CommonJS modules
  - Configured `optimizeDeps.include` in `vite.config.ts` to pre-bundle `limax` and `lodash.deburr`
  - Fixes error: "The requested module '.../lodash.deburr/index.js' does not provide an export named 'default'"
  - Resolves integration test failures where login page failed to load
  - See `.archive/VITE_COMMONJS_MODULE_FIX.md` for detailed explanation
- **Import Removal**: Removed explicit import of `UFFormValidationError` component from `Form.vue`
  - `UFFormValidationError` is globally registered by `@userfrosting/theme-pink-cupcake` plugin
  - Aligns with UserFrosting 6 patterns where theme components are auto-registered (see `GroupForm.vue`)
  - Resolves Vite build error: "Missing './components/UFFormValidationError.vue' specifier in '@userfrosting/theme-pink-cupcake' package"
  - Component usage unchanged - still works via global registration

## [0.6.0] - 2024-10-06

### Changed
- **Structure**: Flattened component directory from `components/Pages/CRUD6/Base/` to `components/CRUD6/`
  - Follows UserFrosting 6 flat component structure pattern
- **Files**: Consolidated 5 interface files into single `types.ts` file
  - Better organization with clear sections for single record and list operations
- **Components**: Maintained UserFrosting 6 naming conventions
  - Views: `PageList.vue`, `PageRow.vue` (consistent with sprinkle-admin)
  - Global components: `UFCRUD6ListPage`, `UFCRUD6RowPage`

### Removed
- Legacy `UFTableCRUD6.vue` component (unused)
- Redundant directory nesting in component structure
- 5 separate interface files (consolidated into `types.ts`)

### Improved
- **Organization**: 40% fewer files (20 → 12), flatter structure
- **Consistency**: Follows UserFrosting 6 sprinkle-admin patterns
- **Documentation**: Better organized type definitions with clear sections
- **Maintainability**: Easier to navigate and find code

### Migration
No breaking changes for users. All component names remain the same:
```vue
<!-- Usage remains unchanged -->
<UFCRUD6ListPage />
<UFCRUD6RowPage />
```

All import paths and composable functions remain unchanged (backward compatible).

See [CODE_CLEANUP_SUMMARY.md](CODE_CLEANUP_SUMMARY.md) for detailed changes.

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
