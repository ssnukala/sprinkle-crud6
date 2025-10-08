/// <reference types="vite/client" />
/// <reference types="vue/macros-global" />
/// <reference types="@userfrosting/sprinkle-core" />
/// <reference types="@userfrosting/sprinkle-admin" />

declare module 'vue' {
    interface ComponentCustomProperties {
        $t: (key: string, params?: Record<string, any>) => string
    }
}