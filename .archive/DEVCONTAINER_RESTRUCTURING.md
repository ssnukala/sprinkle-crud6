# DevContainer Restructuring: Alignment with Integration Test Workflow

**Date:** 2025-01-14  
**Issue:** DevContainer structure did not match integration-test.yml workflow  
**Resolution:** Complete restructuring to mirror CI/CD environment

## Problem Statement

The original devcontainer configuration had the workspace folder set to the sprinkle repository itself (`/workspace` = sprinkle-crud6 repo), with UserFrosting created inside at `/workspace/userfrosting`. This did NOT match the integration-test.yml workflow, which:

1. Creates UserFrosting 6 project FIRST
2. Checks out sprinkle-crud6 to a separate directory
3. Configures sprinkle as a local path dependency
4. Works from the UserFrosting project as the main directory

## Root Cause

The devcontainer was designed as a "sprinkle development environment" where the sprinkle repo was the primary workspace. However, the integration test correctly treats UserFrosting as the primary workspace with sprinkles as dependencies.

## Solution Overview

### Before (Incorrect Structure)
```
/workspace/                          # Sprinkle repo (this repo)
├── app/                            # CRUD6 Sprinkle source
├── .devcontainer/
└── userfrosting/                   # UserFrosting created inside
    ├── app/
    ├── vendor/
    │   └── ssnukala/sprinkle-crud6/ → symlink to /ssnukala/sprinkle-crud6
    └── ...

/ssnukala/
├── sprinkle-crud6/                 # Clone from GitHub main
└── sprinkle-c6admin/               # Clone from GitHub main
```

**Problems:**
- Workspace was the sprinkle, not UserFrosting
- Complex directory structure with clones in `/ssnukala`
- Did not match integration test workflow
- Confusing for developers (where to edit files?)

### After (Correct Structure - Matches integration-test.yml)
```
/workspace/                          # UserFrosting 6 project (main working directory)
├── app/
│   ├── src/MyApp.php              # Configured with CRUD6
│   ├── assets/
│   └── schema/crud6/
├── vendor/
│   └── ssnukala/sprinkle-crud6/   → /repos/sprinkle-crud6 (local path dependency)
├── node_modules/
└── ...

/repos/sprinkle-crud6/              # This repository (mounted from host)
├── app/                            # CRUD6 Sprinkle source
├── .devcontainer/
└── ...
```

**Benefits:**
- ✅ Workspace IS UserFrosting (matches integration test)
- ✅ Sprinkle mounted at `/repos/sprinkle-crud6` (clean separation)
- ✅ Simple, clear structure
- ✅ Exact mirror of CI/CD environment
- ✅ Developers work in UserFrosting context

## Changes Made

### 1. compose.yml
**Before:**
```yaml
volumes:
  - ..:/workspace:cached  # Sprinkle repo at workspace
  - sprinkle-crud6-userfrosting-node_modules:/workspace/userfrosting/node_modules
  - sprinkle-crud6-userfrosting-vendor:/workspace/userfrosting/vendor
  - sprinkle-crud6-source-node_modules:/ssnukala/sprinkle-crud6/node_modules
  - sprinkle-crud6-source-vendor:/ssnukala/sprinkle-crud6/vendor
```

**After:**
```yaml
volumes:
  - ..:/repos/sprinkle-crud6:cached  # Sprinkle repo reference
  - userfrosting-workspace:/workspace:cached  # UserFrosting workspace
  - userfrosting-node_modules:/workspace/node_modules
  - userfrosting-vendor:/workspace/vendor
```

**Rationale:**
- Sprinkle repo is mounted for reference/editing at `/repos/sprinkle-crud6`
- Workspace volume holds the UserFrosting project
- Simplified volume structure (no `/ssnukala` needed)

### 2. devcontainer.json
**Before:**
```json
{
  "workspaceFolder": "/workspace",
  "postCreateCommand": "bash .devcontainer/setup-project.sh",
  "mounts": [
    "source=sprinkle-crud6-userfrosting-node_modules,target=/workspace/userfrosting/node_modules,type=volume",
    "source=sprinkle-crud6-userfrosting-vendor,target=/workspace/userfrosting/vendor,type=volume",
    "source=sprinkle-crud6-source-node_modules,target=/ssnukala/sprinkle-crud6/node_modules,type=volume",
    "source=sprinkle-crud6-source-vendor,target=/ssnukala/sprinkle-crud6/vendor,type=volume"
  ]
}
```

