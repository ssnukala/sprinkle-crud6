// Test the field merging fix with actual response data
const rawResponse = {"message":"Retrieved Users schema successfully","modelDisplayName":"Users","breadcrumb":{"modelTitle":"Users","singularTitle":"User"},"model":"users","title":"Users","singular_title":"User","primary_key":"id","title_field":"user_name","description":"A listing of the users for your site. Provides management tools including editing, activation and enabling/disabling accounts.","permissions":{"read":"uri_users","create":"create_user","update":"update_user_field","delete":"delete_user"},"actions":[{"key":"create_action","label":"Create ","icon":"plus","type":"form","style":"primary","permission":"create_user","modal_config":{"type":"form","title":"Create "}},{"key":"edit_action","label":"Edit ","icon":"pen-to-square","type":"form","style":"primary","permission":"update_user_field","modal_config":{"type":"form","title":"Edit "}},{"key":"delete_action","label":"Delete ","icon":"trash","type":"delete","style":"danger","permission":"delete_user","confirm":"CRUD6.DELETE_CONFIRM","modal_config":{"type":"confirm","buttons":"yes_no","warning":"WARNING_CANNOT_UNDONE"}},{"key":"toggle_enabled","label":"Toggle Enabled","icon":"toggle-on","type":"field_update","field":"flag_enabled","style":"warning","confirm":"CRUD6.USER.TOGGLE_ENABLED_CONFIRM","toggle":true,"permission":"update_user_field","modal_config":{"type":"confirm","buttons":"yes_no"}},{"key":"toggle_verified","label":"Toggle Verified","icon":"check-circle","type":"field_update","style":"primary","field":"flag_verified","confirm":"CRUD6.USER.TOGGLE_VERIFIED_CONFIRM","toggle":true,"permission":"update_user_field","modal_config":{"type":"confirm","buttons":"yes_no"}},{"key":"reset_password","label":"Send Password Reset","icon":"envelope","type":"api_call","method":"POST","style":"secondary","permission":"update_user_field","confirm":"CRUD6.USER.ADMIN.PASSWORD_RESET_CONFIRM","modal_config":{"type":"confirm"}},{"key":"password_action","label":"Change User's Password","icon":"key","type":"field_update","field":"password","style":"warning","permission":"update_user_field","confirm":"CRUD6.USER.ADMIN.PASSWORD_CHANGE_CONFIRM","modal_config":{"type":"input","fields":["password"]}},{"key":"disable_user","label":"Disable User","icon":"user-slash","type":"field_update","field":"flag_enabled","value":false,"style":"danger","permission":"update_user_field","confirm":"CRUD6.USER.DISABLE_CONFIRM","visible_when":{"flag_enabled":true},"modal_config":{"type":"confirm"}},{"key":"enable_user","label":"Enable User","icon":"user-check","type":"field_update","field":"flag_enabled","value":true,"style":"primary","permission":"update_user_field","confirm":"CRUD6.USER.ENABLE_CONFIRM","visible_when":{"flag_enabled":false},"modal_config":{"type":"confirm"}}],"contexts":{"list":{"fields":{"user_name":{"type":"string","label":"Username","sortable":true,"filterable":true},"first_name":{"type":"string","label":"First Name","sortable":true,"filterable":true},"last_name":{"type":"string","label":"Last Name","sortable":true,"filterable":true},"email":{"type":"email","label":"Email Address","sortable":true,"filterable":true},"flag_verified":{"type":"boolean","label":"Verified","sortable":true,"filterable":true},"flag_enabled":{"type":"boolean","label":"Enabled","sortable":true,"filterable":true}},"default_sort":{"user_name":"asc"},"actions":[{"key":"create_action","label":"Create ","icon":"plus","type":"form","style":"primary","permission":"create_user","modal_config":{"type":"form","title":"Create "}},{"key":"edit_action","label":"Edit ","icon":"pen-to-square","type":"form","style":"primary","permission":"update_user_field","modal_config":{"type":"form","title":"Edit "}},{"key":"delete_action","label":"Delete ","icon":"trash","type":"delete","style":"danger","permission":"delete_user","confirm":"CRUD6.DELETE_CONFIRM","modal_config":{"type":"confirm","buttons":"yes_no","warning":"WARNING_CANNOT_UNDONE"}},{"key":"toggle_enabled","label":"Toggle Enabled","icon":"toggle-on","type":"field_update","field":"flag_enabled","style":"warning","confirm":"CRUD6.USER.TOGGLE_ENABLED_CONFIRM","toggle":true,"permission":"update_user_field","modal_config":{"type":"confirm","buttons":"yes_no"}},{"key":"toggle_verified","label":"Toggle Verified","icon":"check-circle","type":"field_update","style":"primary","field":"flag_verified","confirm":"CRUD6.USER.TOGGLE_VERIFIED_CONFIRM","toggle":true,"permission":"update_user_field","modal_config":{"type":"confirm","buttons":"yes_no"}},{"key":"reset_password","label":"Send Password Reset","icon":"envelope","type":"api_call","method":"POST","style":"secondary","permission":"update_user_field","confirm":"CRUD6.USER.ADMIN.PASSWORD_RESET_CONFIRM","modal_config":{"type":"confirm"}},{"key":"password_action","label":"Change User's Password","icon":"key","type":"field_update","field":"password","style":"warning","permission":"update_user_field","confirm":"CRUD6.USER.ADMIN.PASSWORD_CHANGE_CONFIRM","modal_config":{"type":"input","fields":["password"]}},{"key":"disable_user","label":"Disable User","icon":"user-slash","type":"field_update","field":"flag_enabled","value":false,"style":"danger","permission":"update_user_field","confirm":"CRUD6.USER.DISABLE_CONFIRM","visible_when":{"flag_enabled":true},"modal_config":{"type":"confirm"}},{"key":"enable_user","label":"Enable User","icon":"user-check","type":"field_update","field":"flag_enabled","value":true,"style":"primary","permission":"update_user_field","confirm":"CRUD6.USER.ENABLE_CONFIRM","visible_when":{"flag_enabled":false},"modal_config":{"type":"confirm"}}]},"form":{"fields":{"user_name":{"type":"string","label":"Username","required":true,"editable":true,"validation":{"required":true,"unique":true,"length":{"min":1,"max":50},"username":true,"no_leading_whitespace":true,"no_trailing_whitespace":true},"show_in":["list","create","edit","detail"]},"first_name":{"type":"string","label":"First Name","required":true,"editable":true,"validation":{"required":true,"length":{"min":1,"max":20},"no_leading_whitespace":true,"no_trailing_whitespace":true},"show_in":["list","create","edit","detail"]},"last_name":{"type":"string","label":"Last Name","required":true,"editable":true,"validation":{"required":true,"length":{"min":1,"max":30},"no_leading_whitespace":true,"no_trailing_whitespace":true},"show_in":["list","create","edit","detail"]},"email":{"type":"email","label":"Email Address","required":true,"editable":true,"validation":{"required":true,"email":true,"unique":true,"length":{"max":254},"no_leading_whitespace":true,"no_trailing_whitespace":true},"show_in":["list","create","edit","detail"]},"locale":{"type":"string","label":"Locale","required":false,"editable":true,"validation":{"length":{"max":10}},"default":"en_US","show_in":["create","edit","detail"]},"group_id":{"type":"integer","label":"Group","required":false,"editable":true,"default":1,"show_in":["create","edit","detail"]},"flag_verified":{"type":"boolean","label":"Verified","required":false,"editable":true,"description":"Email verification status","default":true,"show_in":["list","create","edit","detail"]},"flag_enabled":{"type":"boolean","label":"Enabled","required":false,"editable":true,"description":"Account enabled status","default":true,"show_in":["list","create","edit","detail"]},"role_ids":{"type":"multiselect","label":"Roles","required":false,"editable":true,"description":"User roles (used for sync on update)","show_in":["create","edit"]},"password":{"type":"password","label":"Password","required":true,"editable":true,"validation":{"required":true,"length":{"min":8,"max":255},"match":true},"show_in":["create","edit"]}}}}};

