# Design Document: GLPI 11 Upgrade

## Overview

This design document outlines the technical approach for upgrading the GLPI Costs plugin from version 3.0.6 (supporting GLPI 10.0 to <11.0) to version 3.1.0 (supporting GLPI 10.0 to <12.0). The upgrade focuses on maintaining backward compatibility with GLPI 10.x while ensuring full compatibility with GLPI 11.x.

The plugin's core functionality—automatic cost generation for tickets based on fixed and time-based costs—will remain unchanged. The upgrade primarily involves updating version constraints, verifying API compatibility, and testing against GLPI 11.

### Design Philosophy

- **Backward Compatibility**: Maintain support for GLPI 10.0+ to avoid breaking existing installations
- **Minimal Changes**: Only modify code where GLPI 11 introduces breaking changes
- **Conservative Approach**: Leverage existing GLPI 10 patterns that remain compatible with GLPI 11
- **Testing Focus**: Emphasize validation over refactoring

## Architecture

### Current Architecture (GLPI 10)

The plugin follows GLPI's standard plugin architecture:

```
costs/
├── setup.php           # Plugin metadata and initialization
├── hook.php            # Installation/uninstallation hooks
├── inc/                # Class definitions
│   ├── config.class.php
│   ├── entity.class.php
│   ├── entity_profile.class.php
│   ├── ticket.class.php
│   └── task.class.php
├── front/              # Form handlers
│   ├── config.form.php
│   ├── entity.form.php
│   └── entity_profile.form.php
└── templates/          # Twig templates
    ├── config.html.twig
    └── billable_dropdown.html.twig
```

### Architecture Changes for GLPI 11

**No structural changes required**. The plugin architecture remains compatible with GLPI 11.

### Key Integration Points

1. **Hook Registration** (setup.php)
   - Uses `Glpi\Plugin\Hooks` namespace (introduced in GLPI 10, stable in GLPI 11)
   - Hook constants: `CSRF_COMPLIANT`, `POST_ITEM_FORM`, `PRE_ITEM_UPDATE`, `ITEM_ADD`, `ITEM_PURGE`

