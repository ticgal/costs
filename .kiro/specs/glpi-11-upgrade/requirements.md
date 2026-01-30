# Requirements Document: GLPI 11 Upgrade

## Introduction

This document specifies the requirements for upgrading the GLPI Costs plugin from version 3.0.6 (supporting GLPI 10.0 to <11.0) to support GLPI version 11.x. The Costs plugin automatically generates cost entries for tickets based on fixed and time-based costs configured per entity. The upgrade must maintain backward compatibility where possible while adopting GLPI 11's new APIs and best practices.

## Glossary

- **Plugin**: The GLPI Costs plugin that extends GLPI functionality
- **GLPI_Core**: The base GLPI application framework
- **Entity**: A GLPI organizational unit that can have cost configurations
- **Ticket**: A GLPI help desk request that can have associated costs
- **TicketTask**: A task associated with a ticket that can generate time-based costs
- **Migration**: The database schema update process during plugin installation/upgrade
- **Hook**: A callback function that integrates plugin functionality into GLPI core events
- **TemplateRenderer**: GLPI's Twig-based template rendering system
- **DBConnection**: GLPI's database connection and query interface
- **CommonDBTM**: GLPI's base class for database table management
- **Session**: GLPI's user session and rights management system

## Requirements

### Requirement 1: Version Constraint Update

**User Story:** As a plugin maintainer, I want to update version constraints, so that the plugin can be installed on GLPI 11.x systems.

#### Acceptance Criteria

1. THE Plugin SHALL define PLUGIN_COSTS_MIN_GLPI as "10.0"
2. THE Plugin SHALL define PLUGIN_COSTS_MAX_GLPI as "12.0"
3. THE Plugin SHALL increment PLUGIN_COSTS_VERSION to "3.1.0"
4. WHEN GLPI_Core version is between 10.0 and <12.0, THE Plugin SHALL be installable

### Requirement 2: Database API Compatibility

**User Story:** As a developer, I want to use GLPI 11 compatible database APIs, so that the plugin works correctly with the updated database layer.

#### Acceptance Criteria

1. WHEN accessing the database connection, THE Plugin SHALL use the DBConnection class methods compatible with GLPI 11
2. WHEN creating database tables, THE Plugin SHALL use DBConnection::getDefaultCharset() for charset specification
3. WHEN creating database tables, THE Plugin SHALL use DBConnection::getDefaultCollation() for collation specification
4. WHEN creating database tables, THE Plugin SHALL use DBConnection::getDefaultPrimaryKeySignOption() for primary key configuration
5. WHEN executing database queries, THE Plugin SHALL use the query builder interface or prepared statements
6. THE Plugin SHALL NOT use deprecated DBmysql type hints in method signatures

### Requirement 3: Hook Registration Compatibility

**User Story:** As a plugin developer, I want to register hooks using GLPI 11 compatible methods, so that plugin functionality integrates correctly with GLPI core events.

#### Acceptance Criteria

1. WHEN registering hooks, THE Plugin SHALL use the Glpi\Plugin\Hooks namespace constants
2. WHEN registering CSRF compliance, THE Plugin SHALL use Hooks::CSRF_COMPLIANT
3. WHEN registering form hooks, THE Plugin SHALL use Hooks::POST_ITEM_FORM
4. WHEN registering update hooks, THE Plugin SHALL use Hooks::PRE_ITEM_UPDATE
5. WHEN registering add hooks, THE Plugin SHALL use Hooks::ITEM_ADD
6. WHEN registering purge hooks, THE Plugin SHALL use Hooks::ITEM_PURGE
7. THE Plugin SHALL verify all hook registrations work correctly in GLPI 11

### Requirement 4: Template Rendering Compatibility

**User Story:** As a developer, I want to ensure template rendering works with GLPI 11, so that all UI components display correctly.

#### Acceptance Criteria

1. WHEN rendering templates, THE Plugin SHALL use TemplateRenderer::getInstance()->display()
2. WHEN passing template options, THE Plugin SHALL use the GLPI 11 compatible parameter format
3. THE Plugin SHALL verify all Twig templates render correctly in GLPI 11
4. WHEN using template paths, THE Plugin SHALL use the "@costs/" namespace prefix

### Requirement 5: Session and Rights Management Compatibility

**User Story:** As a developer, I want to ensure session and rights checks work with GLPI 11, so that access control functions correctly.

#### Acceptance Criteria

1. WHEN checking user rights, THE Plugin SHALL use Session::haveRight() with GLPI 11 compatible parameters
2. WHEN checking multiple rights, THE Plugin SHALL use Session::haveRightsOr() with GLPI 11 compatible parameters
3. WHEN getting current interface, THE Plugin SHALL use Session::getCurrentInterface()
4. THE Plugin SHALL verify all rights checks work correctly in GLPI 11