console.log('=== TESTING FIELD MERGING FIX ===\n');

// Simulate the requested context: "list,form"
const context = "list,form";
const requestedContexts = context.split(',').map(c => c.trim());

console.log('Requested contexts:', requestedContexts);
console.log('Available contexts:', Object.keys(rawResponse.contexts));
console.log();

// Extract base schema (without contexts)
const baseSchema = { ...rawResponse };
delete baseSchema.contexts;

console.log('Base schema keys:', Object.keys(baseSchema));
console.log();

// Test OLD approach (buggy)
console.log('=== OLD APPROACH (BUGGY) ===');
let mergedFieldsOld = {};
let mergedContextDataOld = {};

for (const ctxName of requestedContexts) {
    if (rawResponse.contexts[ctxName]) {
        const ctxData = rawResponse.contexts[ctxName];
        if (ctxData.fields) {
            mergedFieldsOld = { ...mergedFieldsOld, ...ctxData.fields };
        }
        // BUG: This includes ctxData.fields which will overwrite our merged fields
        mergedContextDataOld = { ...mergedContextDataOld, ...ctxData };
    }
}

const schemaDataOld = {
    ...baseSchema,
    ...mergedContextDataOld,
    fields: mergedFieldsOld
};

console.log('Merged fields count (old):', Object.keys(mergedFieldsOld).length);
console.log('Merged fields keys (old):', Object.keys(mergedFieldsOld));
console.log('mergedContextDataOld has fields?', 'fields' in mergedContextDataOld);
console.log('mergedContextDataOld.fields count:', mergedContextDataOld.fields ? Object.keys(mergedContextDataOld.fields).length : 0);
console.log('Final schema has fields at root?', 'fields' in schemaDataOld);
console.log('Final schema.fields count:', schemaDataOld.fields ? Object.keys(schemaDataOld.fields).length : 0);
console.log('Final schema.fields keys:', schemaDataOld.fields ? Object.keys(schemaDataOld.fields) : []);
console.log();

