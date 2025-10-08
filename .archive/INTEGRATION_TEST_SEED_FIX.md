# Integration Test Seed Command Fix

## Issue

Integration test was failing at the `php bakery seed` step due to an interactive confirmation prompt, even when using the `--force` flag. The workflow run at https://github.com/ssnukala/sprinkle-crud6/actions/runs/18302767685/job/52113754889 showed the test hanging waiting for user input.

## Root Cause

UserFrosting 6's `SeedCommand` (from `@userfrosting/sprinkle-core`) has a configuration option `bakery.confirm_sensitive_command` that controls whether sensitive bakery commands prompt for user confirmation. When this is set to `true` (or not explicitly set to `false`), the command will always prompt for confirmation, even with the `--force` flag in some scenarios.

## Solution

Added the `BAKERY_CONFIRM_SENSITIVE_COMMAND=false` environment variable to the `.env` configuration in the integration test workflow. This globally disables interactive prompts for all sensitive bakery commands in the CI environment.

### Changes Made

#### 1. `.github/workflows/integration-test.yml`

Added three lines to the "Setup environment" step to append the bakery configuration to the `.env` file:

```yaml
# Disable interactive prompts for bakery commands in CI environment
echo "" >> app/.env
echo "# Bakery Configuration" >> app/.env
echo "BAKERY_CONFIRM_SENSITIVE_COMMAND=false" >> app/.env
```

This ensures that:
- `php bakery seed` runs without prompting
- `php bakery migrate` continues to work as expected
- All other sensitive bakery commands work in automated environments

#### 2. `INTEGRATION_TESTING.md`

Updated documentation in two places:

**a) Database configuration section (Step 8):**
- Added `BAKERY_CONFIRM_SENSITIVE_COMMAND=false` to the example `.env` configuration
- Added comments explaining this is useful for CI/CD environments

**b) Seed command section (Step 10):**
- Added explanation about the environment variable approach
- Added a note explaining the difference between `--force` flag and environment variable
- Recommended using the environment variable for CI/CD pipelines

## Benefits

1. **Reliability**: Prevents integration tests from hanging on interactive prompts
2. **Flexibility**: Works for all sensitive bakery commands, not just seed
3. **Clarity**: Explicitly documents the CI/CD configuration approach
4. **Best Practice**: Follows UserFrosting 6 patterns for automated environments

## Testing

- ✅ YAML syntax validated with Python yaml parser
- ✅ Documentation updated with clear examples
- ⏳ Waiting for next workflow run to verify fix

## Reference

- Problem statement: https://github.com/ssnukala/sprinkle-crud6/actions/runs/18302767685/job/52113754889
- UserFrosting Core SeedCommand: `@userfrosting/sprinkle-core/files/app/src/Bakery/SeedCommand.php`
- Configuration option: `bakery.confirm_sensitive_command`

## Usage

### For Local Development

You can keep interactive prompts enabled for safety:
```bash
php bakery seed
# Will prompt: "Are you sure you want to seed the database? (yes/no)"
```

### For CI/CD Environments

Set the environment variable to skip prompts:
```env
BAKERY_CONFIRM_SENSITIVE_COMMAND=false
```

Or use the `--force` flag:
```bash
php bakery seed --force
```

**Recommendation**: Use the environment variable approach in CI/CD as it's more comprehensive and applies to all sensitive commands.

## Related Files

- `.github/workflows/integration-test.yml` - Integration test workflow
- `INTEGRATION_TESTING.md` - User-facing integration testing guide
- `.devcontainer/setup-project.sh` - Development container setup (no changes needed)
