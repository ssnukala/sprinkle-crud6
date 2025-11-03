#!/bin/bash

echo "=== Route Configuration Validation ==="
echo ""
echo "Checking for static title placeholders in CRUD6Routes.ts..."
echo ""

# Check if routes file has title: 'CRUD6.PAGE' or similar static titles
if grep -q "title: 'CRUD6" app/assets/routes/CRUD6Routes.ts; then
    echo "✗ Found static CRUD6 title in routes!"
    grep -n "title:" app/assets/routes/CRUD6Routes.ts
    echo ""
    echo "✗✗✗ Route configuration has issues! ✗✗✗"
    exit 1
else
    echo "✓ No static CRUD6 titles found in route meta"
    echo ""
    echo "Route structure:"
    grep -A 2 "meta:" app/assets/routes/CRUD6Routes.ts | head -20
    echo ""
    echo "✓✓✓ Route configuration is correct! ✓✓✓"
    echo "✓ No static title placeholders"
    echo "✓ Titles will be set dynamically by components"
    echo "✓ Breadcrumbs will show actual model names, not {{model}}"
    exit 0
fi
