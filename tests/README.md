# GLPI Costs Plugin Test Suite

This directory contains test scripts and reports for the GLPI Costs plugin GLPI 11 upgrade.

## Test Scripts

### verify-plugin-structure.php

Comprehensive verification script that checks:
- Required plugin files exist
- Version constants are correctly set
- Hook registration uses GLPI 11 compatible patterns
- Database API usage is GLPI 11 compatible
- Template renderer usage is correct
- Deprecated patterns are identified
- Twig templates are valid

**Usage**:
```bash
docker run --rm -v $(pwd):/app -w /app php:8.1-cli php tests/verify-plugin-structure.php
```

**Exit Codes**:
- 0: All tests passed
- 1: Tests failed with errors

### test-installation.php

Installation function testing script (requires full GLPI environment).

**Note**: This script requires a complete GLPI installation to run. Use `verify-plugin-structure.php` for standalone testing.

### test-uninstallation.php

Uninstallation function testing script that verifies:
- plugin_costs_uninstall() function exists and executes successfully
- All plugin database tables are properly dropped
- All class uninstall methods are present and called
- No errors occur during uninstallation process

**Usage**:
```bash
docker run --rm -v $(pwd):/app -w /app php:8.1-cli php tests/test-uninstallation.php
```

**Exit Codes**:
- 0: All tests passed (uninstallation works correctly)
- 1: Tests failed (uninstallation issues detected)

**Requirements Validated**: 10.2

### test-entity-cost-configuration.php

Entity cost configuration testing script that verifies:
- Creating entity cost configurations
- Testing fixed cost and time cost settings
- Testing inheritance configuration
- Verifying configuration persistence
- Testing getFromDBByEntity method

**Usage**:
```bash
docker run --rm -v $(pwd):/app -w /app php:8.1-cli php tests/test-entity-cost-configuration.php
```

**Exit Codes**:
- 0: All tests passed
- 1: Tests failed

**Requirements Validated**: 12.2  
**Task**: 5.1

### test-ticket-cost-generation.php

Ticket cost generation testing script that verifies:
- Creating tickets in billable entities
- Verifying cost entries are generated
- Testing billable vs non-billable tickets
- Testing auto-billable behavior
- Testing manual billable override
- Testing ticket billable status updates

**Usage**:
```bash
docker run --rm -v $(pwd):/app -w /app php:8.1-cli php tests/test-ticket-cost-generation.php
```

**Exit Codes**:
- 0: All tests passed
- 1: Tests failed

**Requirements Validated**: 12.3  
**Task**: 5.3

### test-task-cost-calculation.php

Task cost calculation testing script that verifies:
- Adding tasks to billable tickets
- Verifying time-based costs are calculated correctly
- Testing with different time durations (30 min, 1.5 hours, 4 hours)
- Testing private task handling (cost_private flag)
- Testing task cost updates

**Usage**:
```bash
docker run --rm -v $(pwd):/app -w /app php:8.1-cli php tests/test-task-cost-calculation.php
```

**Exit Codes**:
- 0: All tests passed
- 1: Tests failed

**Requirements Validated**: 12.4  
**Task**: 5.5

### test-hook-execution.php

Hook execution testing script that verifies:
- POST_ITEM_FORM hook on ticket form
- PRE_ITEM_UPDATE hook on ticket update
- PRE_ITEM_UPDATE hook on task update
- ITEM_ADD hooks for tickets and tasks
- ITEM_PURGE hook for task deletion
- Hook registration in setup.php using GLPI 11 compatible patterns

**Usage**:
```bash
docker run --rm -v $(pwd):/app -w /app php:8.1-cli php tests/test-hook-execution.php
```

**Exit Codes**:
- 0: All tests passed (all hooks execute correctly)
- 1: Tests failed (hook execution issues detected)

**Requirements Validated**: 3.7  
**Task**: 9.1

### test-migration.php

Plugin migration testing script that verifies:
- Fresh installation scenario (baseline)
- Upgrade from old version with costs_id field
- Migration operations (field additions, table creation)
- Database schema uses correct API methods (DBConnection)
- Version constant is set correctly

**Usage**:
```bash
docker run --rm -v $(pwd):/app -w /app php:8.1-cli php tests/test-migration.php
```

**Exit Codes**:
- 0: All tests passed (migration works correctly)
- 1: Tests failed (migration issues detected)

**Requirements Validated**: 7.7  
**Task**: 11.1

## Test Reports

### installation-test-report.md

Comprehensive test report documenting:
- Test environment setup
- Test results for all verification checks
- Requirements validation
- Compatibility assessment
- Recommendations for production deployment

## Running Tests

### Quick Verification

Run the structure verification script to quickly check plugin compatibility:

```bash
docker run --rm -v $(pwd):/app -w /app php:8.1-cli php tests/verify-plugin-structure.php
```

### CI/CD Integration

Add to your CI/CD pipeline:

```yaml
test:
  script:
    - docker run --rm -v $(pwd):/app -w /app php:8.1-cli php tests/verify-plugin-structure.php
```

## Test Results Summary

**Last Test Run**: 2026-01-30  
**Plugin Version**: 3.1.0  
**Result**: ✓ PASSED (7/7 tests)

All critical GLPI 11 compatibility requirements have been validated:
- ✓ Version constants
- ✓ Hook registration
- ✓ Database API usage
- ✓ Template renderer usage
- ✓ Plugin structure
- ✓ Twig templates
- ✓ Installation functions

## Known Issues

### ARM64 (Apple Silicon) Docker Compatibility

The `diouxx/glpi:latest` Docker image does not support ARM64 architecture, causing segmentation faults when running on Apple Silicon Macs with platform emulation.

**Workaround**: Use the static verification scripts instead of full Docker-based GLPI testing on ARM64 systems.

**Alternative**: Test on x86_64 systems or use a native GLPI installation.

## Next Steps

1. ✓ Task 3.1: Install plugin on GLPI 11 test instance - COMPLETED
2. ✓ Task 4.1: Test uninstallation process - COMPLETED
3. ✓ Task 5.1: Test entity cost configuration - COMPLETED
4. ✓ Task 5.3: Test ticket cost generation - COMPLETED
5. ✓ Task 5.5: Test task cost calculation - COMPLETED
6. Task 3.2: Write property test for plugin installation - PENDING (OPTIONAL)
7. Task 3.3: Write property test for database table creation - PENDING (OPTIONAL)
8. Task 4.2: Write property test for plugin uninstallation - PENDING (OPTIONAL)
9. Task 5.2: Write property test for cost configuration round trip - PENDING (OPTIONAL)
10. Task 5.4: Write property test for ticket cost generation - PENDING (OPTIONAL)
11. Task 5.6: Write property test for task cost calculation - PENDING (OPTIONAL)

See `.kiro/specs/glpi-11-upgrade/tasks.md` for the complete task list.
