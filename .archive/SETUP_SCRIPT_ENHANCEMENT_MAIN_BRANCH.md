# Setup Script Enhancement - Clone from Main Branch

## Date
November 2025

## Overview

Enhanced the dev container setup script to clone both sprinkle-crud6 and sprinkle-c6admin from their respective main branches on GitHub, and automatically configure them in the UserFrosting application.

## Previous Behavior

The setup script previously:
- Copied sprinkle-crud6 from `/workspace` to `/ssnukala/sprinkle-crud6`
- Cloned sprinkle-c6admin (if available) but did not specify branch
- Only configured CRUD6 in MyApp.php, main.ts, and router/index.ts
- Did not handle C6Admin configuration automatically

## New Behavior

The setup script now:
1. **Clones sprinkle-crud6 from main branch** to `/ssnukala/sprinkle-crud6`
2. **Clones sprinkle-c6admin from main branch** to `/ssnukala/sprinkle-c6admin` (if available)
3. **Auto-updates existing clones**: If directories exist, pulls latest changes from main branch
4. **Configures both sprinkles** in composer.json and package.json as local path dependencies
5. **Auto-configures MyApp.php** to include both CRUD6::class and C6Admin::class
6. **Auto-configures router/index.ts** to include routes from both sprinkles
7. **Auto-configures main.ts** to include both sprinkles

## Implementation Details

### Step 1: Clone sprinkle-crud6 from Main Branch

```bash
# Clone sprinkle-crud6 from main branch
if [ ! -d "/ssnukala/sprinkle-crud6" ]; then
    git clone --branch main https://github.com/ssnukala/sprinkle-crud6.git /ssnukala/sprinkle-crud6
else
    # Update if already exists
    cd /ssnukala/sprinkle-crud6
    git fetch origin
    git checkout main
    git pull origin main
    cd /workspace
fi
```

### Step 2: Clone sprinkle-c6admin from Main Branch

```bash
# Clone sprinkle-c6admin from main branch if repository exists
if [ ! -d "/ssnukala/sprinkle-c6admin" ]; then
    if git ls-remote https://github.com/ssnukala/sprinkle-c6admin.git &>/dev/null; then
        git clone --branch main https://github.com/ssnukala/sprinkle-c6admin.git /ssnukala/sprinkle-c6admin
    fi
else
    # Update if already exists and repository is accessible
    if git ls-remote https://github.com/ssnukala/sprinkle-c6admin.git &>/dev/null; then
        cd /ssnukala/sprinkle-c6admin
        git fetch origin
        git checkout main
        git pull origin main
        cd /workspace
    fi
fi
```

### Step 3: Configure MyApp.php

```bash
# Add CRUD6 import and class
sed -i '/use UserFrosting\\Sprinkle\\Core\\Core;/a use UserFrosting\\Sprinkle\\CRUD6\\CRUD6;' app/src/MyApp.php
sed -i '/Admin::class,/a \            CRUD6::class,' app/src/MyApp.php

# Add C6Admin if it exists
if [ -d "/ssnukala/sprinkle-c6admin" ]; then
    sed -i '/use UserFrosting\\Sprinkle\\CRUD6\\CRUD6;/a use UserFrosting\\Sprinkle\\C6Admin\\C6Admin;' app/src/MyApp.php
    sed -i '/CRUD6::class,/a \            C6Admin::class,' app/src/MyApp.php
fi
```

### Step 4: Configure router/index.ts

```bash
# Add CRUD6Routes import and spread
sed -i "/import AdminRoutes from '@userfrosting\/sprinkle-admin\/routes'/a import CRUD6Routes from '@ssnukala\/sprinkle-crud6\/routes'" app/assets/router/index.ts
sed -i '/\.\.\.AccountRoutes,/a \            ...CRUD6Routes,' app/assets/router/index.ts

# Add C6AdminRoutes if sprinkle exists
if [ -d "/ssnukala/sprinkle-c6admin" ] && [ -f "/ssnukala/sprinkle-c6admin/package.json" ]; then
    sed -i "/import CRUD6Routes from '@ssnukala\/sprinkle-crud6\/routes'/a import C6AdminRoutes from '@ssnukala\/sprinkle-c6admin\/routes'" app/assets/router/index.ts
    sed -i '/\.\.\.CRUD6Routes,/a \            ...C6AdminRoutes,' app/assets/router/index.ts
fi
```

### Step 5: Configure main.ts