**After:**
```json
{
  "workspaceFolder": "/workspace",
  "postCreateCommand": "bash /repos/sprinkle-crud6/.devcontainer/setup-project.sh"
}
```

**Rationale:**
- Workspace folder remains `/workspace` but now it's UserFrosting
- Mounts removed (handled by compose.yml)
- Script path updated to reflect new mount location

### 3. setup-project.sh
**Major Changes:**

**REMOVED:**
- Cloning sprinkle-crud6 from GitHub to `/ssnukala/sprinkle-crud6`
- Cloning sprinkle-c6admin from GitHub to `/ssnukala/sprinkle-c6admin`
- C6Admin integration (not part of integration-test.yml)
- Creating UserFrosting at `/workspace/userfrosting`

**CHANGED:**
- Creates UserFrosting directly at `/workspace`
- Configures composer with local path to `/repos/sprinkle-crud6`
- Packages sprinkle from `/repos/sprinkle-crud6`
- Simplified step numbering (removed unnecessary steps)

**Step Mapping to integration-test.yml:**

| Integration Test Step | setup-project.sh Step | Implementation |
|----------------------|----------------------|----------------|
| Checkout sprinkle-crud6 | N/A | Already mounted at `/repos/sprinkle-crud6` |
| Setup PHP/Node | N/A | In Dockerfile |
| Create UserFrosting project | Step 1 | `composer create-project` at `/workspace` |
| Configure Composer | Step 2 | Local path to `/repos/sprinkle-crud6` |
| Install PHP dependencies | Step 3 | `composer install` |
| Package sprinkle-crud6 for NPM | Step 4 | `npm pack` from `/repos/sprinkle-crud6` |
| Install NPM dependencies | Step 5 | `npm install` with package |
| Configure MyApp.php | Step 6 | Add CRUD6::class |
| Configure router/index.ts | Step 7 | Add CRUD6Routes |
| Configure main.ts | Step 8 | Add CRUD6Sprinkle |
| Create groups schema | Step 9 | Example schema |
| Setup environment | Step 10 | .env file |
| Wait for MySQL | Step 11 | Retry loop |
| Run migrations | Step 12 | `php bakery migrate --force` |
| Seed database | Step 13 | Account + CRUD6 seeds |
| Create admin user | Step 14 | `php bakery create:admin-user` |
| Build assets | Step 15 | `php bakery bake` |

### 4. Documentation Updates

**README.md:**
- Complete rewrite to reflect new structure
- Removed all references to `/ssnukala` directory
- Removed C6Admin integration
- Updated file structure diagrams
- Updated troubleshooting for new paths

**GITHUB_CODESPACES_GUIDE.md:**
- Updated setup description
- Changed paths from `/workspace/userfrosting` to `/workspace`
- Updated script path references

## Workflow Comparison

### Integration Test Workflow (integration-test.yml)
```bash
# 1. Checkout sprinkle to subdirectory
- uses: actions/checkout@v4
  with:
    path: sprinkle-crud6

# 2. Create UserFrosting project
composer create-project userfrosting/userfrosting userfrosting

# 3. Configure as local repository
cd userfrosting
composer config repositories.local path ../sprinkle-crud6
composer require ssnukala/sprinkle-crud6:@dev

# 4. Package and install NPM
cd ../sprinkle-crud6
npm pack
mv *.tgz ../userfrosting/
cd ../userfrosting
npm install ./ssnukala-sprinkle-crud6-*.tgz

# 5. Configure UserFrosting files
sed -i '/use UserFrosting\\Sprinkle\\Core\\Core;/a use UserFrosting\\Sprinkle\\CRUD6\\CRUD6;' app/src/MyApp.php
```

