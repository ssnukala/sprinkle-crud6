/// <reference types="vite/client" />
/// <reference types="vue/macros-global" />
/// <reference types="@userfrosting/sprinkle-core" />
/// <reference types="@userfrosting/sprinkle-admin" />

// Global type augmentation for Vue components
declare global {
    interface Window {
        [key: string]: any
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $t: (key: string, params?: Record<string, any>) => string
    }
}

export {}
