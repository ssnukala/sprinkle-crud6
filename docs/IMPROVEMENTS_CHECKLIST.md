# CRUD6 Improvements Checklist

A prioritized list of improvements extracted from the [Comprehensive Review](./COMPREHENSIVE_REVIEW.md). This serves as an actionable checklist for optimizing the repository.

---

## 🚀 Phase 1: Quick Wins (Estimated: 1-2 weeks)

These are low-effort, high-impact improvements that can be implemented immediately.

### Code Fixes

- [x] **Remove hardcoded Sprunje defaults** ✅
  - File: `app/src/Sprunje/CRUD6Sprunje.php` (Line 42)
  - Change: `protected array $sortable = ["name"];` → `protected array $sortable = [];`
  - Impact: Eliminates potential bugs when models don't have a `name` field
  - Effort: 5 minutes

### Documentation

- [x] **Create Quick Start guide** ✅
  - File: `docs/QUICK_START.md`
  - Status: Completed

- [x] **Add architecture diagram to documentation** ✅
  - File: `docs/COMPREHENSIVE_REVIEW.md`
  - Status: Completed

- [x] **Add inline JSDoc/PHPDoc examples** ✅
  - Files: `app/assets/composables/*.ts`
  - Add `@example` blocks to public functions
  - Status: Completed - Added comprehensive examples to useCRUD6Api, useCRUD6Schema, useCRUD6Actions, useCRUD6FieldRenderer, useCRUD6Relationships

---

## 🔧 Phase 2: Core Improvements (Estimated: 2-4 weeks)

These improvements require more effort but provide significant value.

### PHP Backend

- [x] **Extract common controller logic to traits** ✅
  - Create: `app/src/Controller/Traits/TransformsData.php`
  - Refactor: CreateAction, EditAction, UpdateFieldAction
  - Benefits: ~30% code reduction, easier testing
  - Effort: 4-6 hours

  ```php
  // New trait with common methods
  trait TransformsData
  {
      protected function transformAndValidate(array $schema, array $params): array
      {
          $requestSchema = $this->getRequestSchema($schema);
          $data = $this->transformer->transform($requestSchema, $params);
          $this->validateData($requestSchema, $data);
          return $data;
      }
  }
  ```

- [x] **Create centralized CRUD6Config class** ✅
  - Create: `app/src/Config/CRUD6Config.php`
  - Purpose: Centralize all config access with type-safe methods
  - Effort: 2-3 hours

  ```php
  // Centralized config access
  class CRUD6Config
  {
      public function isDebugMode(): bool;
      public function getDefaultPageSize(): int;
      public function getMaxPageSize(): int;
      public function getSchemaPath(): string;
  }
  ```

- [x] **Add PSR-16 cache support to SchemaService** ✅
  - File: `app/src/ServicesProvider/SchemaService.php`
  - Add optional persistent cache for production
  - Status: Completed with two-tier caching (in-memory + PSR-16)

### Vue Frontend

- [x] **Create shared API client** ✅
  - Create: `app/assets/utils/apiClient.ts`
  - Centralize axios configuration and error handling
  - Effort: 2-3 hours

  ```typescript
  // Shared axios instance with interceptors
  export const crud6Api = axios.create({
      baseURL: '/api/crud6',
      headers: { 'Content-Type': 'application/json' }
  })
  
  crud6Api.interceptors.response.use(
      response => response,
      error => { /* centralized error handling */ }
  )
  ```

- [x] **Add API Reference documentation** ✅
  - Create: `docs/API_REFERENCE.md`
  - Document all endpoints with request/response examples
  - Status: Completed with comprehensive endpoint documentation

---

## 🏗️ Phase 3: Advanced Features (Estimated: 4-8 weeks)

These are larger improvements that add significant new functionality.

### Field Type System

- [x] **Implement FieldTypeRegistry** ✅
  - Create: `app/src/FieldTypes/FieldTypeRegistry.php`
  - Create: `app/src/FieldTypes/FieldTypeInterface.php`
  - Create: `app/src/FieldTypes/AbstractFieldType.php`
  - Create: `app/src/FieldTypes/Types/CurrencyFieldType.php`
  - Create: `app/src/ServicesProvider/FieldTypeServiceProvider.php`
  - Purpose: Pluggable field type system for custom types
  - Status: Completed with currency example

  ```php
  interface FieldTypeInterface
  {
      public function getType(): string;
      public function transform(mixed $value): mixed;
      public function cast(mixed $value): mixed;
      public function getPhpType(): string;
      public function getValidationRules(): array;
      public function isVirtual(): bool;
  }
  ```

