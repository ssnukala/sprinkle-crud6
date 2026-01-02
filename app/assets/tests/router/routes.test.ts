import { describe, expect, test } from 'vitest'

import { CRUD6RoutesImport } from '../../routes'

describe('routes.test.ts', () => {
    test('CRUD6Routes should contain the main crud6 route', () => {
        expect(CRUD6RoutesImport.length).toBe(1) // main crud6 route only
        expect(CRUD6RoutesImport[0].path).toBe('/crud6/:model')
        expect(CRUD6RoutesImport[0].children.length).toBe(2) // list and detail routes
    })

    test('CRUD6Routes should have description in meta', () => {
        const mainRoute = CRUD6RoutesImport[0]
        
        // Parent route should have description (not title, which is set dynamically)
        expect(mainRoute.meta).toHaveProperty('description')
        expect(mainRoute.meta.description).toBe('CRUD6.PAGE_DESCRIPTION')
        
        // List route (empty path) should NOT have meta (dynamically set by component)
        const listRoute = mainRoute.children[0]
        expect(listRoute.name).toBe('crud6.list')
        expect(listRoute.meta).toBeUndefined()
        
        // View route should have both title and description
        const viewRoute = mainRoute.children[1]
        expect(viewRoute.name).toBe('crud6.view')
        expect(viewRoute.meta).toHaveProperty('title')
        expect(viewRoute.meta).toHaveProperty('description')
    })
})
