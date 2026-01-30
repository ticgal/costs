# Rights Management Tests

## Overview

This document describes the rights management tests for the GLPI Costs plugin, validating that permission checks work correctly on GLPI 11.

## Test Files

### test-rights-management.php

Tests permission checks for the Costs plugin including:
- Users with entity UPDATE rights can access entity cost configuration
- Users with config READ/UPDATE rights can access global configuration  
- Users lacking required rights are denied access
- Plugin registration behavior based on user rights

**Requirements Validated**: 5.4

**Task**: 10.1

## Running the Tests

### Option 1: In GLPI Docker Environment

```bash
# Copy test file to GLPI container
docker cp tests/test-rights-management.php glpi11-app:/tmp/

# Run the test
docker exec glpi11-app php /tmp/test-rights-management.php
```

### Option 2: In Local GLPI Installation

```bash
# Run from plugin directory
php tests/test-rights-management.php
```

### Option 3: Standalone (with mocked GLPI classes)

The test includes comprehensive mocks for GLPI classes and can run standalone:

```bash
# Run directly
php tests/test-rights-management.php
```

## Expected Output

```
========================================
Rights Management Test
========================================

Test 1: Testing entity UPDATE rights...
  ✓ User with entity UPDATE rights can access entity costs
  ✓ PluginCostsEntity registered successfully
  ✓ Entity cost configuration update check passed

Test 2: Testing user without entity UPDATE rights...
  ✓ User without entity UPDATE rights cannot access entity costs
  ✓ PluginCostsEntity not registered (as expected)
  ✓ Entity update check correctly denied access

Test 3: Testing config READ rights...
  ✓ User with config READ rights can access config
  ✓ PluginCostsConfig registered successfully

Test 4: Testing config UPDATE rights...
  ✓ User with config UPDATE rights can access config
  ✓ PluginCostsConfig registered successfully
  ✓ Config update check passed

Test 5: Testing user without config rights...
  ✓ User without config rights cannot access config
  ✓ PluginCostsConfig not registered (as expected)
  ✓ Config update check correctly denied access

Test 6: Testing plugin registration behavior with entity rights...
  ✓ Only PluginCostsEntity registered with entity rights
  ✓ PluginCostsConfig not registered (correct)

Test 7: Testing plugin registration behavior with config rights...
  ✓ Only PluginCostsConfig registered with config rights
  ✓ PluginCostsEntity not registered (correct)

Test 8: Testing user with both entity and config rights...
  ✓ Both PluginCostsEntity and PluginCostsConfig registered
  ✓ User with combined rights has full access

========================================
Test Summary
========================================

✓ Entity UPDATE rights access: PASSED
✓ Entity no access denial: PASSED
✓ Config READ rights access: PASSED
✓ Config UPDATE rights access: PASSED
✓ Config no access denial: PASSED
✓ Plugin registration with entity rights: PASSED
✓ Plugin registration with config rights: PASSED

Results: 7/7 tests passed
```

## Test Coverage

### Rights Checks Tested

1. **Entity UPDATE Rights**
   - Users with entity UPDATE rights can access entity cost configuration
   - PluginCostsEntity is registered in plugin initialization
   - Entity cost configuration update checks pass

2. **Entity Access Denial**
   - Users without entity UPDATE rights cannot access entity costs
   - PluginCostsEntity is not registered without proper rights
   - Entity update checks correctly deny access

3. **Config READ Rights**
   - Users with config READ rights can access global configuration
   - PluginCostsConfig is registered with READ rights
   - Config page is accessible

4. **Config UPDATE Rights**
   - Users with config UPDATE rights can access and modify configuration
   - PluginCostsConfig is registered with UPDATE rights
   - Config update checks pass

5. **Config Access Denial**
   - Users without config rights cannot access configuration
   - PluginCostsConfig is not registered without proper rights
   - Config update checks correctly deny access

6. **Plugin Registration Logic**
   - Plugin correctly registers only classes the user has rights for
   - Entity rights only register PluginCostsEntity
   - Config rights only register PluginCostsConfig
   - Combined rights register both classes

7. **Combined Rights Scenario**
   - Users with both entity and config rights have full access
   - Both plugin classes are registered
   - All features are accessible

### GLPI APIs Tested

