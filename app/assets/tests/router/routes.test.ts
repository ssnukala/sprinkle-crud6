import { describe, expect, test } from 'vitest'

import CRUD6Routes from '../../routes'

describe('routes.test.ts', () => {
    test('CRUD6Routes should contain the main crud6 route', () => {
        expect(CRUD6Routes.length).toBe(1) // main crud6 route only
        expect(CRUD6Routes[0].path).toBe('/crud6/:model')
        expect(CRUD6Routes[0].children.length).toBe(2) // list and detail routes
    })

    test('CRUD6Routes should have title and description in meta for breadcrumbs', () => {
        const mainRoute = CRUD6Routes[0]
        
        // Parent route should have title and description
        expect(mainRoute.meta).toHaveProperty('title')
        expect(mainRoute.meta).toHaveProperty('description')
        
        // List route should NOT have title and description (dynamically set by Vue component)
        const listRoute = mainRoute.children[0]
        expect(listRoute.meta).not.toHaveProperty('title')
        expect(listRoute.meta).not.toHaveProperty('description')
        
        // View route should have title and description
        const viewRoute = mainRoute.children[1]
        expect(viewRoute.meta).toHaveProperty('title')
        expect(viewRoute.meta).toHaveProperty('description')
    })
})
