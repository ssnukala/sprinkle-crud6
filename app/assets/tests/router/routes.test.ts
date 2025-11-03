import { describe, expect, test } from 'vitest'

import CRUD6Routes from '../../routes'

describe('routes.test.ts', () => {
    test('CRUD6Routes should contain the main crud6 route', () => {
        expect(CRUD6Routes.length).toBe(1) // main crud6 route only
        expect(CRUD6Routes[0].path).toBe('/crud6/:model')
        expect(CRUD6Routes[0].children.length).toBe(2) // list and detail routes
    })

    test('CRUD6Routes meta properties for dynamic title handling', () => {
        const mainRoute = CRUD6Routes[0]
        
        // Parent route should NOT have title (dynamically set by child components based on schema)
        expect(mainRoute.meta).not.toHaveProperty('title')
        
        // List route should NOT have title (dynamically set by PageList.vue based on schema)
        const listRoute = mainRoute.children[0]
        expect(listRoute.meta).not.toHaveProperty('title')
        
        // View route should NOT have title (dynamically set by PageRow/PageMasterDetail based on schema and record)
        const viewRoute = mainRoute.children[1]
        expect(viewRoute.meta).not.toHaveProperty('title')
        expect(viewRoute.meta).toHaveProperty('description') // Description is still set statically
    })
})
