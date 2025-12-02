import { describe, expect, test } from 'vitest'

import CRUD6Routes from '../../routes'

describe('routes.test.ts', () => {
    test('CRUD6Routes should contain the main crud6 route', () => {
        expect(CRUD6Routes.length).toBe(1) // main crud6 route only
        expect(CRUD6Routes[0].path).toBe('/crud6/:model')
        expect(CRUD6Routes[0].children.length).toBe(2) // list and detail routes
    })

    test('CRUD6Routes should have description in meta but NOT title (titles set dynamically)', () => {
        const mainRoute = CRUD6Routes[0]
        
        // Parent route should have description but NOT title
        // Title is intentionally omitted to prevent {{model}} placeholder from
        // appearing literally in breadcrumbs. Vue components set title dynamically.
        expect(mainRoute.meta).not.toHaveProperty('title')
        expect(mainRoute.meta).toHaveProperty('description')
        
        // List route should NOT have title (dynamically set by PageList.vue from schema)
        const listRoute = mainRoute.children[0]
        expect(listRoute.meta).not.toHaveProperty('title')
        expect(listRoute.meta).not.toHaveProperty('description')
        
        // View route should have description but NOT title (dynamically set by PageRow.vue from schema)
        const viewRoute = mainRoute.children[1]
        expect(viewRoute.meta).not.toHaveProperty('title')
        expect(viewRoute.meta).toHaveProperty('description')
    })
})