2. **Database Layer** (all inc/*.class.php files)
   - Uses `DBConnection` static methods for database metadata
   - Uses query builder pattern for SELECT queries
   - Uses direct methods for INSERT/UPDATE/DELETE operations

3. **Template Rendering** (inc/config.class.php, inc/ticket.class.php)
   - Uses `TemplateRenderer::getInstance()->display()`
   - Template namespace: `@costs/`

4. **Session Management** (setup.php, inc/*.class.php)
   - Uses `Session::haveRight()` and `Session::haveRightsOr()`
   - Uses `Session::getCurrentInterface()`

## Components and Interfaces

### Component 1: Version Configuration (setup.php)

**Current Implementation:**
```php
define('PLUGIN_COSTS_VERSION', '3.0.6');
define("PLUGIN_COSTS_MIN_GLPI", "10.0");
define("PLUGIN_COSTS_MAX_GLPI", "11.0");
```

**Updated Implementation:**
```php
define('PLUGIN_COSTS_VERSION', '3.1.0');
define("PLUGIN_COSTS_MIN_GLPI", "10.0");
define("PLUGIN_COSTS_MAX_GLPI", "12.0");
```

**Rationale**: 
- Increment minor version (3.0.6 → 3.1.0) to indicate new GLPI version support
- Update MAX_GLPI to "12.0" to support all GLPI 11.x versions
- Maintain MIN_GLPI at "10.0" for backward compatibility

### Component 2: Database Type Hints

**Current Pattern:**
```php
/** @var \DBmysql $DB */
global $DB;
```

**Analysis**: 
- The `@var \DBmysql` type hint is a PHPDoc annotation, not a runtime type
- GLPI 11 may deprecate the `DBmysql` class name in favor of generic `DB` or `DBConnection`
- However, PHPDoc annotations don't affect runtime behavior

**Decision**: 
- **No changes required** for GLPI 11 compatibility
- The `$DB` global variable works identically in GLPI 10 and 11
- PHPDoc annotations are for IDE support only

**Verification Needed**:
- Test that database operations work correctly in GLPI 11
- If GLPI 11 shows deprecation warnings, update PHPDoc to `@var \DB $DB`

### Component 3: Database Connection Methods

**Current Usage:**
```php
DBConnection::getDefaultCharset()
DBConnection::getDefaultCollation()
DBConnection::getDefaultPrimaryKeySignOption()
```

**Analysis**:
- These static methods were introduced in GLPI 10
- They abstract database-specific configuration
- GLPI 11 maintains these methods for compatibility

**Decision**: **No changes required**

### Component 4: Hook Registration

**Current Implementation:**
```php
use Glpi\Plugin\Hooks;

$PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['costs'] = true;
$PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['costs'] = [PluginCostsTicket::class,'postItemForm'];
// ... other hooks
```

**Analysis**:
- The `Glpi\Plugin\Hooks` namespace was introduced in GLPI 10
- GLPI 11 maintains this namespace and all hook constants
- This is the recommended pattern for both versions

**Decision**: **No changes required**

### Component 5: Template Rendering

**Current Implementation:**
```php
use Glpi\Application\View\TemplateRenderer;

$template = "@costs/config.html.twig";
$template_options = ['item' => $config, 'credit' => $credit_enabled];
TemplateRenderer::getInstance()->display($template, $template_options);
```

**Analysis**:
- `TemplateRenderer` was introduced in GLPI 10
- GLPI 11 maintains the same API
- Template namespace syntax (`@pluginname/`) is standard

**Decision**: **No changes required**

### Component 6: Session and Rights Management

**Current Implementation:**
```php
Session::haveRight('entity', UPDATE)
Session::haveRightsOr("config", [READ, UPDATE])
Session::getCurrentInterface()
```

**Analysis**:
- These methods are core GLPI session management APIs
- GLPI 11 maintains backward compatibility
- No API changes documented

**Decision**: **No changes required**

### Component 7: Migration Class

**Current Implementation:**
```php
$migration = new Migration(PLUGIN_COSTS_VERSION);
$migration->addField($table, 'field_name', 'type');
$migration->dropField($table, 'field_name');
$migration->executeMigration();
```

**Analysis**:
- Migration class API is stable across GLPI versions
- GLPI 11 maintains the same migration methods
- No breaking changes documented

**Decision**: **No changes required**

### Component 8: Search Options

**Current Implementation:**
```php
public static function rawSearchOptionsToAdd(): array
{
    $opt[] = [
        'id'            => '1000',
        'table'         => self::getTable(),
        'field'         => 'billable',
        'name'          => __("Billable", 'cost'),
        'datatype'      => 'bool',
        'searchtype'    => 'equals',
        'joinparams'    => ['jointype' => 'child']
    ];
    return $opt;
}
```

**Analysis**:
- Search options format is stable in GLPI 10 and 11
- The `rawSearchOptionsToAdd()` method is the standard pattern
- No format changes required

**Decision**: **No changes required**

### Component 9: Form Generation

**Current Implementation:**
```php
Dropdown::showYesNo("field_name", $value, -1, ['display' => false]);
Html::closeForm(false);
```

**Analysis**:
- These are stable GLPI UI helper methods
- GLPI 11 maintains backward compatibility
- No API changes documented

**Decision**: **No changes required**

## Data Models

### Database Schema

The plugin uses five database tables:

1. **glpi_plugin_costs_configs** - Global plugin configuration
2. **glpi_plugin_costs_entities** - Per-entity cost configuration
3. **glpi_plugin_costs_entities_profiles** - Entity-profile cost mappings
4. **glpi_plugin_costs_tickets** - Ticket billability tracking
5. **glpi_plugin_costs_tasks** - Task cost tracking

**Schema Compatibility**: All table schemas use GLPI-standard patterns and remain compatible with GLPI 11.

**No schema changes required** for GLPI 11 compatibility.

### Data Flow

1. **Ticket Creation**: When a ticket is created, the plugin checks entity configuration and creates a billability record
2. **Task Addition**: When a task is added to a billable ticket, the plugin calculates time-based costs
3. **Ticket Update**: When ticket status changes, costs are generated based on configuration

**Data flow remains unchanged** in GLPI 11.

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*


### Property 1: Version Constants Configuration

*For any* plugin installation, the version constants PLUGIN_COSTS_MIN_GLPI, PLUGIN_COSTS_MAX_GLPI, and PLUGIN_COSTS_VERSION should be set to "10.0", "12.0", and "3.1.0" respectively.

**Validates: Requirements 1.1, 1.2, 1.3**

### Property 2: Database Table Creation

*For any* database table created during plugin installation, the table should use DBConnection::getDefaultCharset(), DBConnection::getDefaultCollation(), and DBConnection::getDefaultPrimaryKeySignOption() for configuration.

**Validates: Requirements 2.2, 2.3, 2.4**

### Property 3: Plugin Installation Success

*For any* GLPI 11.x installation, executing plugin_costs_install() should complete without throwing exceptions and all expected database tables should exist afterward.

**Validates: Requirements 10.1, 10.3, 10.4, 12.1**

### Property 4: Plugin Uninstallation Success

*For any* GLPI 11.x installation with the plugin installed, executing plugin_costs_uninstall() should complete without throwing exceptions.

**Validates: Requirements 10.2**

### Property 5: Template Rendering

*For any* template in the plugin, rendering the template with valid data should complete without throwing exceptions on GLPI 11.

**Validates: Requirements 4.3**

### Property 6: Form Rendering

*For any* form generated by the plugin, rendering the form should complete without throwing exceptions and produce valid HTML on GLPI 11.

**Validates: Requirements 9.3**

### Property 7: Cost Configuration Round Trip

*For any* valid cost configuration data, saving the configuration to the database and then retrieving it should produce equivalent data on GLPI 11.

**Validates: Requirements 12.2**

### Property 8: Ticket Cost Generation

*For any* ticket created in a billable entity, the plugin should generate a cost entry according to the entity's configuration on GLPI 11.

**Validates: Requirements 12.3**

### Property 9: Task Cost Calculation

*For any* task added to a billable ticket, the plugin should calculate time-based costs correctly according to the entity's time cost configuration on GLPI 11.

**Validates: Requirements 12.4**

### Property 10: Search Options Integration

*For any* ticket search that includes the billable field, the search should execute without errors and return results with the billable field populated on GLPI 11.

**Validates: Requirements 8.3**

### Property 11: Hook Execution

*For any* GLPI event that should trigger a plugin hook (ticket creation, task addition, etc.), the corresponding hook callback should execute without errors on GLPI 11.

**Validates: Requirements 3.7**

### Property 12: Rights Management

*For any* user with specific rights (entity UPDATE, config READ/UPDATE), the plugin should correctly allow or deny access to plugin features based on those rights on GLPI 11.

**Validates: Requirements 5.4**

### Property 13: Migration Execution

*For any* plugin upgrade scenario, executing the migration should complete without errors and update the database schema correctly on GLPI 11.

**Validates: Requirements 7.7**

### Property 14: Cross-Version Compatibility

*For any* core plugin feature (cost configuration, ticket cost generation, task cost calculation), the feature should function identically on GLPI 10.x and GLPI 11.x.

**Validates: Requirements 11.2, 11.3, 11.4**

## Error Handling

### Current Error Handling

The plugin uses GLPI's standard error handling patterns:

1. **Database Errors**: Handled by GLPI's `$DB->doQueryOrDie()` method
2. **Missing Data**: Handled by checking return values and creating default records
3. **Permission Errors**: Handled by GLPI's rights system before plugin code executes

### Error Handling for GLPI 11

**No changes required**. GLPI 11 maintains the same error handling patterns.

### Specific Error Scenarios

1. **Installation Failure**: If database table creation fails, GLPI will display an error and prevent plugin activation
2. **Missing Configuration**: If entity configuration is missing, the plugin creates a default configuration with inheritance enabled
3. **Invalid Cost Values**: Form validation prevents invalid numeric inputs

## Testing Strategy

### Dual Testing Approach

The upgrade will use both unit tests and property-based tests:

- **Unit Tests**: Verify specific examples, edge cases, and GLPI 11 compatibility
- **Property Tests**: Verify universal properties across all inputs

### Unit Testing Focus

Unit tests will focus on:

1. **Version Constant Verification**: Test that constants are set to correct values
2. **Installation/Uninstallation**: Test that plugin lifecycle methods execute successfully
3. **UI Rendering**: Test that specific pages render without errors
4. **GLPI 11 Specific Tests**: Test compatibility with GLPI 11 APIs

### Property-Based Testing Focus

Property tests will focus on:

1. **Data Persistence**: Round-trip properties for configuration save/load
2. **Cost Calculation**: Properties for cost generation logic
3. **Cross-Version Compatibility**: Properties that verify identical behavior on GLPI 10 and 11

### Testing Environment

Tests should be run against:

1. **GLPI 10.0.x** (minimum supported version)
2. **GLPI 10.0.latest** (latest GLPI 10 release)
3. **GLPI 11.0.x** (initial GLPI 11 release)
4. **GLPI 11.0.latest** (latest GLPI 11 release)

### Property Test Configuration

- **Minimum 100 iterations** per property test
- **Tag format**: `Feature: glpi-11-upgrade, Property {number}: {property_text}`
- Each property test references its design document property

### Manual Testing Checklist

After automated tests pass, perform manual testing:

1. Install plugin on fresh GLPI 11 instance
2. Configure entity costs
3. Create billable and non-billable tickets
4. Add tasks to tickets and verify cost generation
5. Verify search functionality includes billable field
6. Test entity inheritance of cost configuration
7. Verify all UI pages render correctly
8. Test plugin uninstallation

## Implementation Notes

### Version Update Process

1. Update version constants in `setup.php`
2. Run automated tests against GLPI 11
3. Fix any compatibility issues discovered
4. Update CHANGELOG.md with version 3.1.0 changes
5. Create release tag

### Backward Compatibility Verification

To ensure GLPI 10 compatibility is maintained:

1. Run full test suite against GLPI 10.0.x
2. Verify no regressions in existing functionality
3. Test upgrade path from 3.0.6 to 3.1.0 on GLPI 10

### Code Review Checklist

Before release, verify:

- [ ] All version constants updated
- [ ] No deprecated API usage
- [ ] All tests pass on GLPI 10 and 11
- [ ] CHANGELOG.md updated
- [ ] README.md updated if needed
- [ ] No breaking changes to plugin API

## Deployment Strategy

### Release Process

1. **Testing Phase**: Run full test suite on GLPI 10 and 11
2. **Documentation**: Update README and CHANGELOG
3. **Version Tag**: Create git tag for v3.1.0
4. **Release Notes**: Document GLPI 11 support and any changes
5. **Distribution**: Update plugin on GLPI marketplace

### Upgrade Path for Users

Users upgrading from 3.0.6 to 3.1.0:

1. **On GLPI 10**: Standard plugin update, no special steps required
2. **On GLPI 11**: Install 3.1.0 directly (3.0.6 won't install on GLPI 11)
3. **Upgrading GLPI 10→11**: Update plugin to 3.1.0 before or after GLPI upgrade

### Rollback Plan

If issues are discovered after release:

1. Users can downgrade to 3.0.6 on GLPI 10.x
2. Users on GLPI 11 must wait for hotfix release
3. Critical issues will be addressed in 3.1.1 patch release

## Risk Assessment

### Low Risk Areas

- Version constant updates (simple, well-tested)
- Database operations (using stable APIs)
- Template rendering (no API changes)
- Hook registration (using GLPI 10 patterns that work in 11)

### Medium Risk Areas

- Cross-version testing (requires multiple GLPI installations)
- Edge cases in cost calculation (complex business logic)
- Entity inheritance (recursive logic)

### Mitigation Strategies

1. **Comprehensive Testing**: Test on multiple GLPI versions
2. **Conservative Changes**: Only modify what's necessary
3. **Community Testing**: Release beta version for community feedback
4. **Monitoring**: Track issue reports after release

## Future Considerations

### GLPI 12 Preparation

When GLPI 12 is released:

1. Review GLPI 12 changelog for breaking changes
2. Update MAX_GLPI to "13.0"
3. Increment version to 3.2.0
4. Repeat compatibility testing process

### API Modernization

Future versions could consider:

1. Adopting new GLPI APIs if introduced
2. Refactoring to use more modern PHP patterns
3. Adding automated integration tests
4. Improving code documentation

### Feature Enhancements

Potential future features (not in this upgrade):

1. Support for additional cost types
2. Cost reporting improvements
3. Integration with other GLPI plugins
4. API endpoints for cost data

## Conclusion

This upgrade is primarily a compatibility update with minimal code changes. The plugin's architecture and implementation patterns are already compatible with GLPI 11, requiring only version constraint updates and verification testing. The conservative approach minimizes risk while ensuring users can adopt GLPI 11 without losing Costs plugin functionality.
