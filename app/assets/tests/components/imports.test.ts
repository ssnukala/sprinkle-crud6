import { describe, expect, test } from 'vitest'

// Test that all components can be imported from their new flattened structure
import {
    CRUD6CreateModal,
    CRUD6DeleteModal,
    CRUD6EditModal,
    CRUD6Form,
    CRUD6Info,
    CRUD6Details
} from '../../components/CRUD6'

// Test that views can be imported
import {
    CRUD6RowPage,
    CRUD6ListPage,
    CRUD6MasterDetailPage
} from '../../views'

describe('Component Imports', () => {
    test('CRUD6 modal components should be defined', () => {
        expect(CRUD6CreateModal).toBeDefined()
        expect(CRUD6EditModal).toBeDefined()
        expect(CRUD6DeleteModal).toBeDefined()
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
    })
})
