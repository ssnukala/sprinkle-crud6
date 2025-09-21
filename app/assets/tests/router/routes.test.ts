import { describe, expect, test } from 'vitest'

import CRUD6Routes from '../../routes'

describe('routes.test.ts', () => {
    test('CRUD6Routes should contain the redirect route and main crud6 route', () => {
        expect(CRUD6Routes.length).toBe(2) // redirect route + main crud6 route
        expect(CRUD6Routes[0].path).toBe('')
        expect(CRUD6Routes[1].path).toBe('crud6/:model')
        expect(CRUD6Routes[1].children.length).toBe(2) // list and detail routes
    })
})
