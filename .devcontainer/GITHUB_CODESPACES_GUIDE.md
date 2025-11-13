# GitHub Codespaces with DevContainer - Official Documentation

## Overview

GitHub Codespaces provides cloud-hosted development environments that work seamlessly with DevContainers. This guide links to official GitHub documentation for using DevContainers in Codespaces.

## Official GitHub Documentation

### Getting Started with Codespaces
- **Introduction to Codespaces**: https://docs.github.com/en/codespaces/overview
- **Quickstart for GitHub Codespaces**: https://docs.github.com/en/codespaces/getting-started/quickstart
- **Deep dive into GitHub Codespaces**: https://docs.github.com/en/codespaces/getting-started/deep-dive

### DevContainer Configuration
- **Introduction to dev containers**: https://docs.github.com/en/codespaces/setting-up-your-project-for-codespaces/adding-a-dev-container-configuration/introduction-to-dev-containers
- **Setting up your project for Codespaces**: https://docs.github.com/en/codespaces/setting-up-your-project-for-codespaces
- **Adding a dev container configuration**: https://docs.github.com/en/codespaces/setting-up-your-project-for-codespaces/adding-a-dev-container-configuration

### Using Codespaces
- **Creating a codespace**: https://docs.github.com/en/codespaces/developing-in-codespaces/creating-a-codespace
- **Developing in a codespace**: https://docs.github.com/en/codespaces/developing-in-codespaces
- **Opening an existing codespace**: https://docs.github.com/en/codespaces/developing-in-codespaces/opening-an-existing-codespace
- **Stopping and starting a codespace**: https://docs.github.com/en/codespaces/developing-in-codespaces/stopping-and-starting-a-codespace
- **Deleting a codespace**: https://docs.github.com/en/codespaces/developing-in-codespaces/deleting-a-codespace

### Troubleshooting
- **Troubleshooting your codespace**: https://docs.github.com/en/codespaces/troubleshooting
- **Troubleshooting creation and deletion of codespaces**: https://docs.github.com/en/codespaces/troubleshooting/troubleshooting-creation-and-deletion-of-codespaces
- **Codespaces logs**: https://docs.github.com/en/codespaces/troubleshooting/github-codespaces-logs

## DevContainer Specification
- **DevContainers Specification**: https://containers.dev/
- **DevContainer JSON Reference**: https://containers.dev/implementors/json_reference/
- **DevContainer Features**: https://containers.dev/features

## Docker Compose in DevContainers
- **Docker Compose in DevContainers**: https://containers.dev/guide/dockerfile#using-docker-compose
- **Docker Compose File Reference**: https://docs.docker.com/compose/compose-file/

> **Note**: This repository uses `compose.yml` (the modern Docker Compose v2 naming convention) instead of the legacy `docker-compose.yml` name. Both work with Docker Compose v2, but `compose.yml` is the recommended naming for new projects.

## Quick Start for This Repository

### 1. Create a Codespace

**Option A: Via GitHub Web UI**
1. Go to https://github.com/ssnukala/sprinkle-crud6
2. Click the green "Code" button
3. Select "Codespaces" tab
4. Click "Create codespace on main" (or your branch)

**Option B: Via GitHub CLI**
```bash
# Install GitHub CLI if needed: https://cli.github.com/
gh codespace create --repo ssnukala/sprinkle-crud6
```

**Option C: Via VS Code**
1. Install "GitHub Codespaces" extension
2. Open Command Palette (Ctrl+Shift+P / Cmd+Shift+P)
3. Select "Codespaces: Create New Codespace"
4. Choose repository: ssnukala/sprinkle-crud6

### 2. Wait for Container to Build

The first time you create a codespace:
- Docker will build the container from `.devcontainer/Dockerfile`
- This includes installing PHP 8.2, Node.js 20, MySQL client, and all dependencies
- Build time: ~3-5 minutes

Subsequent starts use cached builds and are much faster (~30 seconds).

### 3. Automatic Setup

After the container starts, the `postCreateCommand` automatically runs:
- Executes `.devcontainer/setup-project.sh`
- Clones CRUD6 and C6Admin sprinkles from GitHub
- Creates UserFrosting 6 project at `/workspace/userfrosting`
- Configures Composer and NPM dependencies
- Runs database migrations and seeds
- Creates admin user (username: `admin`, password: `admin123`)
- Setup time: ~5-10 minutes

### 4. Start Development

Once setup completes, you can start the development servers:

```bash
# Terminal 1: Start UserFrosting server
cd /workspace/userfrosting
php bakery serve

# Terminal 2: Start Vite dev server
cd /workspace/userfrosting
php bakery assets:vite
```

Then open the forwarded port 8080 in your browser to access the application.

## Common Codespaces Issues

### Issue: Container Fails to Start

**Symptoms**: Error message "An error occurred setting up the container"

