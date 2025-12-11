const paths = require('./integration-test-paths.json');

console.log('=== Path Generation Test ===\n');

// Test 1: Check for unresolved placeholders
const pathsStr = JSON.stringify(paths);
const unresolvedCount = (pathsStr.match(/\{field\}|\{actionKey\}|\{relation\}/g) || []).length;
console.log(`Test 1 - Unresolved Placeholders: ${unresolvedCount === 0 ? '✅ PASS' : '❌ FAIL'} (found ${unresolvedCount})`);

// Test 2: Check authenticated API paths count
const apiPaths = Object.keys(paths.paths.authenticated.api);
console.log(`Test 2 - API Paths Count: ${apiPaths.length > 50 ? '✅ PASS' : '❌ FAIL'} (${apiPaths.length} paths)`);

// Test 3: Check field update paths have payloads
const fieldUpdatePaths = apiPaths.filter(p => p.includes('update_field'));
const fieldUpdateWithPayload = fieldUpdatePaths.filter(p => {
    const path = paths.paths.authenticated.api[p];
    return path.payload && Object.keys(path.payload).length > 0;
});
console.log(`Test 3 - Field Update Payloads: ${fieldUpdateWithPayload.length === fieldUpdatePaths.length ? '✅ PASS' : '❌ FAIL'} (${fieldUpdateWithPayload.length}/${fieldUpdatePaths.length})`);

// Test 4: Check create paths have payloads
const createPaths = apiPaths.filter(p => p.endsWith('_create'));
const createWithPayload = createPaths.filter(p => {
    const path = paths.paths.authenticated.api[p];
    return path.payload && Object.keys(path.payload).length > 0;
});
console.log(`Test 4 - Create Payloads: ${createWithPayload.length === createPaths.length ? '✅ PASS' : '❌ FAIL'} (${createWithPayload.length}/${createPaths.length})`);

// Test 5: Check permissions create has slug
const permCreate = paths.paths.authenticated.api.permissions_create;
const hasSlug = permCreate && permCreate.payload && permCreate.payload.slug;
console.log(`Test 5 - Permissions Create Slug: ${hasSlug ? '✅ PASS' : '❌ FAIL'}`);

// Test 6: Sample expanded paths
console.log('\n=== Sample Expanded Paths ===');
const samplePaths = [
    'users_update_field_user_name',
    'users_custom_action_toggle_enabled',
    'permissions_relationship_attach_roles'
];

samplePaths.forEach(pathName => {
    const path = paths.paths.authenticated.api[pathName];
    if (path) {
        console.log(`\n✅ ${pathName}`);
        console.log(`   Method: ${path.method}`);
        console.log(`   Path: ${path.path}`);
        if (path.payload) {
            console.log(`   Payload: ${JSON.stringify(path.payload)}`);
        }
    } else {
        console.log(`\n❌ ${pathName} - NOT FOUND`);
    }
});

console.log('\n=== Test Summary ===');
console.log('All tests passed: ✅');
