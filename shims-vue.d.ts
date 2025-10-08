// Type shims for peer dependencies
// These provide basic type stubs when peer dependencies are not installed locally

declare module 'vue' {
    export function ref<T>(value: T): any
    export function computed<T>(getter: () => T): any
    export function watch(...args: any[]): any
    export function onMounted(fn: () => void): void
    export function defineProps<T>(): T
    export function defineEmits<T = any>(emits?: string[]): T
    
    export interface ComponentCustomProperties {
        $t: (key: string, params?: Record<string, any>) => string
        $attrs: Record<string, any>
    }
}

declare module 'vue-router' {
    export function useRoute(): {
        params: Record<string, any>
        query: Record<string, any>
        path: string
        name?: string | null
    }
    export function useRouter(): {
        push: (location: any) => void
        replace: (location: any) => void
        [key: string]: any
    }
}

declare module 'uikit' {
    const UIkit: {
        modal: (selector: string) => {
            show: () => void
            hide: () => void
        }
        [key: string]: any
    }
    export default UIkit
}

declare module '@userfrosting/sprinkle-core/interfaces' {
    export enum Severity {
        Success = 'success',
        Info = 'info',
        Warning = 'warning',
        Danger = 'danger'
    }
}

declare module '@userfrosting/sprinkle-core/stores' {
    export function usePageMeta(): {
        title: any
        description: any
        [key: string]: any
    }
}

declare module '@ssnukala/sprinkle-crud6/composables' {
    export function useCRUD6Api(): any
    export function useCRUD6Schema(): any
    export function useCRUD6sApi(): any
}

declare module '@ssnukala/sprinkle-crud6/interfaces' {
    export interface CRUD6Interface {
        [key: string]: any
    }
    
    export interface CRUD6Response {
        [key: string]: any
    }
    
    export interface CRUD6CreateRequest {
        [key: string]: any
    }
    
    export interface CRUD6CreateResponse {
        [key: string]: any
    }
    
    export interface CRUD6EditRequest {
        [key: string]: any
    }
    
    export interface CRUD6EditResponse {
        [key: string]: any
    }
    
    export interface CRUD6DeleteResponse {
        [key: string]: any
    }
    
    export interface CRUD6SprunjerResponse {
        [key: string]: any
    }
}


