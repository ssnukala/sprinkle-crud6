# GitHub Authentication Fix for Composer

## Issue
When running `composer install` locally or in certain CI environments, you may encounter:
```
Could not authenticate against github.com
```

This prevents downloading UserFrosting dependencies from GitHub.

## Root Cause
Composer needs GitHub authentication to:
1. Avoid API rate limits (60 requests/hour unauthenticated vs 5000/hour authenticated)
2. Access private repositories (if any)
3. Download packages reliably without timeouts

## Solutions

### Solution 1: GitHub Personal Access Token (Recommended for Local Development)

1. **Create a GitHub Personal Access Token:**
   - Go to https://github.com/settings/tokens
   - Click "Generate new token (classic)"
   - Select scopes: `repo` (if accessing private repos) or just `public_repo`
   - Copy the generated token

2. **Configure Composer to use the token:**
   ```bash
   composer config --global github-oauth.github.com YOUR_TOKEN_HERE
   ```

3. **Or set as environment variable:**
   ```bash
   export COMPOSER_AUTH='{"github-oauth":{"github.com":"YOUR_TOKEN_HERE"}}'
   composer install
   ```

### Solution 2: For GitHub Actions (Already Configured)

GitHub Actions automatically provides authentication through the `GITHUB_TOKEN` secret. The workflow in `.github/workflows/unit-tests.yml` should work without additional configuration because:

1. GitHub Actions injects `GITHUB_TOKEN` automatically
2. Composer detects and uses it automatically
3. No additional configuration needed in the workflow file

### Solution 3: Ignore Platform Requirements (Last Resort)

If you can't authenticate but need to test syntax/structure:

```bash
composer install --ignore-platform-reqs --no-scripts
```

**Warning:** This may install incompatible versions and skip important setup scripts.

## Verification

After configuring authentication, verify it works:

```bash
composer config --list --global | grep github-oauth
```

You should see your configured token (partially masked).

## Security Notes

- **Never commit tokens to git**
- Use `--global` flag to store in your user config, not project config
- Tokens can be revoked anytime from GitHub settings
- Use environment variables in CI/CD pipelines, not hardcoded tokens

## CI Environment

In the actual GitHub Actions CI environment (not local simulation), this issue should NOT occur because:

1. GitHub Actions provides `GITHUB_TOKEN` automatically
2. Composer auto-detects and uses it
3. The workflow has proper cache configuration to speed up subsequent runs

The authentication errors we see locally are expected when not running in the actual GitHub Actions environment.