**Solution**: This was fixed in our devcontainer configuration by:
1. Setting `workspaceFolder` to `/workspace` (not a non-existent subdirectory)
2. Removing obsolete `version` attribute from `compose.yml`
3. Adding `command: sleep infinity` to keep container running

See `.archive/DEVCONTAINER_STARTUP_FIX.md` for details.

### Issue: Setup Script Fails

**Symptoms**: Setup script exits with errors during postCreateCommand

**Common Causes**:
1. MySQL not ready - script waits up to 60 seconds, may need manual retry
2. Network issues - GitHub API rate limits or connection problems
3. Disk space - Codespaces have storage limits

**Solution**: Re-run the setup script manually:
```bash
bash /workspace/.devcontainer/setup-project.sh
```

### Issue: Port Forwarding Not Working

**Solution**: 
1. Open Ports panel in VS Code (View → Ports)
2. Verify ports 8080, 5173, and 3306 are forwarded
3. Make ports public if needed (right-click port → Port Visibility → Public)

### Issue: Running Out of Resources

**Solution**:
1. Check your Codespaces usage: https://github.com/settings/billing
2. Stop unused codespaces to free resources
3. Consider upgrading to a larger machine type

## Codespaces Configuration

### Machine Types
- **2-core, 8 GB RAM, 32 GB storage** (default, free tier)
- **4-core, 16 GB RAM, 32 GB storage** (paid)
- **8-core, 32 GB RAM, 64 GB storage** (paid)
- **16-core, 64 GB RAM, 128 GB storage** (paid)
- **32-core, 128 GB RAM, 256 GB storage** (paid)

For UserFrosting development, the default 2-core instance should be sufficient.

### Timeout Settings
Configure in repository settings or user settings:
- **Idle timeout**: Default 30 minutes
- **Retention period**: Default 30 days

## Environment Variables and Secrets

To add secrets to your codespace:
1. Go to repository settings → Secrets and variables → Codespaces
2. Add secrets (e.g., API keys, tokens)
3. Secrets are automatically available as environment variables

## Prebuilds

For faster codespace startup, configure prebuilds:
1. Go to repository settings → Codespaces
2. Set up prebuild configuration
3. Choose branches to prebuild
4. GitHub will prebuild containers on each push

Documentation: https://docs.github.com/en/codespaces/prebuilding-your-codespaces

## Billing and Limits

- **Free tier**: 120 core-hours/month for personal accounts
- **Core hours**: 2-core machine × 60 hours = 120 core-hours
- **Storage**: 15 GB free, then paid

Check usage: https://github.com/settings/billing

Documentation: https://docs.github.com/en/billing/managing-billing-for-github-codespaces

## VS Code Desktop vs Web

### Web Editor (default)
- Runs in browser at `*.github.dev`
- No local installation needed
- Full VS Code experience

### VS Code Desktop
To open codespace in local VS Code:
1. Install VS Code and "GitHub Codespaces" extension
2. Open codespace from VS Code command palette
3. Or use GitHub CLI: `gh codespace code`

## Advanced Topics

### Dotfiles
Automatically configure your environment across all codespaces:
- https://docs.github.com/en/codespaces/customizing-your-codespace/personalizing-github-codespaces-for-your-account#dotfiles

### Port Forwarding
- https://docs.github.com/en/codespaces/developing-in-codespaces/forwarding-ports-in-your-codespace

### Managing Secrets
- https://docs.github.com/en/codespaces/managing-your-codespaces/managing-encrypted-secrets-for-your-codespaces

### Lifecycle Scripts
- `postCreateCommand`: Runs once after container creation
- `postStartCommand`: Runs each time codespace starts
- `postAttachCommand`: Runs when attaching to codespace

## Getting Help

### Official Support
- **GitHub Codespaces Support**: https://github.com/orgs/community/discussions/categories/codespaces
- **DevContainers Support**: https://github.com/devcontainers/spec/discussions

### Community Resources
- **DevContainers Repository**: https://github.com/devcontainers
- **DevContainers Templates**: https://github.com/devcontainers/templates
- **DevContainers Features**: https://github.com/devcontainers/features

## This Repository's DevContainer

Our `.devcontainer/` configuration:
- **Base Image**: PHP 8.2-FPM with Node.js 20 (multi-stage build)
- **Services**: App container + MySQL 8.0
- **Workspace Folder**: `/workspace` (repository root)
- **Setup Script**: `.devcontainer/setup-project.sh`
- **UserFrosting Project**: Created at `/workspace/userfrosting` during setup

For detailed setup information, see `.devcontainer/README.md`.

## Next Steps

1. **Review**: Read `.devcontainer/README.md` for detailed setup information
2. **Create**: Launch a codespace from GitHub
3. **Wait**: Let the setup script complete (5-10 minutes)
4. **Develop**: Start the servers and begin coding
5. **Commit**: Your changes are automatically saved in the codespace

Happy coding! 🚀