// Test NEW approach (fixed)
console.log('=== NEW APPROACH (FIXED) ===');
let mergedFieldsNew = {};
let mergedContextDataNew = {};

for (const ctxName of requestedContexts) {
    if (rawResponse.contexts[ctxName]) {
        const ctxData = rawResponse.contexts[ctxName];
        if (ctxData.fields) {
            mergedFieldsNew = { ...mergedFieldsNew, ...ctxData.fields };
        }
        // FIX: Exclude fields from ctxData before spreading
        const { fields: _, ...ctxDataWithoutFields } = ctxData;
        mergedContextDataNew = { ...mergedContextDataNew, ...ctxDataWithoutFields };
    }
}

const schemaDataNew = {
    ...baseSchema,
    ...mergedContextDataNew,
    fields: mergedFieldsNew
};

console.log('Merged fields count (new):', Object.keys(mergedFieldsNew).length);
console.log('Merged fields keys (new):', Object.keys(mergedFieldsNew));
console.log('mergedContextDataNew has fields?', 'fields' in mergedContextDataNew);
console.log('Final schema has fields at root?', 'fields' in schemaDataNew);
console.log('Final schema.fields count:', schemaDataNew.fields ? Object.keys(schemaDataNew.fields).length : 0);
console.log('Final schema.fields keys:', schemaDataNew.fields ? Object.keys(schemaDataNew.fields) : []);
console.log();

// Expected result
console.log('=== EXPECTED RESULT ===');
console.log('Should merge fields from both contexts:');
console.log('- list context has 6 fields:', Object.keys(rawResponse.contexts.list.fields));
console.log('- form context has 10 fields:', Object.keys(rawResponse.contexts.form.fields));
console.log('- Merged should have 10 unique fields (form is superset of list)');
console.log();

// Comparison
console.log('=== COMPARISON ===');
console.log('Old approach fields count:', schemaDataOld.fields ? Object.keys(schemaDataOld.fields).length : 0);
console.log('New approach fields count:', schemaDataNew.fields ? Object.keys(schemaDataNew.fields).length : 0);
console.log();

if (schemaDataOld.fields && schemaDataNew.fields) {
    const oldCount = Object.keys(schemaDataOld.fields).length;
    const newCount = Object.keys(schemaDataNew.fields).length;
    
    if (oldCount === newCount && newCount === 10) {
        console.log('✅ BOTH approaches work correctly in this case!');
        console.log('   (The bug would manifest if contexts had conflicting top-level properties)');
    } else if (newCount === 10 && oldCount !== 10) {
        console.log('✅ NEW approach is correct! (10 fields)');
        console.log('❌ OLD approach is wrong! (' + oldCount + ' fields)');
    } else {
        console.log('⚠️ Unexpected result - need more investigation');
    }
} else {
    console.log('❌ One or both approaches failed to create fields at root!');
}
