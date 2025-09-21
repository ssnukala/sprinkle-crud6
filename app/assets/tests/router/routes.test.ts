import { describe, expect, test } from 'vitest'

import CRUD6Routes from '../../routes'

describe('routes.test.ts', () => {
    test('CRUD6Routes should contain all the individual routes', () => {
        expect(CRUD6Routes.length).toBe(8)
    })
})
