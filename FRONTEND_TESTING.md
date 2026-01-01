# Frontend Testing Guide

This document describes the frontend testing approach for the CRUD6 sprinkle, following UserFrosting 6 standards.

## Testing Stack

- **Framework**: [Vitest](https://vitest.dev/) - Fast unit test framework for Vite projects
- **Component Testing**: [@vue/test-utils](https://test-utils.vuejs.org/) - Official testing utilities for Vue 3
- **Environment**: [happy-dom](https://github.com/capricorn86/happy-dom) - Lightweight DOM implementation
- **Plugins**: [@vitejs/plugin-vue](https://github.com/vitejs/vite-plugin-vue) - Vue 3 support for Vite

## Test Scripts

```bash
# Run all tests once
npm test

# Run tests in watch mode (auto-rerun on file changes)
npm run test:watch

# Run tests with UI interface
npm run test:ui

# Run tests with coverage report
npm run test:coverage
```

## Test Structure

Tests are organized in `app/assets/tests/` following this structure:

```
app/assets/tests/
├── setup.ts                      # Global test setup and mocks
├── components/                   # Component tests
│   ├── ToggleSwitch.test.ts     # Toggle switch component
│   ├── Details.test.ts          # Details display component
│   ├── Form.test.ts             # CRUD form component
│   ├── Info.test.ts             # Info display component
│   ├── UnifiedModal.test.ts     # Modal component
│   └── imports.test.ts          # Component import verification
├── views/                        # View/page tests
│   ├── PageList.test.ts         # List page
│   └── PageRow.test.ts          # Detail page
├── router/                       # Router tests
│   └── routes.test.ts           # Route configuration
├── useCRUD6ValidationAdapter.test.ts  # Validation adapter
└── useMasterDetail.test.ts      # Master-detail composable
```

## Writing Tests

### Basic Component Test Example

```typescript
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import MyComponent from '../../components/MyComponent.vue'

describe('MyComponent.vue', () => {
  it('renders correctly', () => {
    const wrapper = mount(MyComponent, {
      props: {
        title: 'Test Title'
      }
    })

    expect(wrapper.exists()).toBe(true)
    expect(wrapper.text()).toContain('Test Title')
  })
})
```

### Testing with Router

```typescript
import { createRouter, createMemoryHistory } from 'vue-router'

const router = createRouter({
  history: createMemoryHistory(),
  routes: [
    { path: '/', component: { template: '<div>Home</div>' } }
  ]
})

router.push('/some-route')
await router.isReady()

const wrapper = mount(MyComponent, {
  global: {
    plugins: [router]
  }
})
```

### Testing with Pinia Store

```typescript
import { createPinia, setActivePinia } from 'pinia'

beforeEach(() => {
  setActivePinia(createPinia())
})

const wrapper = mount(MyComponent, {
  global: {
    plugins: [createPinia()]
  }
})
```

### Mocking Composables

```typescript
import { vi } from 'vitest'

vi.mock('../../composables/useCRUD6Api', () => ({
  useCRUD6Api: () => ({
    getRow: vi.fn(() => Promise.resolve({ id: 1, name: 'Test' })),
    apiLoading: { value: false }
  })
}))
```

## Global Mocks

The `setup.ts` file provides global mocks for common UserFrosting dependencies:

### Translator Mock

```typescript
useTranslator() // Returns mock translator that echoes keys
```

### Alerts Store Mock

```typescript
useAlertsStore() // Returns mock alerts store
```

### Page Meta Mock

```typescript
usePageMeta() // Returns mock page metadata functions
```

## Test Patterns

### Component Props Testing

```typescript
it('accepts custom props', () => {
  const wrapper = mount(MyComponent, {
    props: {
      modelValue: true,
      disabled: false,
      id: 'custom-id'
    }
  })

  expect(wrapper.props('modelValue')).toBe(true)
})
```

### Event Emission Testing

```typescript
it('emits events on user action', async () => {
  const wrapper = mount(MyComponent)
  
  await wrapper.find('button').trigger('click')
  
  expect(wrapper.emitted()).toHaveProperty('confirmed')
  expect(wrapper.emitted('confirmed')?.[0]).toEqual([{ data: 'value' }])
})
```

### Async Testing

```typescript
import { flushPromises } from '@vue/test-utils'

it('loads data asynchronously', async () => {
  const wrapper = mount(MyComponent)
  
  await flushPromises() // Wait for all promises to resolve
  
  expect(wrapper.text()).toContain('Loaded Data')
})
```

### Mocking Child Components

When child components are complex or not relevant to the test:

```typescript
const mockChild = {
  name: 'ChildComponent',
  template: '<div class="mock-child"><slot /></div>',
  props: ['data']
}

const wrapper = mount(ParentComponent, {
  global: {
    components: {
      ChildComponent: mockChild
    }
  }
})
```

## Testing Best Practices

### 1. Follow AAA Pattern

Structure tests with **Arrange**, **Act**, **Assert**:

```typescript
it('performs action', async () => {
  // Arrange: Set up test data and conditions
  const wrapper = mount(MyComponent, { props: { value: 10 } })
  
  // Act: Perform the action being tested
  await wrapper.find('button').trigger('click')
  
  // Assert: Verify the expected outcome
  expect(wrapper.emitted('update:value')?.[0]).toEqual([11])
})
```

### 2. Test User Interactions

Focus on testing how users interact with components, not implementation details:

```typescript
it('allows user to toggle state', async () => {
  const wrapper = mount(ToggleSwitch, {
    props: { modelValue: false }
  })
  
  await wrapper.find('input[type="checkbox"]').setValue(true)
  
  expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true])
})
```

### 3. Keep Tests Isolated

Each test should be independent and not rely on other tests:

```typescript
beforeEach(() => {
  // Reset state before each test
  setActivePinia(createPinia())
})
```

### 4. Use Descriptive Test Names

Test names should clearly describe what is being tested:

```typescript
// Good
it('displays error message when validation fails')

// Bad
it('works correctly')
```

### 5. Mock External Dependencies

Mock API calls, stores, and external services to keep tests fast and reliable:

```typescript
vi.mock('axios')
const mockedAxios = vi.mocked(axios)
mockedAxios.get.mockResolvedValue({ data: { success: true } })
```

## Common Issues and Solutions

### Issue: "Cannot find module"

**Solution**: Check import paths are correct relative to test file location.

```typescript
// Tests in app/assets/tests/components/
import Component from '../../components/MyComponent.vue' // Correct
```

### Issue: "No export defined on mock"

**Solution**: Ensure all used exports are defined in the mock:

```typescript
vi.mock('@userfrosting/sprinkle-core/stores', () => ({
  useTranslator: () => ({ translate: (key: string) => key }),
  useAlertsStore: () => ({ push: vi.fn() }),
  usePageMeta: () => ({ setTitle: vi.fn() }) // Add missing exports
}))
```

### Issue: Tests timeout

**Solution**: Ensure async operations complete with `flushPromises()` or increase timeout:

```typescript
import { flushPromises } from '@vue/test-utils'

it('loads data', async () => {
  const wrapper = mount(Component)
  await flushPromises() // Wait for promises
  // assertions...
}, { timeout: 10000 }) // Increase timeout if needed
```

## Continuous Integration

Tests are automatically run in CI workflows. Ensure all tests pass before merging:

```bash
npm test # Must exit with code 0
```

## Coverage Reports

Generate coverage reports to identify untested code:

```bash
npm run test:coverage
```

Coverage reports are generated in `./_meta/_coverage/` directory.

## Further Reading

- [Vitest Documentation](https://vitest.dev/)
- [Vue Test Utils Guide](https://test-utils.vuejs.org/guide/)
- [UserFrosting 6 Theme Pink Cupcake Tests](https://github.com/userfrosting/theme-pink-cupcake/tree/6.0/src/tests)
- [Testing Vue 3 Components](https://vuejs.org/guide/scaling-up/testing.html)

## Contributing Tests

When adding new components or features:

1. Create corresponding test files in `app/assets/tests/`
2. Follow existing test patterns and naming conventions
3. Aim for meaningful test coverage (critical paths and edge cases)
4. Run tests locally before committing: `npm test`
5. Ensure all tests pass in CI before merging

For questions or issues with tests, refer to the [UserFrosting forums](https://forums.userfrosting.com/) or open an issue on GitHub.
