# Dev Container Node.js Build Fix

## Issue
The dev container build was failing in GitHub Codespaces with the following error:

```
failed to solve: process "/bin/sh -c curl -fsSL --insecure https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs npm" did not complete successfully: exit code: 100
```

## Root Cause
The NodeSource repository setup script approach was failing due to:
1. Network connectivity issues in GitHub Codespaces build environments
2. SSL certificate validation problems even with `--insecure` flag
3. Repository availability and rate limiting issues
4. Non-deterministic behavior in restricted network environments

## Solution
Implemented a **multi-stage Docker build** approach that uses the official Node.js Docker image instead of trying to install Node.js from repositories.

### Before (Unreliable)
```dockerfile
# Install Node.js 20 LTS and npm
# Handle SSL certificate issues in restricted network environments
RUN curl -fsSL --insecure https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs npm
```

### After (Reliable)
```dockerfile
# Multi-stage build: Get Node.js from official image
FROM node:20-bookworm-slim AS node-stage

# Main PHP image with Node.js copied from node-stage
FROM php:8.2-fpm

# ... (system dependencies installation)

# Copy Node.js and npm from the official Node.js image
COPY --from=node-stage /usr/local/bin/node /usr/local/bin/node
COPY --from=node-stage /usr/local/lib/node_modules /usr/local/lib/node_modules

# Create symlinks for npm and npx
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
```

## Benefits

1. **Reliability**: Uses official Docker images from Docker Hub, which are highly available and cached
2. **Performance**: Pre-built binaries are faster than repository installation
3. **No Network Issues**: Eliminates SSL certificate and network connectivity problems
4. **Deterministic**: Same Node.js version every time (specified in FROM statement)
5. **Security**: No need for `--insecure` flags or bypassing certificate validation
6. **Maintainability**: Easier to update Node.js version (just change the tag)

## Testing Results

Build completed successfully with the following versions installed:

```
✅ PHP 8.2.29
✅ Composer 2.8.12
✅ Node.js v20.19.5
✅ npm 10.8.2
```

Both `docker build` and `docker compose build` commands completed successfully.

## Technical Notes

### Multi-Stage Build Pattern
The multi-stage build uses two stages:

1. **node-stage**: Uses `node:20-bookworm-slim` as the base to get Node.js binaries
2. **stage-1**: The main PHP image that copies Node.js from node-stage

This pattern is recommended by Docker for combining tools from different base images.

### Symlink Creation
The npm and npx symlinks are necessary because:
- npm is actually a Node.js script located in `/usr/local/lib/node_modules/npm/bin/`
- The symlinks make npm and npx available in the system PATH
- This follows the standard Node.js installation pattern

### Node.js Version Selection
We use `node:20-bookworm-slim` because:
- Node.js 20 is the current LTS (Long Term Support) version
- `bookworm` matches Debian 12, which is compatible with PHP 8.2-fpm base image
- `-slim` variant reduces image size while including all necessary Node.js files

## Files Modified
- `.devcontainer/Dockerfile`: Changed Node.js installation method from repository-based to multi-stage build

## Related Issues
- GitHub Codespaces build failure (exit code 100)
- NodeSource repository connectivity issues
- SSL certificate validation problems in restricted environments

## Date
2025-11-13

## PR
Issue resolved in branch `copilot/fix-dev-container-build-error`
