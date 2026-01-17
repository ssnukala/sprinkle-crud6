#!/bin/bash

# UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
# @link      https://github.com/ssnukala/sprinkle-crud6
# @copyright Copyright (c) 2026 Srinivas Nukala
# @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)

# Quick verification script to identify source of redundant API calls
# Run this in your browser's DevTools Console when on a CRUD6 page

echo "==================================="
echo "CRUD6 API Call Source Verification"
echo "==================================="
echo ""
echo "Instructions:"
echo "1. Open your UserFrosting application in a browser"
echo "2. Open Browser DevTools (F12)"
echo "3. Go to the Network tab"
echo "4. Clear network log (trash icon)"
echo "5. Navigate to /crud6/groups"
echo "6. Filter by 'yaml' in the network tab filter box"
echo "7. Check the results below"
echo ""
echo "==================================="
echo ""
echo "Expected if CRUD6 is NOT causing YAML imports:"
echo "  - YAML files should show in Initiator column:"
echo "    useGroupApi.ts (from sprinkle-admin)"
echo "    useRoleApi.ts (from sprinkle-admin)"
echo "    useLoginApi.ts (from sprinkle-account)"
echo ""
echo "  - NOT from:"
echo "    useCRUD6Api.ts"
echo "    useCRUD6Schema.ts"
echo "    Any CRUD6 component"
echo ""
echo "==================================="
echo ""
echo "To check schema API calls:"
echo "1. Clear network log again"
echo "2. Navigate to /crud6/groups"
echo "3. Filter by '/schema' in network tab"
echo "4. Check how many calls are made"
echo ""
echo "Expected:"
echo "  - 1 call: GET /api/crud6/groups/schema?context=list"
echo ""
echo "If you see more than 1, that's the actual problem to fix!"
echo ""
echo "==================================="
echo ""
echo "Files to check in CRUD6 code:"
echo ""
grep -r "loadSchema" app/assets/views/*.vue app/assets/components/CRUD6/*.vue 2>/dev/null | grep -v "node_modules" | head -20
echo ""
echo "==================================="
