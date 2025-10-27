import type { App } from 'vue'
import axios from 'axios'
import {
    CRUD6RowPage,
    CRUD6ListPage,
} from '../views'
import {
    CRUD6CreateModal,
    CRUD6DeleteModal,
    CRUD6EditModal,
    CRUD6Form,
    CRUD6Info,
    CRUD6Details,
    CRUD6DetailGrid,
    CRUD6MasterDetailForm
} from '../components/CRUD6'

/**
 * Register CRUD6 components & views globally
 * See : https://vuejs.org/guide/components/registration
 */
export default {
    install: (app: App) => {
        // Add axios request interceptor for CRUD6 debugging
        axios.interceptors.request.use(
            (config) => {
                if (config.url?.includes('/api/crud6/')) {
                    console.log('[CRUD6 Axios] ===== REQUEST START =====', {
                        method: config.method?.toUpperCase(),
                        url: config.url,
                        baseURL: config.baseURL,
                        fullURL: (config.baseURL || '') + (config.url || ''),
                        params: config.params,
                        data: config.data,
                        headers: config.headers,
                        timestamp: new Date().toISOString()
                    })
                }
                return config
            },
            (error) => {
                console.error('[CRUD6 Axios] Request error', error)
                return Promise.reject(error)
            }
        )

        // Add axios response interceptor for CRUD6 debugging
        axios.interceptors.response.use(
            (response) => {
                if (response.config.url?.includes('/api/crud6/')) {
                    console.log('[CRUD6 Axios] ===== RESPONSE RECEIVED =====', {
                        method: response.config.method?.toUpperCase(),
                        url: response.config.url,
                        status: response.status,
                        statusText: response.statusText,
                        dataKeys: response.data ? Object.keys(response.data) : [],
                        dataPreview: typeof response.data === 'object' ? 
                            JSON.stringify(response.data).substring(0, 200) : 
                            String(response.data).substring(0, 200),
                        headers: response.headers,
                        timestamp: new Date().toISOString()
                    })
                }
                return response
            },
            (error) => {
                if (error.config?.url?.includes('/api/crud6/')) {
                    console.error('[CRUD6 Axios] ===== RESPONSE ERROR =====', {
                        method: error.config?.method?.toUpperCase(),
                        url: error.config?.url,
                        status: error.response?.status,
                        statusText: error.response?.statusText,
                        errorMessage: error.message,
                        responseData: error.response?.data,
                        timestamp: new Date().toISOString()
                    })
                }
                return Promise.reject(error)
            }
        )
        
        // Register views from '../views'
        app.component('UFCRUD6RowPage', CRUD6RowPage)
            .component('UFCRUD6ListPage', CRUD6ListPage)
            // Register components from '../components/CRUD6'
            .component('UFCRUD6CreateModal', CRUD6CreateModal)
            .component('UFCRUD6DeleteModal', CRUD6DeleteModal)
            .component('UFCRUD6EditModal', CRUD6EditModal)
            .component('UFCRUD6Form', CRUD6Form)
            .component('UFCRUD6Info', CRUD6Info)
            .component('UFCRUD6Details', CRUD6Details)
            .component('UFCRUD6DetailGrid', CRUD6DetailGrid)
            .component('UFCRUD6MasterDetailForm', CRUD6MasterDetailForm)
    }
}

declare module 'vue' {
    export interface GlobalComponents {
        // Views from '../views'
        UFCRUD6RowPage: typeof CRUD6RowPage
        UFCRUD6ListPage: typeof CRUD6ListPage

        // Components from '../components/CRUD6'
        UFCRUD6CreateModal: typeof CRUD6CreateModal
        UFCRUD6DeleteModal: typeof CRUD6DeleteModal
        UFCRUD6EditModal: typeof CRUD6EditModal
        UFCRUD6Form: typeof CRUD6Form
        UFCRUD6Info: typeof CRUD6Info
        UFCRUD6Details: typeof CRUD6Details
        UFCRUD6DetailGrid: typeof CRUD6DetailGrid
        UFCRUD6MasterDetailForm: typeof CRUD6MasterDetailForm
    }
}
