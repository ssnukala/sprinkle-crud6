// Test validation logic with actual response data
const rawResponse = {"message":"Retrieved Users schema successfully","modelDisplayName":"Users","breadcrumb":{"modelTitle":"Users","singularTitle":"User"},"model":"users","title":"Users","singular_title":"User","primary_key":"id","title_field":"user_name","description":"A listing of the users for your site. Provides management tools including editing, activation and enabling/disabling accounts.","permissions":{"read":"uri_users","create":"create_user","update":"update_user_field","delete":"delete_user"},"actions":[{"key":"create_action","label":"Create ","icon":"plus","type":"form","style":"primary","permission":"create_user","modal_config":{"type":"form","title":"Create "}},{"key":"edit_action","label":"Edit ","icon":"pen-to-square","type":"form","style":"primary","permission":"update_user_field","modal_config":{"type":"form","title":"Edit "}},{"key":"delete_action","label":"Delete ","icon":"trash","type":"delete","style":"danger","permission":"delete_user","confirm":"CRUD6.DELETE_CONFIRM","modal_config":{"type":"confirm","buttons":"yes_no","warning":"WARNING_CANNOT_UNDONE"}},{"key":"toggle_enabled","label":"Toggle Enabled","icon":"toggle-on","type":"field_update","field":"flag_enabled","style":"warning","confirm":"CRUD6.USER.TOGGLE_ENABLED_CONFIRM","toggle":true,"permission":"update_user_field","modal_config":{"type":"confirm","buttons":"yes_no"}},{"key":"toggle_verified","label":"Toggle Verified","icon":"check-circle","type":"field_update","style":"primary","field":"flag_verified","confirm":"CRUD6.USER.TOGGLE_VERIFIED_CONFIRM","toggle":true,"permission":"update_user_field","modal_config":{"type":"confirm","buttons":"yes_no"}},{"key":"reset_password","label":"Send Password Reset","icon":"envelope","type":"api_call","method":"POST","style":"secondary","permission":"update_user_field","confirm":"CRUD6.USER.ADMIN.PASSWORD_RESET_CONFIRM","modal_config":{"type":"confirm"}},{"key":"password_action","label":"Change User's Password","icon":"key","type":"field_update","field":"password","style":"warning","permission":"update_user_field","confirm":"CRUD6.USER.ADMIN.PASSWORD_CHANGE_CONFIRM","modal_config":{"type":"input","fields":["password"]}},{"key":"disable_user","label":"Disable User","icon":"user-slash","type":"field_update","field":"flag_enabled","value":false,"style":"danger","permission":"update_user_field","confirm":"CRUD6.USER.DISABLE_CONFIRM","visible_when":{"flag_enabled":true},"modal_config":{"type":"confirm"}},{"key":"enable_user","label":"Enable User","icon":"user-check","type":"field_update","field":"flag_enabled","value":true,"style":"primary","permission":"update_user_field","confirm":"CRUD6.USER.ENABLE_CONFIRM","visible_when":{"flag_enabled":false},"modal_config":{"type":"confirm"}}],"contexts":{"list":{"fields":{"user_name":{"type":"string","label":"Username","sortable":true,"filterable":true},"first_name":{"type":"string","label":"First Name","sortable":true,"filterable":true},"last_name":{"type":"string","label":"Last Name","sortable":true,"filterable":true},"email":{"type":"email","label":"Email Address","sortable":true,"filterable":true},"flag_verified":{"type":"boolean","label":"Verified","sortable":true,"filterable":true},"flag_enabled":{"type":"boolean","label":"Enabled","sortable":true,"filterable":true}},"default_sort":{"user_name":"asc"},"actions":[{"key":"create_action","label":"Create ","icon":"plus","type":"form","style":"primary","permission":"create_user","modal_config":{"type":"form","title":"Create "}},{"key":"edit_action","label":"Edit ","icon":"pen-to-square","type":"form","style":"primary","permission":"update_user_field","modal_config":{"type":"form","title":"Edit "}},{"key":"delete_action","label":"Delete ","icon":"trash","type":"delete","style":"danger","permission":"delete_user","confirm":"CRUD6.DELETE_CONFIRM","modal_config":{"type":"confirm","buttons":"yes_no","warning":"WARNING_CANNOT_UNDONE"}},{"key":"toggle_enabled","label":"Toggle Enabled","icon":"toggle-on","type":"field_update","field":"flag_enabled","style":"warning","confirm":"CRUD6.USER.TOGGLE_ENABLED_CONFIRM","toggle":true,"permission":"update_user_field","modal_config":{"type":"confirm","buttons":"yes_no"}},{"key":"toggle_verified","label":"Toggle Verified","icon":"check-circle","type":"field_update","style":"primary","field":"flag_verified","confirm":"CRUD6.USER.TOGGLE_VERIFIED_CONFIRM","toggle":true,"permission":"update_user_field","modal_config":{"type":"confirm","buttons":"yes_no"}},{"key":"reset_password","label":"Send Password Reset","icon":"envelope","type":"api_call","method":"POST","style":"secondary","permission":"update_user_field","confirm":"CRUD6.USER.ADMIN.PASSWORD_RESET_CONFIRM","modal_config":{"type":"confirm"}},{"key":"password_action","label":"Change User's Password","icon":"key","type":"field_update","field":"password","style":"warning","permission":"update_user_field","confirm":"CRUD6.USER.ADMIN.PASSWORD_CHANGE_CONFIRM","modal_config":{"type":"input","fields":["password"]}},{"key":"disable_user","label":"Disable User","icon":"user-slash","type":"field_update","field":"flag_enabled","value":false,"style":"danger","permission":"update_user_field","confirm":"CRUD6.USER.DISABLE_CONFIRM","visible_when":{"flag_enabled":true},"modal_config":{"type":"confirm"}},{"key":"enable_user","label":"Enable User","icon":"user-check","type":"field_update","field":"flag_enabled","value":true,"style":"primary","permission":"update_user_field","confirm":"CRUD6.USER.ENABLE_CONFIRM","visible_when":{"flag_enabled":false},"modal_config":{"type":"confirm"}}]},"form":{"fields":{"user_name":{"type":"string","label":"Username","required":true,"editable":true,"validation":{"required":true,"unique":true,"length":{"min":1,"max":50},"username":true,"no_leading_whitespace":true,"no_trailing_whitespace":true},"show_in":["list","create","edit","detail"]},"first_name":{"type":"string","label":"First Name","required":true,"editable":true,"validation":{"required":true,"length":{"min":1,"max":20},"no_leading_whitespace":true,"no_trailing_whitespace":true},"show_in":["list","create","edit","detail"]},"last_name":{"type":"string","label":"Last Name","required":true,"editable":true,"validation":{"required":true,"length":{"min":1,"max":30},"no_leading_whitespace":true,"no_trailing_whitespace":true},"show_in":["list","create","edit","detail"]},"email":{"type":"email","label":"Email Address","required":true,"editable":true,"validation":{"required":true,"email":true,"unique":true,"length":{"max":254},"no_leading_whitespace":true,"no_trailing_whitespace":true},"show_in":["list","create","edit","detail"]},"locale":{"type":"string","label":"Locale","required":false,"editable":true,"validation":{"length":{"max":10}},"default":"en_US","show_in":["create","edit","detail"]},"group_id":{"type":"integer","label":"Group","required":false,"editable":true,"default":1,"show_in":["create","edit","detail"]},"flag_verified":{"type":"boolean","label":"Verified","required":false,"editable":true,"description":"Email verification status","default":true,"show_in":["list","create","edit","detail"]},"flag_enabled":{"type":"boolean","label":"Enabled","required":false,"editable":true,"description":"Account enabled status","default":true,"show_in":["list","create","edit","detail"]},"role_ids":{"type":"multiselect","label":"Roles","required":false,"editable":true,"description":"User roles (used for sync on update)","show_in":["create","edit"]},"password":{"type":"password","label":"Password","required":true,"editable":true,"validation":{"required":true,"length":{"min":8,"max":255},"match":true},"show_in":["create","edit"]}}}}};