- [ ] **Create Vue field renderer plugin system**
  - Create: `app/assets/utils/fieldRenderers.ts`
  - Purpose: Register custom field renderers for new types
  - Effort: 6-8 hours

### Code Consolidation

- [x] **Create HashesPasswords trait** ✅
  - Create: `app/src/Controller/Traits/HashesPasswords.php`
  - Refactor: CreateAction, EditAction, UpdateFieldAction to use trait
  - Benefits: Eliminated duplicate password hashing code
  - Status: Completed

### Comparable Packages Analysis

- [x] **Document comparable packages** ✅
  - Create: `docs/COMPARABLE_PACKAGES.md`
  - Purpose: Comprehensive comparison with 15+ similar tools
  - Status: Completed

### Event System

- [ ] **Add hook/event system for CRUD operations**
  - Create: `app/src/Events/CRUD6Events.php`
  - Add events: BEFORE_CREATE, AFTER_CREATE, BEFORE_UPDATE, etc.
  - Effort: 6-8 hours

### CLI Tools

- [ ] **Add schema validation CLI command**
  - Create bakery command to validate all schemas
  - Output: List of errors/warnings per schema
  - Effort: 4-6 hours

- [ ] **Add OpenAPI documentation generation**
  - Auto-generate OpenAPI/Swagger docs from schemas
  - Effort: 8-12 hours

---

## 🔮 Phase 4: Future Enhancements (Estimated: 8+ weeks)

These are significant features for future consideration.

### Real-time Support

- [ ] **Add WebSocket/real-time updates**
  - Consider Laravel Echo or Pusher integration
  - Auto-refresh lists when data changes
  - Effort: 2-3 weeks

### Additional Protocols

- [ ] **Add GraphQL endpoint**
  - Auto-generate GraphQL schema from JSON schemas
  - Complement existing REST API
  - Effort: 2-4 weeks

### Developer Tools

- [ ] **Schema generation from existing database**
  - CLI command to introspect DB and generate schema files
  - Effort: 1-2 weeks

- [ ] **Plugin architecture**
  - Formal system for extending CRUD6
  - Plugin marketplace concept
  - Effort: 3-4 weeks

---

## 📊 Priority Matrix

| Improvement | Impact | Effort | Priority | Status |
|------------|--------|--------|----------|--------|
| Remove hardcoded Sprunje defaults | Medium | Very Low | **P1** | ✅ Done |
| Add JSDoc/PHPDoc examples | Medium | Low | **P1** | ✅ Done |
| Extract controller traits | High | Medium | **P2** | ✅ Done |
| Create shared API client | Medium | Low | **P2** | ✅ Done |
| Create CRUD6Config class | Medium | Low | **P2** | ✅ Done |
| Add PSR-16 cache support | Medium | Medium | **P2** | ✅ Done |
| Add API Reference docs | Medium | Medium | **P2** | ✅ Done |
| Implement FieldTypeRegistry | High | High | **P3** | ✅ Done |
| Create HashesPasswords trait | Medium | Low | **P3** | ✅ Done |
| Document comparable packages | Medium | Medium | **P3** | ✅ Done |
| Add event/hook system | High | Medium | **P3** | Pending |
| Add OpenAPI generation | Medium | High | **P3** | Pending |
| Add GraphQL support | Medium | Very High | **P4** | Pending |
| Real-time updates | Medium | Very High | **P4** | Pending |

---

## 🎯 Recommended Starting Points

1. **Completed** ✅:
   - Remove hardcoded `$sortable` in Sprunje
   - Create `TransformsData` trait
   - Create shared API client
   - Create `CRUD6Config` class
   - Implement FieldTypeRegistry
   - Create HashesPasswords trait
   - Document comparable packages
   - Add JSDoc examples to composables
   - Add PSR-16 cache support to SchemaService
   - Create API Reference documentation

2. **Next Priority**:
   - Add hook/event system for CRUD operations
   - Add OpenAPI documentation generation
   - Create Vue field renderer plugin system

3. **Future Sprint**:
   - Add GraphQL support
   - Add real-time/WebSocket updates
   - Schema generation from existing database

---

## How to Use This Checklist

1. Pick items from Phase 1 first - they're quick wins
2. Create GitHub issues for Phase 2+ items
3. Mark items complete as you implement them
4. Update this document as new improvements are identified

---

*Last updated: November 2025*
*Recent updates: Added FieldTypeRegistry, HashesPasswords trait, comparable packages analysis*
