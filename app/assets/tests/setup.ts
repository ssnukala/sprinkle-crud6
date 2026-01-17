/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * Test Setup File
 * 
 * Common configuration and mocks for all frontend tests
 */

import { vi } from 'vitest'
import { config } from '@vue/test-utils'

// Mock the translator store and other core stores
vi.mock('@userfrosting/sprinkle-core/stores', () => ({
  useTranslator: () => ({
    translate: (key: string) => key,
    translateWithParameters: (key: string, params: Record<string, any>) => key
  }),
  useAlertsStore: () => ({
    push: vi.fn(),
    clear: vi.fn()
  }),
  usePageMeta: () => ({
    setTitle: vi.fn(),
    setDescription: vi.fn()
  })
}))

// Set global config for test utils
config.global.stubs = {
  // Stub router-link and other common components if needed
  'router-link': {
    template: '<a><slot /></a>'
  }
}
