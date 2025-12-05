import { describe, expect, test } from 'vitest'

// Test that all components can be imported from their new flattened structure
import {
    CRUD6UnifiedModal,
    CRUD6Form,
    CRUD6Info,
    CRUD6Details
} from '../../components/CRUD6'

// Test that views can be imported
import {
    CRUD6RowPage,
    CRUD6ListPage,
    CRUD6MasterDetailPage,
    CRUD6DynamicPage
} from '../../views'

describe('Component Imports', () => {
    test('CRUD6 UnifiedModal component should be defined', () => {
        expect(CRUD6UnifiedModal).toBeDefined()
    })

    test('CRUD6 form and info components should be defined', () => {
        expect(CRUD6Form).toBeDefined()
        expect(CRUD6Info).toBeDefined()
        expect(CRUD6Details).toBeDefined()
    })

    test('CRUD6 view pages should be defined', () => {
        expect(CRUD6RowPage).toBeDefined()
        expect(CRUD6ListPage).toBeDefined()
        expect(CRUD6MasterDetailPage).toBeDefined()
        expect(CRUD6DynamicPage).toBeDefined()
    })
})
