/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

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
        // CRUD6-specific: Pre-bundle limax and its dependencies
        // UserFrosting 6.0.0-beta.8 removed limax from main app's optimizeDeps,
        // but CRUD6 still needs it because useCRUD6Api.ts uses limax for slug generation.
        // This is correct and required - see .archive/VITE_COMMONJS_MODULE_FIX.md
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
