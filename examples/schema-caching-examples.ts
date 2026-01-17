/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * Example demonstrating the schema caching functionality
 * This shows how the useCRUD6Schema composable prevents duplicate API calls
 */

import { useCRUD6Schema } from '@ssnukala/sprinkle-crud6/composables'

// Example 1: Basic caching
async function example1_BasicCaching() {
    console.log('\n=== Example 1: Basic Schema Caching ===\n')
    
    const { schema, loadSchema, currentModel } = useCRUD6Schema()
    
    console.log('1. Loading schema for "users" model...')
    await loadSchema('users')
    console.log('   ✓ Schema loaded (API call made)')
    console.log('   Current model:', currentModel.value)
    console.log('   Schema title:', schema.value?.title)
    
    console.log('\n2. Loading schema for "users" model again...')
    await loadSchema('users')
    console.log('   ✓ Schema returned from cache (NO API call)')
    console.log('   Current model:', currentModel.value)
    
    console.log('\n3. Loading schema for "products" model...')
    await loadSchema('products')
    console.log('   ✓ Schema loaded (API call made for new model)')
    console.log('   Current model:', currentModel.value)
    console.log('   Schema title:', schema.value?.title)
    
    console.log('\n4. Going back to "users" model...')
    await loadSchema('users')
    console.log('   ✓ Schema loaded (API call made - previous cache cleared)')
    console.log('   Current model:', currentModel.value)
}

// Example 2: Direct schema setting
async function example2_DirectSetting() {
    console.log('\n=== Example 2: Direct Schema Setting ===\n')
    
    const { schema, setSchema, loadSchema } = useCRUD6Schema()
    
    // Simulate receiving schema from parent component
    const parentSchema = {
        model: 'users',
        title: 'User Management',
        table: 'users',
        primary_key: 'id',
        fields: {
            id: { type: 'integer', label: 'ID' },
            name: { type: 'string', label: 'Name' }
        }
    }
    
    console.log('1. Setting schema directly (no API call)...')
    setSchema(parentSchema, 'users')
    console.log('   ✓ Schema set')
    console.log('   Schema title:', schema.value?.title)
    
    console.log('\n2. Calling loadSchema for same model...')
    await loadSchema('users')
    console.log('   ✓ Using cached schema (NO API call)')
}

// Example 3: Force reload
async function example3_ForceReload() {
    console.log('\n=== Example 3: Force Reload ===\n')
    
    const { schema, loadSchema } = useCRUD6Schema()
    
    console.log('1. Loading schema for "users" model...')
    await loadSchema('users')
    console.log('   ✓ Schema loaded (API call made)')
    
    console.log('\n2. Force reloading schema...')
    await loadSchema('users', true)
    console.log('   ✓ Schema reloaded (API call made even though cached)')
}

// Example 4: Component pattern (PageRow.vue simulation)
async function example4_ComponentPattern() {
    console.log('\n=== Example 4: Component Pattern (PageRow.vue) ===\n')
    
    const model = { value: 'users' }
    const { schema, loadSchema } = useCRUD6Schema()
    
    // Simulating onMounted
    console.log('onMounted hook:')
    console.log('  Loading schema for model:', model.value)
    await loadSchema(model.value)
    console.log('  ✓ Schema loaded (API call made)')
    
    // Simulating watcher firing immediately
    console.log('\nWatcher fires (same model):')
    console.log('  Loading schema for model:', model.value)
    await loadSchema(model.value)
    console.log('  ✓ Using cached schema (NO API call)')
    
    // Simulating navigation to different record but same model
    console.log('\nNavigation to different record (same model):')
    console.log('  Loading schema for model:', model.value)
    await loadSchema(model.value)
    console.log('  ✓ Using cached schema (NO API call)')
    
    console.log('\n📊 API Calls Made: 1')
    console.log('📊 Cache Hits: 2')
    console.log('📊 Performance Improvement: 67% fewer API calls!')
}

// Example 5: Parent-Child pattern
async function example5_ParentChildPattern() {
    console.log('\n=== Example 5: Parent-Child Schema Sharing ===\n')
    
    // Parent component
    console.log('Parent Component (PageList.vue):')
    const parent = useCRUD6Schema()
    await parent.loadSchema('users')
    console.log('  ✓ Parent loaded schema (API call made)')
    
    // Child component receives schema via props
    console.log('\nChild Component (PageRow.vue):')
    const child = useCRUD6Schema()
    
    console.log('  Receiving schema from parent via props...')
    if (parent.schema.value) {
        child.setSchema(parent.schema.value, 'users')
        console.log('  ✓ Child using parent\'s schema (NO API call)')
    }
    
    console.log('\n📊 Total API Calls: 1 (shared between parent and child)')
}

// Run all examples
export async function runExamples() {
    console.log('╔════════════════════════════════════════════════════╗')
    console.log('║  useCRUD6Schema Caching Examples                   ║')
    console.log('╚════════════════════════════════════════════════════╝')
    
    try {
        await example1_BasicCaching()
        await example2_DirectSetting()
        await example3_ForceReload()
        await example4_ComponentPattern()
        await example5_ParentChildPattern()
        
        console.log('\n✅ All examples completed successfully!')
        console.log('\nKey Takeaways:')
        console.log('• Schema caching prevents duplicate API calls automatically')
        console.log('• Use setSchema() to share schemas between components')
        console.log('• Force reload with loadSchema(model, true) when needed')
        console.log('• Backward compatible - no code changes required')
        
    } catch (error) {
        console.error('\n❌ Error running examples:', error)
    }
}

// Uncomment to run examples:
// runExamples()

export {
    example1_BasicCaching,
    example2_DirectSetting,
    example3_ForceReload,
    example4_ComponentPattern,
    example5_ParentChildPattern
}