### DevContainer Workflow (setup-project.sh)
```bash
# 1. Sprinkle already mounted at /repos/sprinkle-crud6

# 2. Create UserFrosting project at /workspace
composer create-project userfrosting/userfrosting userfrosting-temp
mv userfrosting-temp/* /workspace/

# 3. Configure as local repository
cd /workspace
composer config repositories.local-crud6 path /repos/sprinkle-crud6
composer require ssnukala/sprinkle-crud6:@dev

# 4. Package and install NPM
cd /repos/sprinkle-crud6
npm pack
mv *.tgz /workspace/
cd /workspace
npm install ./ssnukala-sprinkle-crud6-*.tgz

# 5. Configure UserFrosting files
sed -i '/use UserFrosting\\Sprinkle\\Core\\Core;/a use UserFrosting\\Sprinkle\\CRUD6\\CRUD6;' app/src/MyApp.php
```

**Result:** Nearly identical workflows!

## Developer Experience

### Before
```bash
# Developer opens codespace
# Workspace is at /workspace (sprinkle repo)
# Must cd to /workspace/userfrosting to use UserFrosting commands
# Edit sprinkle files at /workspace or /ssnukala/sprinkle-crud6 (confusing!)
# Complex mental model

cd /workspace/userfrosting  # Every time!
php bakery serve
```

### After
```bash
# Developer opens codespace
# Workspace is at /workspace (UserFrosting project)
# Can immediately use UserFrosting commands
# Edit sprinkle files at /repos/sprinkle-crud6 (clear location)
# Simple mental model: work in UserFrosting, reference sprinkle

php bakery serve  # No cd needed!
# Edit sprinkle: /repos/sprinkle-crud6/app/src/
```

## Benefits

1. **Exact CI/CD Match:** DevContainer now mirrors integration-test.yml exactly
2. **Clearer Structure:** Workspace IS UserFrosting, sprinkle is a mounted reference
3. **Simpler Setup:** No cloning from GitHub, no `/ssnukala` directory
4. **Better DX:** Developers work in UserFrosting context immediately
5. **Easier Debugging:** Same structure as CI means issues reproduce locally
6. **Less Confusion:** Single source of truth for sprinkle location

## Testing Checklist

- [ ] Container builds successfully
- [ ] setup-project.sh completes without errors
- [ ] UserFrosting created at `/workspace`
- [ ] Sprinkle accessible at `/repos/sprinkle-crud6`
- [ ] Composer local path dependency works
- [ ] NPM package installation works
- [ ] MyApp.php configured correctly
- [ ] router/index.ts configured correctly
- [ ] main.ts configured correctly
- [ ] Database migrations run
- [ ] Database seeding works
- [ ] Admin user created
- [ ] `php bakery serve` works
- [ ] `php bakery assets:vite` works
- [ ] Can edit sprinkle files at `/repos/sprinkle-crud6`
- [ ] Changes to sprinkle reflect in UserFrosting
- [ ] Application accessible at http://localhost:8080

## Migration Notes

### For Existing Codespaces

If you have an existing codespace with the old structure:

1. **Recommended:** Delete and recreate the codespace
2. **Alternative:** Manually restructure (not recommended)

### For Local DevContainer Users

1. Rebuild container: "Dev Containers: Rebuild Container"
2. Or remove volumes and rebuild:
   ```bash
   docker-compose down -v
   docker-compose up --build
   ```

## Files Changed

- `.devcontainer/compose.yml` - Volume structure
- `.devcontainer/devcontainer.json` - Mount configuration
- `.devcontainer/setup-project.sh` - Setup workflow
- `.devcontainer/README.md` - Complete rewrite
- `.devcontainer/GITHUB_CODESPACES_GUIDE.md` - Path updates

## References

- Integration test workflow: `.github/workflows/integration-test.yml`
- Original issue: DevContainer structure not aligned with integration test
- Docker Compose v2 spec: https://docs.docker.com/compose/compose-file/
- DevContainers spec: https://containers.dev/

## Conclusion

This restructuring ensures that the development environment exactly mirrors the CI/CD environment, making it easier for developers to:
- Understand the project structure
- Reproduce CI issues locally
- Work efficiently with UserFrosting and the sprinkle
- Contribute with confidence

The new structure follows the integration-test.yml workflow step-by-step, creating a seamless development experience that matches production deployment.
