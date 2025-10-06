import type { App } from 'vue'
import CRUD6Sprinkle from './plugins/crud6'

/**
 * CRUD6 Sprinkle initialization recipe.
 * 
 * This recipe is responsible for loading the CRUD6 sprinkle plugin
 * which registers all Vue components and views globally.
 */
export default {
    install: (app: App) => {
        app.use(CRUD6Sprinkle)
    }
}

// Export components
export * from './components'

// Export composables  
export * from './composables'

// Export interfaces
export * from './interfaces'

// Export views
export * from './views'

// Export routes
export * from './routes'

// Export plugins
export * from './plugins'