### Requirement 6: CommonDBTM and CommonGLPI Compatibility

**User Story:** As a developer, I want to ensure all plugin classes extend GLPI 11 compatible base classes, so that database operations and UI integration work correctly.

#### Acceptance Criteria

1. WHEN extending CommonDBTM, THE Plugin SHALL use GLPI 11 compatible method signatures
2. WHEN implementing getTypeName(), THE Plugin SHALL use the correct return type (string)
3. WHEN implementing getTabNameForItem(), THE Plugin SHALL use GLPI 11 compatible parameters
4. WHEN implementing displayTabContentForItem(), THE Plugin SHALL use GLPI 11 compatible parameters
5. THE Plugin SHALL verify all class inheritance works correctly in GLPI 11

### Requirement 7: Migration Class Compatibility

**User Story:** As a plugin maintainer, I want to ensure database migrations work with GLPI 11, so that plugin installation and upgrades function correctly.

#### Acceptance Criteria

1. WHEN creating migrations, THE Plugin SHALL instantiate Migration with the plugin version
2. WHEN adding fields, THE Plugin SHALL use Migration::addField() with GLPI 11 compatible parameters
3. WHEN dropping fields, THE Plugin SHALL use Migration::dropField() with GLPI 11 compatible parameters
4. WHEN adding keys, THE Plugin SHALL use Migration::addKey() with GLPI 11 compatible parameters
5. WHEN dropping keys, THE Plugin SHALL use Migration::dropKey() with GLPI 11 compatible parameters
6. WHEN executing migrations, THE Plugin SHALL call Migration::executeMigration()
7. THE Plugin SHALL verify all migration operations work correctly in GLPI 11

### Requirement 8: Search Options Compatibility

**User Story:** As a developer, I want to ensure search options work with GLPI 11, so that ticket search functionality includes cost fields.

#### Acceptance Criteria

1. WHEN defining search options, THE Plugin SHALL use the rawSearchOptionsToAdd() format compatible with GLPI 11
2. WHEN specifying join parameters, THE Plugin SHALL use GLPI 11 compatible joinparams structure
3. THE Plugin SHALL verify search options display correctly in GLPI 11 ticket search

### Requirement 9: Form Generation Compatibility

**User Story:** As a developer, I want to ensure HTML form generation works with GLPI 11, so that configuration forms display and function correctly.

#### Acceptance Criteria

1. WHEN generating forms, THE Plugin SHALL use Html::closeForm() with GLPI 11 compatible parameters
2. WHEN using dropdowns, THE Plugin SHALL use Dropdown class methods compatible with GLPI 11
3. THE Plugin SHALL verify all forms render and submit correctly in GLPI 11

### Requirement 10: Plugin Lifecycle Compatibility

**User Story:** As a plugin maintainer, I want to ensure plugin installation and uninstallation work with GLPI 11, so that users can manage the plugin correctly.

#### Acceptance Criteria

1. WHEN installing the plugin, THE Plugin SHALL execute plugin_costs_install() successfully on GLPI 11
2. WHEN uninstalling the plugin, THE Plugin SHALL execute plugin_costs_uninstall() successfully on GLPI 11
3. WHEN initializing the plugin, THE Plugin SHALL execute plugin_init_costs() successfully on GLPI 11
4. THE Plugin SHALL verify all database tables are created correctly during installation on GLPI 11

### Requirement 11: Backward Compatibility

**User Story:** As a plugin maintainer, I want to maintain compatibility with GLPI 10.x, so that existing users can continue using the plugin.

#### Acceptance Criteria

1. THE Plugin SHALL continue to support GLPI 10.0 and later versions
2. WHEN running on GLPI 10.x, THE Plugin SHALL function with all existing features
3. WHEN running on GLPI 11.x, THE Plugin SHALL function with all existing features
4. THE Plugin SHALL NOT break existing functionality when upgrading from GLPI 10 to GLPI 11

### Requirement 12: Testing and Validation

**User Story:** As a plugin maintainer, I want to validate the plugin works correctly on GLPI 11, so that users have a reliable upgrade path.

#### Acceptance Criteria

1. WHEN installed on GLPI 11, THE Plugin SHALL install without errors
2. WHEN creating cost configurations, THE Plugin SHALL save and retrieve data correctly on GLPI 11
3. WHEN creating tickets, THE Plugin SHALL generate costs correctly on GLPI 11
4. WHEN creating ticket tasks, THE Plugin SHALL generate time-based costs correctly on GLPI 11
5. WHEN viewing entity configurations, THE Plugin SHALL display the costs tab correctly on GLPI 11
6. WHEN viewing global configuration, THE Plugin SHALL display the config page correctly on GLPI 11
