import type { App } from 'vue'
import {
    CRUD6Detail,
    CRUD6List,
} from '../views'
import {
    CRUD6CreateModal,
    CRUD6DeleteModal,
    CRUD6EditModal,
    CRUD6Form,
    CRUD6Info,
    CRUD6Users
} from '../components/CRUD6'

/**
 * Register CRUD6 components & views globally
 * See : https://vuejs.org/guide/components/registration
 */
export default {
    install: (app: App) => {
        // Register views from '../views'
        app.component('UFCRUD6Detail', CRUD6Detail)
            .component('UFCRUD6List', CRUD6List)
            // Register components from '../components/CRUD6'
            .component('UFCRUD6CreateModal', CRUD6CreateModal)
            .component('UFCRUD6DeleteModal', CRUD6DeleteModal)
            .component('UFCRUD6EditModal', CRUD6EditModal)
            .component('UFCRUD6Form', CRUD6Form)
            .component('UFCRUD6Info', CRUD6Info)
            .component('UFCRUD6Users', CRUD6Users)
    }
}

declare module 'vue' {
    export interface GlobalComponents {
        // Views from '../views'
        UFCRUD6Detail: typeof CRUD6Detail
        UFCRUD6List: typeof CRUD6List

        // Components from '../components/CRUD6'
        UFCRUD6CreateModal: typeof CRUD6CreateModal
        UFCRUD6DeleteModal: typeof CRUD6DeleteModal
        UFCRUD6EditModal: typeof CRUD6EditModal
        UFCRUD6Form: typeof CRUD6Form
        UFCRUD6Info: typeof CRUD6Info
        UFCRUD6Users: typeof CRUD6Users
    }
}
