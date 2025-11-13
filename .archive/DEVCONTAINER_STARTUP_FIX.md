# DevContainer Startup Fix

> **Update**: The file `docker-compose.yml` was renamed to `compose.yml` to align with Docker Compose v2 naming conventions. All references in this document have been updated accordingly.

## Issue
DevContainer was failing to start in GitHub Codespaces with the following error:
```
Shell server terminated (code: 1, signal: null)
Error response from daemon: Container <id> is not running
{"outcome":"error","message":"An error occurred setting up the container."}
```

## Root Causes

### 1. Obsolete `version` Attribute
The `compose.yml` file included `version: '3.8'` which is now obsolete in newer versions of Docker Compose. This caused warnings:
```
WARN[0000] /var/lib/docker/codespacemount/workspace/sprinkle-crud6/.devcontainer/compose.yml: 
the attribute `version` is obsolete, it will be ignored, please remove it to avoid potential confusion
```

### 2. Invalid workspaceFolder Path
The `devcontainer.json` specified `workspaceFolder: "/workspace/userfrosting"`, but this directory doesn't exist when the container first starts. It's only created later by the `setup-project.sh` script. This caused the container to fail during initialization.

### 3. Container Exits Immediately
The container had no long-running command to keep it alive. While the Dockerfile specifies `CMD ["bash"]`, this exits immediately in a non-interactive context, causing the container to stop.

## Solutions

### 1. Remove Obsolete `version` Attribute
**File**: `.devcontainer/compose.yml`
```diff
-version: '3.8'
-
 services:
   sprinkle-crud6:
```

**Reason**: Docker Compose v2 no longer requires or uses the `version` field. Modern Docker uses `docker compose` (space) instead of `docker-compose` (hyphen).

### 2. Fix workspaceFolder Path
**File**: `.devcontainer/devcontainer.json`
```diff
-    "workspaceFolder": "/workspace/userfrosting",
+    "workspaceFolder": "/workspace",
```

**Reason**: The devcontainer mounts the repository at `/workspace` (see `volumes: - ..:/workspace:cached` in compose.yml). The workspaceFolder must point to an existing directory when the container starts. The UserFrosting project at `/workspace/userfrosting` is created later by the setup script.

### 3. Add `sleep infinity` Command
**File**: `.devcontainer/compose.yml`
```diff
     networks:
       - sprinkle-crud6-network
+    # Keep container running for devcontainer
+    command: sleep infinity
```

**Reason**: DevContainers need a long-running process to keep the container alive. `sleep infinity` is a standard pattern for development containers that don't have a persistent service running.

## Documentation Updates

**File**: `.devcontainer/README.md`

Updated documentation to reflect that:
- The workspace folder is `/workspace` (root of the mounted repository)
- The UserFrosting project at `/workspace/userfrosting` is created during setup
- Users should navigate to `/workspace/userfrosting` for UserFrosting commands after setup completes

## Validation

### Syntax Validation
```bash
# Validate devcontainer.json
cat .devcontainer/devcontainer.json | jq . > /dev/null
✓ devcontainer.json is valid JSON

# Validate compose.yml
docker compose config
✓ compose.yml is valid
```

### Expected Behavior After Fix
1. Container builds successfully
2. Container starts and remains running (`sleep infinity` keeps it alive)
3. VS Code opens to `/workspace` directory
4. `postCreateCommand` runs `setup-project.sh` which creates `/workspace/userfrosting`
5. Users can then navigate to `/workspace/userfrosting` to work with the UserFrosting project

## Related Changes
- GitHub Issue: Container startup failure in Codespaces
- Commit: "Fix devcontainer startup issues - remove obsolete version, fix workspaceFolder, add sleep infinity"
- Date: 2025-11-13

## References
- Docker Compose v2 documentation: https://docs.docker.com/compose/compose-file/
- DevContainers specification: https://containers.dev/
- Docker Compose command change: `docker-compose` (v1) → `docker compose` (v2)
