# CI Integration Test Fix - PinkCupcake Theme Installation

## Issue

GitHub Actions integration test was failing with:
```
PHP Fatal error: Uncaught UserFrosting\Support\Exception\BadClassNameException: 
Sprinkle recipe class `UserFrosting\Theme\PinkCupcake\PinkCupcake` not found.
```

**Failed Run:** https://github.com/ssnukala/sprinkle-crud6/actions/runs/18296045691/job/52094538234

## Root Cause Analysis

### Initial Assessment (Incorrect)
Originally believed PinkCupcake was optional and could be removed from CI test.

### Correct Assessment
After user feedback and code review, confirmed that:

1. **Frontend Components Require Theme**
   - Components use: `UFModal`, `UFModalConfirmation`, `UFFormValidationError`
   - These are globally registered by `@userfrosting/theme-pink-cupcake` plugin
   - Documented in CHANGELOG.md (lines 11-15)

2. **Theme is Already a Dependency**
   - package.json lists `@userfrosting/theme-pink-cupcake` as a **peerDependency** (line 48)
   - This is correct - theme should be a peer dependency, not a direct dependency

3. **CI Workflow Missing Installation**
   - Original workflow referenced PinkCupcake in MyApp.php
   - But never installed the theme packages (neither composer nor npm)
   - This caused the class not found error

## Solution

### What Changed
Added explicit PinkCupcake theme installation to CI workflow:

1. **Composer Installation** (Step after sprinkle-crud6 config, before PHP dependencies):
   ```yaml
   - name: Install PinkCupcake theme
     run: |
       cd userfrosting
       # Install PinkCupcake theme (required for CRUD6 frontend components)
       composer require userfrosting/theme-pink-cupcake:^6.0.0-beta --no-update
   ```

2. **NPM Installation** (After sprinkle-crud6 npm install):
   ```yaml
   npm install @userfrosting/theme-pink-cupcake@^6.0.0-beta
   ```

3. **MyApp.php Configuration** (Already present, kept as-is):
   ```php
   use UserFrosting\Theme\PinkCupcake\PinkCupcake;
   
   public function getSprinkles(): array
   {
       return [
           Core::class,
           Account::class,
           Admin::class,
           CRUD6::class,
           PinkCupcake::class,  // Required for frontend components
       ];
   }
   ```

### Commits
- **6ea2d4b**: Add PinkCupcake theme installation to CI workflow (composer + npm)
- **d627bf8**: Add PinkCupcake to MyApp.php sprinkles configuration

## Why This Approach

### PinkCupcake as Peer Dependency
- ✅ **Correct**: Theme is listed as peerDependency in package.json
- ✅ **Correct**: sprinkle-crud6 doesn't directly depend on a specific theme
- ✅ **Flexible**: Users can choose different themes or custom themes

### CI Must Install Theme
- The CI test needs to validate full functionality including frontend
- Frontend components require theme components to be available
- Theme must be installed in the UserFrosting project context
- This mimics real-world usage where users install both packages

## Impact

### What Works Now
✅ CI workflow installs all required dependencies (PHP + frontend)  
✅ PinkCupcake theme is available for component rendering  
✅ Frontend components can access `UFModal`, `UFFormValidationError`, etc.  
✅ Tests can validate full stack functionality  

### No Breaking Changes
✅ sprinkle-crud6 package structure unchanged  
✅ peerDependency approach maintained  
✅ Users still have theme flexibility  
✅ Integration pattern documented for users  

## For Users

### Installing sprinkle-crud6
Users installing sprinkle-crud6 should:

1. **Install PHP package:**
   ```bash
   composer require ssnukala/sprinkle-crud6
   ```

2. **Install NPM package with peer dependencies:**
   ```bash
   npm install @ssnukala/sprinkle-crud6
   npm install @userfrosting/theme-pink-cupcake  # Required for frontend
   ```

3. **Configure sprinkles in MyApp.php:**
   ```php
   public function getSprinkles(): array
   {
       return [
           Core::class,
           Account::class,
           Admin::class,
           CRUD6::class,
           PinkCupcake::class,  // Required for CRUD6 frontend components
       ];
   }
   ```

### Using Custom Themes
If users want to use a different theme, they would need to ensure it provides:
- `UFModal` component
- `UFModalConfirmation` component
- `UFFormValidationError` component

Or modify CRUD6 components to use their theme's components.

## References

- [UserFrosting 6 Documentation](https://learn.userfrosting.com)
- [PinkCupcake Theme Repository](https://github.com/userfrosting/theme-pink-cupcake)
- [sprinkle-crud6 Package](https://github.com/ssnukala/sprinkle-crud6)
- [CHANGELOG.md](./CHANGELOG.md) - Documents UFFormValidationError global registration
- [package.json](./package.json) - Shows theme as peerDependency

## User Feedback Credit

Thanks to @ssnukala for the feedback that identified the frontend component dependency on theme-pink-cupcake. The initial approach to remove the theme would have broken frontend rendering.
