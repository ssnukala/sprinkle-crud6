# Middleware Injection Pattern - Clarification and Correction

**Date:** October 25, 2025  
**Issue:** Incorrect refactoring of controller parameter injection  
**Resolution:** Reverted changes and documented correct pattern

## What Happened

### Incorrect Changes (Reverted)
Commits 39a4ba8 and 23de0db incorrectly changed all CRUD6 controllers based on a misunderstanding of how UserFrosting 6 handles middleware-injected data.

**Incorrect assumption:** 
- Slim 4 doesn't automatically inject request attributes as controller parameters
- Controllers must retrieve data using `$request->getAttribute()`

**What was done (WRONG):**
Changed all controllers from:
```php
public function __invoke(
    array $crudSchema,
    CRUD6ModelInterface $crudModel,
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface
```

To:
```php
public function __invoke(
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface {
    $crudSchema = $request->getAttribute('crudSchema');
    $crudModel = $request->getAttribute('crudModel');
    // ...
}
```

### Why This Was Wrong

**UserFrosting 6 DOES support automatic injection of request attributes as controller parameters.**

This is a core framework feature, not a limitation. The pattern is used throughout UserFrosting, as demonstrated in sprinkle-admin.

## The Correct Pattern

### How It Works in UserFrosting 6

UserFrosting extends Slim 4 with automatic parameter resolution from request attributes. When a controller method has parameters, the framework:

1. Checks the parameter name and type
2. Looks for matching request attributes
3. Automatically injects them as parameters

### Example from sprinkle-admin

**Middleware (GroupInjector):**
```php
class GroupInjector extends AbstractInjector
{
    // Sets request attribute named 'group'
    protected string $attribute = 'group';
    
    protected function getInstance(?string $slug): GroupInterface
    {
        // Load and return the group
        return $group;
    }
}
```

**Controller (GroupApi):**
```php
class GroupApi
{
    public function __invoke(GroupInterface $group, Response $response): Response
    {
        // $group is automatically injected!
        // The parameter name 'group' matches the attribute name 'group'
        // The type GroupInterface matches the injected instance
    }
}
```

**Reference:**
- [GroupApi.php](https://github.com/userfrosting/sprinkle-admin/blob/6.0/app/src/Controller/Group/GroupApi.php)
- [GroupInjector.php](https://github.com/userfrosting/sprinkle-admin/blob/6.0/app/src/Middlewares/GroupInjector.php)

### CRUD6 Implementation (Correct)

**Middleware (CRUD6Injector):**
```php
// Sets request attributes
$request = $request
    ->withAttribute('crudModel', $instance)   // Attribute name: crudModel
    ->withAttribute('crudSchema', $schema);   // Attribute name: crudSchema
```

**Controllers (All CRUD6 actions):**
```php
public function __invoke(
    array $crudSchema,                      // Parameter name matches attribute 'crudSchema'
    CRUD6ModelInterface $crudModel,         // Parameter name matches attribute 'crudModel'
    ServerRequestInterface $request,
    ResponseInterface $response
): ResponseInterface
{
    // Both $crudSchema and $crudModel are automatically injected
    // This is the CORRECT pattern - DO NOT CHANGE
}
```

## Why The Pattern Works

UserFrosting 6 uses PHP-DI with custom configuration that enables this automatic injection. The framework:

1. Intercepts controller invocation
2. Inspects controller method parameters
3. Matches parameter names to request attribute names
4. Injects the values from request attributes

This is NOT standard Slim 4 behavior - it's a UserFrosting 6 enhancement.

## What Was Fixed

### Commit f8a433e - Revert
- Restored all 7 controllers to original parameter injection pattern
- Removed incorrect documentation file
- All controllers now correctly expect parameters to be injected

### Commit 35d9f7e - Documentation
- Added comprehensive documentation to `.github/copilot-instructions.md`
- New section: "Middleware Injection Pattern"
- Explains the pattern with examples
- Warns against future incorrect refactoring
- References official UserFrosting examples

## Controllers Affected

All restored to correct pattern:
1. SprunjeAction.php
2. ApiAction.php
3. CreateAction.php
4. EditAction.php
5. DeleteAction.php
6. UpdateFieldAction.php
7. RelationshipAction.php

## Lessons Learned

1. **Trust the existing pattern:** If code was working, investigate thoroughly before changing it
2. **Check framework documentation:** UserFrosting 6 extends Slim 4 with additional features
3. **Reference official examples:** sprinkle-admin provides canonical examples of patterns
4. **Document non-obvious patterns:** The middleware injection pattern needed explicit documentation
5. **Test assumptions:** The assumption about Slim 4 limitations was incorrect for UserFrosting 6

## Related PRs

- **PR #119:** Previously fixed the same issue when the pattern was broken
- **Current PR:** Re-fixed after incorrectly "fixing" it again

## Prevention

Updated `.github/copilot-instructions.md` with:
- Clear explanation of the pattern
- Examples from sprinkle-admin
- DO NOT warnings
- References to official documentation

This ensures future AI agents understand this is a core UserFrosting 6 feature and won't "fix" it again.

## Conclusion

The middleware injection pattern in CRUD6 controllers is **CORRECT** and follows UserFrosting 6 conventions exactly as demonstrated in sprinkle-admin. Controllers should continue to receive `crudSchema` and `crudModel` as method parameters, not retrieve them from request attributes.

Any 500 errors are NOT caused by this pattern and need separate investigation.