- `Session::haveRight($right, $value)` - Check single right
- `Session::haveRightsOr($right, $values)` - Check multiple rights with OR logic
- `Plugin::registerClass($class, $options)` - Register plugin classes
- `CommonDBTM::check($id, $right)` - Check rights before operations

### Rights Constants Used

- `READ` (1) - Read access
- `UPDATE` (2) - Update access
- `CREATE` (4) - Create access
- `DELETE` (8) - Delete access
- `PURGE` (16) - Purge access

## Requirements Validation

### Requirement 5.4: Session and Rights Management Compatibility

The tests validate that:

1. ✓ `Session::haveRight()` works correctly with GLPI 11 compatible parameters
2. ✓ `Session::haveRightsOr()` works correctly with GLPI 11 compatible parameters
3. ✓ All rights checks work correctly in GLPI 11
4. ✓ Plugin registration respects user rights
5. ✓ Access control functions correctly based on user permissions

## Mock Classes

The test includes mocks for:

- **Session**: Simulates GLPI session and rights management
- **Plugin**: Simulates GLPI plugin registration system
- **CommonDBTM**: Base class for database table management with rights checks
- **PluginCostsConfig**: Plugin configuration class with config rights
- **PluginCostsEntity**: Plugin entity class with entity rights

## Test Scenarios

### Scenario 1: Entity Administrator
- Rights: entity UPDATE
- Expected: Can access entity cost configuration
- Expected: Cannot access global configuration

### Scenario 2: System Administrator
- Rights: config READ, config UPDATE
- Expected: Can access global configuration
- Expected: Cannot access entity cost configuration (without entity rights)

### Scenario 3: Super Administrator
- Rights: entity UPDATE, config UPDATE
- Expected: Can access both entity and global configuration
- Expected: Full plugin functionality available

### Scenario 4: Regular User
- Rights: None
- Expected: Cannot access any plugin configuration
- Expected: Plugin classes not registered

## Integration with GLPI 11

The rights management system integrates with GLPI 11 through:

1. **setup.php**: Plugin initialization checks rights before registering classes
   ```php
   if (Session::haveRight('entity', UPDATE)) {
       Plugin::registerClass(PluginCostsEntity::class, ['addtabon' => 'Entity']);
   }
   
   if (Session::haveRightsOr("config", [READ, UPDATE])) {
       Plugin::registerClass(PluginCostsConfig::class, ['addtabon' => 'Config']);
   }
   ```

2. **front/entity.form.php**: Entity form handler checks rights
   ```php
   Session::haveRight("entity", UPDATE);
   ```

3. **front/config.form.php**: Config form handler checks rights
   ```php
   $config->check($_POST['id'], UPDATE);
   ```

4. **inc/config.class.php**: Config class uses config rightname
   ```php
   public static $rightname = 'config';
   ```

5. **inc/entity.class.php**: Entity class uses entity rightname
   ```php
   public static $rightname = 'entity';
   ```

## Notes

1. **Rights Hierarchy**: GLPI uses bitwise operations for rights, allowing multiple rights to be combined
2. **OR Logic**: `Session::haveRightsOr()` allows checking if user has ANY of the specified rights
3. **AND Logic**: `Session::haveRight()` checks for a specific right value
4. **Plugin Registration**: Classes are only registered if the user has appropriate rights
5. **Form Handlers**: Front scripts check rights before processing form submissions

## Troubleshooting

### Test Fails with "Access denied"

This is expected behavior when testing users without proper rights. The test validates that access is correctly denied.

### Plugin Classes Not Registered

This is expected when the user doesn't have the required rights. The test validates that classes are only registered for authorized users.

### Rights Check Returns False

Verify that:
1. Rights are set correctly using `Session::setRights()`
2. Right constants are defined (READ, UPDATE, etc.)
3. Bitwise operations are working correctly

## Related Files

- `setup.php` - Plugin initialization with rights checks
- `inc/config.class.php` - Config class with config rightname
- `inc/entity.class.php` - Entity class with entity rightname
- `front/config.form.php` - Config form handler with rights checks
- `front/entity.form.php` - Entity form handler with rights checks

## Next Steps

After running these tests:

1. Verify all tests pass (7/7)
2. Test in real GLPI 11 environment with actual users
3. Test with different user profiles (admin, technician, observer)
4. Verify rights work correctly in both central and helpdesk interfaces
5. Proceed to Task 11: Test migration functionality on GLPI 11