console.log('=== TESTING VALIDATION LOGIC ===\n');

// Test 1: Check if contexts key exists
const hasContextsKey = 'contexts' in rawResponse;
console.log('1. Has "contexts" key:', hasContextsKey);

// Test 2: Check if contexts is truthy
const contextsIsTruthy = !!rawResponse.contexts;
console.log('2. Contexts is truthy:', contextsIsTruthy);

// Test 3: Check contexts type
const contextsType = typeof rawResponse.contexts;
console.log('3. Contexts type:', contextsType);

// Test 4: Check if contexts is object
const contextsIsObject = rawResponse.contexts && typeof rawResponse.contexts === 'object';
console.log('4. Contexts is object:', contextsIsObject);

// Test 5: Check if contexts is NOT array
const contextsIsArray = Array.isArray(rawResponse.contexts);
console.log('5. Contexts is array:', contextsIsArray);
console.log('   (should be false for validation to pass)');

// Test 6: Check contexts length
const contextsKeys = Object.keys(rawResponse.contexts);
const contextsLength = contextsKeys.length;
console.log('6. Contexts keys:', contextsKeys);
console.log('   Contexts length:', contextsLength);

// Test 7: All conditions together
const allConditionsMet = 
    hasContextsKey && 
    contextsIsTruthy && 
    contextsIsObject && 
    !contextsIsArray && 
    contextsLength > 0;

console.log('\n=== FINAL VALIDATION ===');
console.log('All conditions met:', allConditionsMet);

// Test 8: Check if contexts have fields
console.log('\n=== CONTEXT STRUCTURE ===');
for (const [ctxName, ctxData] of Object.entries(rawResponse.contexts)) {
    console.log(`Context "${ctxName}":`, {
        hasFields: 'fields' in ctxData,
        fieldCount: ctxData.fields ? Object.keys(ctxData.fields).length : 0,
        fieldKeys: ctxData.fields ? Object.keys(ctxData.fields) : []
    });
}

// Test 9: Check alternative structures
console.log('\n=== ALTERNATIVE STRUCTURE CHECKS ===');
console.log('Has "schema" key at root:', 'schema' in rawResponse);
console.log('Has "fields" key at root:', 'fields' in rawResponse);

console.log('\n=== CONCLUSION ===');
if (allConditionsMet) {
    console.log('✅ Multi-context validation SHOULD PASS');
    console.log('The response has valid multi-context structure');
} else {
    console.log('❌ Multi-context validation WOULD FAIL');
    console.log('Checking why...');
    if (!hasContextsKey) console.log('  - Missing "contexts" key');
    if (!contextsIsTruthy) console.log('  - Contexts is falsy');
    if (contextsType !== 'object') console.log('  - Contexts is not an object');
    if (contextsIsArray) console.log('  - Contexts is an array (should be object)');
    if (contextsLength === 0) console.log('  - Contexts object is empty');
}
