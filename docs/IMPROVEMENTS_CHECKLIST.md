# CRUD6 Improvements Checklist

A prioritized list of improvements extracted from the [Comprehensive Review](./COMPREHENSIVE_REVIEW.md). This serves as an actionable checklist for optimizing the repository.

---

## 🚀 Phase 1: Quick Wins (Estimated: 1-2 weeks)

These are low-effort, high-impact improvements that can be implemented immediately.

### Code Fixes

- [ ] **Remove hardcoded Sprunje defaults**
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

- [ ] **Add inline JSDoc/PHPDoc examples**
  - Files: `app/assets/composables/*.ts`
  - Add `@example` blocks to public functions
  - Effort: 2-3 hours

---

## 🔧 Phase 2: Core Improvements (Estimated: 2-4 weeks)

These improvements require more effort but provide significant value.

### PHP Backend

- [ ] **Extract common controller logic to traits**
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

- [ ] **Create centralized CRUD6Config class**
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

- [ ] **Add PSR-16 cache support to SchemaService**
  - File: `app/src/ServicesProvider/SchemaService.php`
  - Add optional persistent cache for production
  - Effort: 3-4 hours

### Vue Frontend

- [ ] **Create shared API client**
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

- [ ] **Add API Reference documentation**
  - Create: `docs/API_REFERENCE.md`
  - Document all endpoints with request/response examples
  - Effort: 4-6 hours

---

## 🏗️ Phase 3: Advanced Features (Estimated: 4-8 weeks)

These are larger improvements that add significant new functionality.

### Field Type System

- [ ] **Implement FieldTypeRegistry**
  - Create: `app/src/FieldTypes/FieldTypeRegistry.php`
  - Create: `app/src/FieldTypes/FieldTypeInterface.php`
  - Purpose: Pluggable field type system for custom types
  - Effort: 8-12 hours

  ```php
  interface FieldTypeInterface
  {
      public function transform(mixed $value): mixed;
      public function validate(mixed $value): bool;
      public function getValidationRules(): array;
  }
  ```

- [ ] **Create Vue field renderer plugin system**
  - Create: `app/assets/utils/fieldRenderers.ts`
  - Purpose: Register custom field renderers for new types
  - Effort: 6-8 hours

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

| Improvement | Impact | Effort | Priority |
|------------|--------|--------|----------|
| Remove hardcoded Sprunje defaults | Medium | Very Low | **P1** |
| Add JSDoc/PHPDoc examples | Medium | Low | **P1** |
| Extract controller traits | High | Medium | **P2** |
| Create shared API client | Medium | Low | **P2** |
| Create CRUD6Config class | Medium | Low | **P2** |
| Add PSR-16 cache support | Medium | Medium | **P2** |
| Implement FieldTypeRegistry | High | High | **P3** |
| Add event/hook system | High | Medium | **P3** |
| Add OpenAPI generation | Medium | High | **P3** |
| Add GraphQL support | Medium | Very High | **P4** |
| Real-time updates | Medium | Very High | **P4** |

---

## 🎯 Recommended Starting Points

1. **Immediate** (can do right now):
   - Remove hardcoded `$sortable` in Sprunje
   - Add JSDoc examples to composables

2. **This Sprint**:
   - Create `TransformsData` trait
   - Create shared API client
   - Create `CRUD6Config` class

3. **Next Sprint**:
   - Add PSR-16 cache support
   - Create API Reference documentation

---

## How to Use This Checklist

1. Pick items from Phase 1 first - they're quick wins
2. Create GitHub issues for Phase 2+ items
3. Mark items complete as you implement them
4. Update this document as new improvements are identified

---

*Last updated: November 2025*