```bash
# Add CRUD6Sprinkle import and use
sed -i "/import AdminSprinkle from '@userfrosting\/sprinkle-admin'/a import CRUD6Sprinkle from '@ssnukala\/sprinkle-crud6'" app/assets/main.ts
sed -i "/app.use(AdminSprinkle)/a app.use(CRUD6Sprinkle)" app/assets/main.ts

# Add C6AdminSprinkle if sprinkle exists
if [ -d "/ssnukala/sprinkle-c6admin" ] && [ -f "/ssnukala/sprinkle-c6admin/package.json" ]; then
    sed -i "/import CRUD6Sprinkle from '@ssnukala\/sprinkle-crud6'/a import C6AdminSprinkle from '@ssnukala\/sprinkle-c6admin'" app/assets/main.ts
    sed -i "/app.use(CRUD6Sprinkle)/a app.use(C6AdminSprinkle)" app/assets/main.ts
fi
```

## Benefits

### 1. Always Fresh Code
- Both sprinkles are cloned from main branch, ensuring latest code
- Auto-update feature pulls latest changes if directories exist
- No need to manually sync code between /workspace and /ssnukala

### 2. Full C6Admin Integration
- C6Admin is automatically configured in all necessary files
- Routes, sprinkle registration, and frontend integration all handled
- Developers can work with both sprinkles seamlessly

### 3. Consistent Development Environment
- Setup matches integration test workflow
- Both sprinkles configured identically
- Local path dependencies for immediate change reflection

### 4. Simplified Workflow
- One-time setup configures everything
- No manual configuration needed
- Changes to sprinkles immediately available in UserFrosting app

## File Structure After Setup

```
/ssnukala/
├── sprinkle-crud6/              # Cloned from main branch
│   ├── app/
│   ├── composer.json
│   └── package.json
└── sprinkle-c6admin/            # Cloned from main branch (if available)
    ├── app/
    ├── composer.json
    └── package.json

/workspace/userfrosting/
├── app/
│   ├── src/MyApp.php           # Configured with CRUD6::class, C6Admin::class
│   └── assets/
│       ├── router/index.ts     # Configured with routes from both sprinkles
│       └── main.ts             # Configured with both sprinkles
├── vendor/ssnukala/
│   ├── sprinkle-crud6/         # Symlink to /ssnukala/sprinkle-crud6
│   └── sprinkle-c6admin/       # Symlink to /ssnukala/sprinkle-c6admin
└── node_modules/@ssnukala/
    ├── sprinkle-crud6/         # Installed from local path
    └── sprinkle-c6admin/       # Installed from local path
```

## Developer Workflow

1. **Initial Setup**: Dev container runs setup script automatically
2. **Edit Code**: Make changes in `/ssnukala/sprinkle-crud6/` or `/ssnukala/sprinkle-c6admin/`
3. **Test Changes**: Changes immediately available in UserFrosting application
4. **Commit & Push**: Commit changes from respective sprinkle directories
5. **Pull Updates**: Re-run setup script or manually pull in sprinkle directories

## Testing

### Bash Syntax Validation
```bash
bash -n .devcontainer/setup-project.sh
✓ Bash syntax is valid
```

### Expected MyApp.php Output
```php
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\CRUD6\CRUD6;
use UserFrosting\Sprinkle\C6Admin\C6Admin;

public function getSprinkles(): array
{
    return [
        Core::class,
        Account::class,
        Admin::class,
        CRUD6::class,
        C6Admin::class,
    ];
}
```

### Expected router/index.ts Output
```typescript
import AdminRoutes from '@userfrosting/sprinkle-admin/routes'
import CRUD6Routes from '@ssnukala/sprinkle-crud6/routes'
import C6AdminRoutes from '@ssnukala/sprinkle-c6admin/routes'

const routes: RouteRecordRaw[] = [
    ...AccountRoutes,
    ...CRUD6Routes,
    ...C6AdminRoutes,
    // ...
]
```

### Expected main.ts Output
```typescript
import AdminSprinkle from '@userfrosting/sprinkle-admin'
import CRUD6Sprinkle from '@ssnukala/sprinkle-crud6'
import C6AdminSprinkle from '@ssnukala/sprinkle-c6admin'

app.use(AdminSprinkle)
app.use(CRUD6Sprinkle)
app.use(C6AdminSprinkle)
```

## Documentation Updates

Updated `.devcontainer/README.md` to reflect:
- Architecture overview: cloning from main branches
- File structure after setup
- Working with sprinkle source code
- Development workflow
- Contributing guidelines
- Integration test mirroring table

## Related Files Modified

1. `.devcontainer/setup-project.sh` - Main setup script
2. `.devcontainer/README.md` - Documentation
3. `.archive/SETUP_SCRIPT_ENHANCEMENT_MAIN_BRANCH.md` - This file

## Commit Information

- Commit: f1fc344
- Date: November 2025
- PR: Fix dev container build failures from PECL and npm issues

## Future Considerations

- Consider adding branch selection option via environment variable
- Add validation to ensure cloned repositories are valid UserFrosting sprinkles
- Add error handling for git operations
- Consider adding option to skip C6Admin if not needed
