import { describe, expect, test } from 'vitest'

import CRUD6Routes from '../../routes'

describe('routes.test.ts', () => {
    test('CRUD6Routes should contain the main crud6 route', () => {
        expect(CRUD6Routes.length).toBe(1) // main crud6 route only
        expect(CRUD6Routes[0].path).toBe('/crud6/:model')
        expect(CRUD6Routes[0].children.length).toBe(2) // list and detail routes
    })

    test('CRUD6Routes should have empty title strings for breadcrumb initialization', () => {
        const mainRoute = CRUD6Routes[0]
        
        // Parent route should have empty title string (allows breadcrumb initialization)
        expect(mainRoute.meta).toHaveProperty('title')
        expect(mainRoute.meta.title).toBe('')
        expect(mainRoute.meta).toHaveProperty('description')
        expect(mainRoute.meta.description).toBe('')
        
        // List route should have empty title string (PageList.vue updates dynamically)
        const listRoute = mainRoute.children[0]
        expect(listRoute.meta).toHaveProperty('title')
        expect(listRoute.meta.title).toBe('')
        expect(listRoute.meta).toHaveProperty('description')
        expect(listRoute.meta.description).toBe('')
        
        // View route should have empty title string (PageRow/PageMasterDetail update dynamically)
        const viewRoute = mainRoute.children[1]
        expect(viewRoute.meta).toHaveProperty('title')
        expect(viewRoute.meta.title).toBe('')
        expect(viewRoute.meta).toHaveProperty('description')
        expect(viewRoute.meta.description).toBe('CRUD6.INFO_PAGE')
    })
})
