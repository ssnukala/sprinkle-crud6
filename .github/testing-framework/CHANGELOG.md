# Changelog

All notable changes to the UserFrosting 6 Integration Testing Framework will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-12-11

### Added
- Initial release of reusable integration testing framework
- Automated installer script (`install.sh`) with parameterization support
- Template configuration files for paths and seeds
- Reusable PHP testing scripts:
  - `run-seeds.php` - Run database seeds from configuration
  - `check-seeds-modular.php` - Validate seed data
  - `test-seed-idempotency-modular.php` - Test seed idempotency
  - `test-paths.php` - Test API endpoints and frontend routes
- Reusable JavaScript testing scripts:
  - `take-screenshots-modular.js` - Capture frontend screenshots with Playwright
- Comprehensive documentation:
  - Main README with overview and quick start
  - Installation guide with multiple installation methods
  - Configuration guide with field-by-field reference
  - Workflow examples for GitHub Actions
  - API reference for all scripts
  - Migration guide for existing sprinkles
- Package metadata for npm distribution
- Support for dry-run mode in installer
- Auto-detection of framework source directory
- Automatic namespace generation from sprinkle name
- Custom namespace support via CLI flag

### Framework Features
- **Configuration-Driven**: Define tests in JSON, not code
- **Parameterized Installation**: Single command setup with sprinkle name
- **Battle-Tested**: Production-proven in CRUD6 sprinkle
- **Well-Documented**: Complete guides and examples
- **Easy Updates**: Re-run installer to get latest scripts
- **Zero Lock-In**: Configuration files are yours to keep

### Supported Testing Scenarios
- API endpoint testing (authenticated and unauthenticated)
- Frontend route testing (with optional screenshots)
- Database seed execution and validation
- Seed idempotency testing (no duplicates on re-run)
- Role and permission validation
- Role-permission assignment validation
- JSON response validation
- HTTP status code validation
- Redirect validation

### Documentation
- [README.md](README.md) - Framework overview and quick start
- [docs/INSTALLATION.md](docs/INSTALLATION.md) - Installation guide
- [docs/CONFIGURATION.md](docs/CONFIGURATION.md) - Configuration reference
- [docs/WORKFLOW_EXAMPLE.md](docs/WORKFLOW_EXAMPLE.md) - GitHub Actions examples
- [docs/API_REFERENCE.md](docs/API_REFERENCE.md) - Script usage reference
- [docs/MIGRATION.md](docs/MIGRATION.md) - Migration guide

### Example Implementations
- [sprinkle-crud6](https://github.com/ssnukala/sprinkle-crud6) - Original implementation
- sprinkle-c6admin - Coming soon

## [Unreleased]

### Planned Features
- [ ] Framework version checking and update notifications
- [ ] Support for multiple authentication methods
- [ ] Extended validation types (schema validation, field-level checks)
- [ ] Performance testing integration
- [ ] Test report generation (HTML/PDF)
- [ ] Integration with popular CI/CD platforms (CircleCI, Travis, Jenkins)
- [ ] Database snapshot/restore for test isolation
- [ ] Mock data generation from schemas
- [ ] Video recording of frontend tests
- [ ] Parallel test execution support

### Under Consideration
- Separate npm package for easier distribution
- Composer package for PHP dependency management
- Docker image with pre-installed dependencies
- VS Code extension for config file editing
- Interactive CLI for config generation
- Integration with UserFrosting bakery commands

## Version History

### Version Numbering

This project follows Semantic Versioning:
- **Major version** (1.x.x): Breaking changes to configuration format or script interfaces
- **Minor version** (x.1.x): New features, backward compatible
- **Patch version** (x.x.1): Bug fixes, documentation updates

### Upgrade Guide

When upgrading between versions:

**From 0.x to 1.0:**
- Initial release - no upgrade needed

**Future Upgrades:**
- Check CHANGELOG for breaking changes
- Re-run installer to get latest scripts
- Review configuration file changes
- Test locally before deploying

## Contributing

Found a bug or want to add a feature?
1. Open an issue on [GitHub](https://github.com/ssnukala/sprinkle-crud6/issues)
2. Submit a PR with your changes
3. All sprinkles benefit from your contribution!

## Support

- **Issues**: [GitHub Issues](https://github.com/ssnukala/sprinkle-crud6/issues)
- **Discussions**: [GitHub Discussions](https://github.com/ssnukala/sprinkle-crud6/discussions)
- **Documentation**: See the `docs/` directory

---

**Built for UserFrosting 6** - Making integration testing easy and consistent across all sprinkles.
