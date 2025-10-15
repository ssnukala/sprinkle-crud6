import { describe, expect, test } from 'vitest'

import CRUD6Routes from '../../routes'

describe('routes.test.ts', () => {
    test('CRUD6Routes should contain the redirect route and main crud6 route', () => {
        expect(CRUD6Routes.length).toBe(2) // redirect route + main crud6 route
        expect(CRUD6Routes[0].path).toBe('')
        expect(CRUD6Routes[1].path).toBe('/crud6/:model')
        expect(CRUD6Routes[1].children.length).toBe(2) // list and detail routes
    })

    test('CRUD6Routes should have correct meta for breadcrumbs', () => {
        const mainRoute = CRUD6Routes[1]
        
        // Parent route should NOT have title and description (dynamically set by child Vue components)
        expect(mainRoute.meta).not.toHaveProperty('title')
        expect(mainRoute.meta).not.toHaveProperty('description')
        
        // List route should NOT have title and description (dynamically set by Vue component)
        const listRoute = mainRoute.children[0]
        expect(listRoute.meta).not.toHaveProperty('title')
        expect(listRoute.meta).not.toHaveProperty('description')
        
        // View route should also NOT have title and description (dynamically set by Vue component)
        const viewRoute = mainRoute.children[1]
        expect(viewRoute.meta).not.toHaveProperty('title')
        expect(viewRoute.meta).not.toHaveProperty('description')
    })
})
