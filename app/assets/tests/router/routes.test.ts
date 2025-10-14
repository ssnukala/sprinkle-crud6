import { describe, expect, test } from 'vitest'

import CRUD6Routes from '../../routes'

describe('routes.test.ts', () => {
    test('CRUD6Routes should contain the redirect route and main crud6 route', () => {
        expect(CRUD6Routes.length).toBe(2) // redirect route + main crud6 route
        expect(CRUD6Routes[0].path).toBe('')
        expect(CRUD6Routes[1].path).toBe('/crud6/:model')
        expect(CRUD6Routes[1].children.length).toBe(2) // list and detail routes
    })

    test('CRUD6Routes should have title and description in meta for breadcrumbs', () => {
        const mainRoute = CRUD6Routes[1]
        
        // Parent route should have title and description
        expect(mainRoute.meta).toHaveProperty('title')
        expect(mainRoute.meta).toHaveProperty('description')
        
        // Child routes should have title and description
        const listRoute = mainRoute.children[0]
        expect(listRoute.meta).toHaveProperty('title')
        expect(listRoute.meta).toHaveProperty('description')
        
        const viewRoute = mainRoute.children[1]
        expect(viewRoute.meta).toHaveProperty('title')
        expect(viewRoute.meta).toHaveProperty('description')
    })
})
