/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

import type { App } from 'vue'
import CRUD6Sprinkle from './plugins/crud6'

/**
 * CRUD6 Sprinkle initialization recipe.
 * 
 * This is the main entry point for plugin installation ONLY.
 * Use this only for: app.use(CRUD6)
 * 
 * For imports, use the specific subpath exports to avoid eager loading:
 * - Composables: import { useCRUD6Api } from '@ssnukala/sprinkle-crud6/composables'
 * - Components: import { CRUD6Form } from '@ssnukala/sprinkle-crud6/components'
 * - Interfaces: import type { CRUD6Interface } from '@ssnukala/sprinkle-crud6/interfaces'
 * - Stores: import { useCRUD6SchemaStore } from '@ssnukala/sprinkle-crud6/stores'
 * - Routes: import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'
 * - Views: Lazy-loaded via router
 * 
 * Components and views are NOT registered globally to prevent eager module loading
 * and unnecessary YAML imports from other sprinkles.
 */
export default {
    install: (app: App) => {
        app.use(CRUD6Sprinkle)
    }
}

// Re-exports removed to prevent eager loading.
// Use subpath imports instead (e.g., '@ssnukala/sprinkle-crud6/composables')
