/// <reference types="vitest" />
import { configDefaults } from 'vitest/config'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import ViteYaml from '@modyfi/vite-plugin-yaml'

// https://vitejs.dev/config/
// https://stackoverflow.com/a/74397545/445757
export default defineConfig({
    plugins: [vue(), ViteYaml()],
    optimizeDeps: {
        // Pre-bundle limax and its dependencies for optimal performance
        // This improves Vite cold-start time and ensures consistent behavior
        // Note: These are external dependencies specific to CRUD6, not UF monorepo packages
        // lodash.deburr is explicitly included to resolve Vite CommonJS module loading error
        // See CHANGELOG.md v0.6.1.8 for details
        include: ['limax', 'lodash.deburr']
    },
    test: {
        coverage: {
            reportsDirectory: './_meta/_coverage',
            include: ['app/assets/**/*.*'],
            exclude: ['app/assets/tests/**/*.*']
        },
        setupFiles: ['app/assets/tests/setup.ts'],
        environment: 'happy-dom',
        exclude: [
            ...configDefaults.exclude,
            './vendor/**/*.*',
        ],
    }
})
